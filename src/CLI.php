<?php

namespace WP2StaticS3;

use WP_CLI;


/**
 * WP2StaticS3 WP-CLI commands
 *
 * Registers WP-CLI commands for WP2StaticS3 under main wp2static cmd
 *
 * Usage: wp wp2static options set s3Bucket mybucketname
 */
class CLI {

    /**
     * S3 commands
     *
     * @param string[] $args CLI args
     * @param string[] $assoc_args CLI args
     */
    public function s3(
        array $args,
        array $assoc_args
    ) : void {
        $action = isset( $args[0] ) ? $args[0] : null;

        if ( empty( $action ) ) {
            WP_CLI::error( 'Missing required argument: <options>' );
        }

        if ( $action === 'options' ) {
            WP_CLI::line( 'TBC setting options for S3 addon' );
        }
    }

    public function deployCLI(array $args, array $assoc_args)
    {
        global $envS3;
        $envS3 = isset( $assoc_args['env'] ) ? $assoc_args['env'] : false;

        // do_action( 'wp2static_deploy', \WP2Static\ProcessedSite::getPath() );

        $processed_site_path = \WP2Static\ProcessedSite::getPath();
        
        \WP2Static\WsLog::l( 'S3 Addon deploying' );

        $s3_deployer = new Deployer();
        $s3_deployer->upload_files( $processed_site_path );
    }
}

