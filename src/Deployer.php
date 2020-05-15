<?php

namespace WP2StaticS3;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Aws\S3\S3Client;
use Aws\CloudFront\CloudFrontClient;
use Aws\Exception\AwsException;
use Aws\Credentials\Credentials;
use WP_CLI;

class Deployer {

    // prepare deploy, if modifies URL structure, should be an action
    // $this->prepareDeploy();

    // options - load from addon's static methods

    public function __construct() {
        global $envS3;
        
        switch ($envS3) {
            case 'staging':
                $this->bucketName = AWS_S3_BUCKET_STAGING;
                break;
            case 'preview':
                $this->bucketName = AWS_S3_BUCKET_PREVIEW;
                break;
            default:
                $this->bucketName = Controller::getValue( 's3Bucket' );
                break;
        }
        // Set s3bucket to process
        // $this->bucketName = $envS3 ?? Controller::getValue( 's3Bucket' );
    }

    public function upload_files( string $processed_site_path ) : void {
        // check if dir exists
        if ( ! is_dir( $processed_site_path ) ) {
            return;
        }

        $client_options = [
            'profile' => Controller::getValue( 's3Profile' ),
            'version' => 'latest',
            'region' => Controller::getValue( 's3Region' ),
        ];

        /*
            If no credentials option, SDK attempts to load credentials from
            your environment in the following order:

                 - environment variables.
                 - a credentials .ini file.
                 - an IAM role.
        */
        if (
            Controller::getValue( 's3AccessKeyID' ) &&
            Controller::getValue( 's3SecretAccessKey' )
        ) {
            $client_options['credentials'] = [
                'key' => Controller::getValue( 's3AccessKeyID' ),
                'secret' => \WP2Static\CoreOptions::encrypt_decrypt(
                    'decrypt',
                    Controller::getValue( 's3SecretAccessKey' )
                ),
            ];
            unset( $client_options['profile'] );
        }

        // instantiate S3 client
        $s3 = new \Aws\S3\S3Client( $client_options );

        // iterate each file in ProcessedSite
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $processed_site_path,
                RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        foreach ( $iterator as $filename => $file_object ) {
            $base_name = basename( $filename );
            if ( $base_name != '.' && $base_name != '..' ) {
                $real_filepath = realpath( $filename );

                // TODO: do filepaths differ when running from WP-CLI (non-chroot)?

                $cache_key = str_replace( $processed_site_path, '', $filename );

                if ( \WP2Static\DeployCache::fileisCached( $cache_key ) ) {
                    continue;
                }

                if ( ! $real_filepath ) {
                    $err = 'Trying to deploy unknown file to S3: ' . $filename;
                    \WP2Static\WsLog::l( $err );
                    continue;
                }

                // Standardise all paths to use / (Windows support)
                $filename = str_replace( '\\', '/', $filename );

                if ( ! is_string( $filename ) ) {
                    continue;
                }

                $s3_key =
                    Controller::getValue( 's3RemotePath' ) ?
                    Controller::getValue( 's3RemotePath' ) . '/' .
                    ltrim( $cache_key, '/' ) :
                    ltrim( $cache_key, '/' );

                $mime_type = MimeTypes::GuessMimeType( $filename );

                // Check bucket exist
                try {
                    $isBucketExist = $s3->headBucket([
                        'Bucket' => $this->bucketName
                    ]);
                } catch (\Throwable $th) {
                    // Bucket not exist
                    if (strpos($th->getMessage(), '404 Not Found')) {
                        $isBucketExist = false;
                    }
                }

                // Create bucket if not exist
                if(!$isBucketExist){
                    $s3->createBucket([
                        'ACL'    => 'public-read',
                        'Bucket' => $this->bucketName
                    ]);
                    
                    // Set bucket as static website
                    $s3->putBucketWebsite([
                        'Bucket' => $this->bucketName,
                        'WebsiteConfiguration' => [ // REQUIRED
                            'ErrorDocument' => [
                                'Key' => 'error.html', // REQUIRED
                            ],
                            'IndexDocument' => [
                                'Suffix' => 'index.html', // REQUIRED
                            ]
                        ],
                    ]);

                }

                if ( defined( 'WP_CLI' ) && WP_CLI ) {
                    // Do WP-CLI-specific things.
                    WP_CLI::line( 'Deploy ' . $filename . ' to bucket => ' .  $this->bucketName);
                }

                $result = $s3->putObject(
                    [
                        'Bucket' => $this->bucketName,
                        'Key' => $s3_key,
                        'Body' => file_get_contents( $filename ),
                        'ACL'    => 'public-read',
                        'ContentType' => $mime_type,
                    ]
                );

                if ( $result['@metadata']['statusCode'] === 200 ) {
                    \WP2Static\DeployCache::addFile( $cache_key );
                }
            }
        }
    }


    public static function cloudfront_invalidate_all_items() : void {
        if ( ! Controller::getValue( 'cfDistributionID' ) ) {
            return;
        }

        \WP2Static\WsLog::l( 'Invalidating all CloudFront items' );

        /*
            If no credentials option, SDK attempts to load credentials from
            your environment in the following order:

                 - environment variables.
                 - a credentials .ini file.
                 - an IAM role.
        */
        if (
            Controller::getValue( 's3AccessKeyID' ) &&
            Controller::getValue( 's3SecretAccessKey' )
        ) {

            $credentials = new \Aws\Credentials\Credentials(
                Controller::getValue( 's3AccessKeyID' ),
                \WP2Static\CoreOptions::encrypt_decrypt(
                    'decrypt',
                    Controller::getValue( 's3SecretAccessKey' )
                )
            );
        }

        $client = \Aws\CloudFront\CloudFrontClient::factory(
            [
                'profile' => Controller::getValue( 'cfProfile' ),
                'region' => Controller::getValue( 'cfRegion' ),
                'version' => 'latest',
                'credentials' => isset( $credentials ) ? $credentials : '',
            ]
        );

        try {
            $result = $client->createInvalidation(
                [
                    'DistributionId' => Controller::getValue( 'cfDistributionID' ),
                    'InvalidationBatch' => [
                        'CallerReference' => 'WP2Static S3 Add-on ' . time(),
                        'Paths' => [
                            'Items' => [ '/*' ],
                            'Quantity' => 1,
                        ],
                    ],
                ]
            );

        } catch ( AwsException $e ) {
            // output error message if fails
            error_log( $e->getMessage() );
        }
    }
}

