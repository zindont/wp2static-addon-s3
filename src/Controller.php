<?php

namespace WP2StaticS3;

class Controller {
    public function run() : void {
        add_filter( 'wp2static_add_menu_items', [ 'WP2StaticS3\Controller', 'addSubmenuPage' ] );

        add_action(
            'admin_post_wp2static_s3_save_options',
            [ $this, 'saveOptionsFromUI' ],
            15,
            1
        );

        add_action(
            'wp2static_deploy',
            [ $this, 'deploy' ],
            15,
            1
        );

        add_action(
            'wp2static_post_deploy_trigger',
            [ 'WP2StaticS3\Deployer', 'cloudfront_invalidate_all_items' ],
            15,
            1
        );

        if ( defined( 'WP_CLI' ) ) {
            \WP_CLI::add_command(
                'wp2static s3',
                [ 'WP2StaticS3\CLI', 's3' ]
            );
        }
    }

    /**
     *  Get all add-on options
     *
     *  @return mixed[] All options
     */
    public static function getOptions() : array {
        global $wpdb;
        $options = [];

        $table_name = $wpdb->prefix . 'wp2static_addon_s3_options';

        $rows = $wpdb->get_results( "SELECT * FROM $table_name" );

        foreach ( $rows as $row ) {
            $options[ $row->name ] = $row;
        }
        
        if (defined('AWS_ACCESS_KEY_ID')) {
            $options['s3AccessKeyID']->value = AWS_ACCESS_KEY_ID;
        }

        if (defined('AWS_SECRET_ACCESS_KEY')) {
            $options['s3SecretAccessKey']->value = \WP2Static\CoreOptions::encrypt_decrypt('encrypt', AWS_SECRET_ACCESS_KEY);
        }
        
        if (defined('AWS_S3_BUCKET_STAGING')) {
            $options['s3Bucket']->value = AWS_S3_BUCKET_STAGING;
        }

        return $options;
    }

    /**
     * Seed options
     */
    public static function seedOptions() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_addon_s3_options';

        $query_string =
            "INSERT INTO $table_name (name, value, label, description) VALUES (%s, %s, %s, %s);";

        $query = $wpdb->prepare(
            $query_string,
            'cfDistributionID',
            '',
            'CloudFront Distribution ID',
            'If using CloudFront, set this to auto-invalidate cache'
        );

        $wpdb->query( $query );

        $query = $wpdb->prepare(
            $query_string,
            's3Bucket',
            '',
            'Bucket name',
            ''
        );

        $wpdb->query( $query );

        $query = $wpdb->prepare(
            $query_string,
            's3AccessKeyID',
            '',
            'Access Key ID',
            ''
        );

        $wpdb->query( $query );

        $query = $wpdb->prepare(
            $query_string,
            's3SecretAccessKey',
            '',
            'Secret Access Key',
            ''
        );

        $wpdb->query( $query );

        $query = $wpdb->prepare(
            $query_string,
            'cfAccessKeyID',
            '',
            'Access Key ID',
            ''
        );

        $wpdb->query( $query );

        $query = $wpdb->prepare(
            $query_string,
            'cfSecretAccessKey',
            '',
            'Secret Access Key',
            ''
        );

        $wpdb->query( $query );

        $query = $wpdb->prepare(
            $query_string,
            's3Region',
            '',
            'Region',
            ''
        );

        $wpdb->query( $query );

        $query = $wpdb->prepare(
            $query_string,
            's3Profile',
            '',
            'Profile',
            ''
        );

        $wpdb->query( $query );

        $query = $wpdb->prepare(
            $query_string,
            'cfRegion',
            '',
            'Region',
            ''
        );

        $wpdb->query( $query );

        $query = $wpdb->prepare(
            $query_string,
            'cfProfile',
            '',
            'Profile',
            ''
        );

        $wpdb->query( $query );

        $query = $wpdb->prepare(
            $query_string,
            's3RemotePath',
            '',
            'Path prefix in bucket',
            'Optionally, deploy to a subdirectory within bucket'
        );

        $wpdb->query( $query );
    }

    /**
     * Save options
     *
     * @param mixed $value option value to save
     */
    public static function saveOption( string $name, $value ) : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_addon_s3_options';

        $query_string = "INSERT INTO $table_name (name, value) VALUES (%s, %s);";
        $query = $wpdb->prepare( $query_string, $name, $value );

        $wpdb->query( $query );
    }

    public static function renderS3Page() : void {
        $view = [];
        $view['nonce_action'] = 'wp2static-s3-options';
        $view['uploads_path'] = \WP2Static\SiteInfo::getPath( 'uploads' );
        $s3_path = \WP2Static\SiteInfo::getPath( 'uploads' ) . 'wp2static-processed-site.s3';

        $view['options'] = self::getOptions();

        $view['s3_url'] =
            is_file( $s3_path ) ?
                \WP2Static\SiteInfo::getUrl( 'uploads' ) . 'wp2static-processed-site.s3' : '#';

        require_once __DIR__ . '/../views/s3-page.php';
    }


    public function deploy( string $processed_site_path ) : void {
        \WP2Static\WsLog::l( 'S3 Addon deploying' );

        $s3_deployer = new Deployer();
        $s3_deployer->upload_files( $processed_site_path );
    }

    public static function activate_for_single_site() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_addon_s3_options';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            value VARCHAR(255) NOT NULL,
            label VARCHAR(255) NULL,
            description VARCHAR(255) NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        $options = self::getOptions();

        if ( ! isset( $options['s3Bucket'] ) ) {
            self::seedOptions();
        }
    }

    public static function deactivate_for_single_site() : void {
    }

    public static function deactivate( bool $network_wide = null ) : void {
        if ( $network_wide ) {
            global $wpdb;

            $query = 'SELECT blog_id FROM %s WHERE site_id = %d;';

            $site_ids = $wpdb->get_col(
                sprintf(
                    $query,
                    $wpdb->blogs,
                    $wpdb->siteid
                )
            );

            foreach ( $site_ids as $site_id ) {
                switch_to_blog( $site_id );
                self::deactivate_for_single_site();
            }

            restore_current_blog();
        } else {
            self::deactivate_for_single_site();
        }
    }

    public static function activate( bool $network_wide = null ) : void {
        if ( $network_wide ) {
            global $wpdb;

            $query = 'SELECT blog_id FROM %s WHERE site_id = %d;';

            $site_ids = $wpdb->get_col(
                sprintf(
                    $query,
                    $wpdb->blogs,
                    $wpdb->siteid
                )
            );

            foreach ( $site_ids as $site_id ) {
                switch_to_blog( $site_id );
                self::activate_for_single_site();
            }

            restore_current_blog();
        } else {
            self::activate_for_single_site();
        }
    }

    /**
     * Add WP2Static submenu
     *
     * @param mixed[] $submenu_pages array of submenu pages
     * @return mixed[] array of submenu pages
     */
    public static function addSubmenuPage( array $submenu_pages ) : array {
        $submenu_pages['s3'] = [ 'WP2StaticS3\Controller', 'renderS3Page' ];

        return $submenu_pages;
    }

    public static function saveOptionsFromUI() : void {
        check_admin_referer( 'wp2static-s3-options' );

        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_addon_s3_options';

        $wpdb->update(
            $table_name,
            [ 'value' => sanitize_text_field( $_POST['cfDistributionID'] ) ],
            [ 'name' => 'cfDistributionID' ]
        );

        $wpdb->update(
            $table_name,
            [ 'value' => sanitize_text_field( $_POST['s3Bucket'] ) ],
            [ 'name' => 's3Bucket' ]
        );

        $wpdb->update(
            $table_name,
            [ 'value' => sanitize_text_field( $_POST['s3AccessKeyID'] ) ],
            [ 'name' => 's3AccessKeyID' ]
        );

        $secret_access_key =
            $_POST['s3SecretAccessKey'] ?
            \WP2Static\CoreOptions::encrypt_decrypt(
                'encrypt',
                sanitize_text_field( $_POST['s3SecretAccessKey'] )
            ) : '';

        $wpdb->update(
            $table_name,
            [ 'value' => $secret_access_key ],
            [ 'name' => 's3SecretAccessKey' ]
        );

        $wpdb->update(
            $table_name,
            [ 'value' => sanitize_text_field( $_POST['cfAccessKeyID'] ) ],
            [ 'name' => 'cfAccessKeyID' ]
        );

        $secret_access_key =
            $_POST['cfSecretAccessKey'] ?
            \WP2Static\CoreOptions::encrypt_decrypt(
                'encrypt',
                sanitize_text_field( $_POST['cfSecretAccessKey'] )
            ) : '';

        $wpdb->update(
            $table_name,
            [ 'value' => $secret_access_key ],
            [ 'name' => 'cfSecretAccessKey' ]
        );

        $wpdb->update(
            $table_name,
            [ 'value' => sanitize_text_field( $_POST['s3Region'] ) ],
            [ 'name' => 's3Region' ]
        );

        $wpdb->update(
            $table_name,
            [ 'value' => sanitize_text_field( $_POST['cfRegion'] ) ],
            [ 'name' => 'cfRegion' ]
        );

        $wpdb->update(
            $table_name,
            [ 'value' => sanitize_text_field( $_POST['s3Profile'] ) ],
            [ 'name' => 's3Profile' ]
        );

        $wpdb->update(
            $table_name,
            [ 'value' => sanitize_text_field( $_POST['cfProfile'] ) ],
            [ 'name' => 'cfProfile' ]
        );

        $wpdb->update(
            $table_name,
            [ 'value' => sanitize_text_field( $_POST['s3RemotePath'] ) ],
            [ 'name' => 's3RemotePath' ]
        );

        wp_safe_redirect( admin_url( 'admin.php?page=wp2static-s3' ) );
        exit;
    }

    /**
     * Get option value
     *
     * @return string option value
     */
    public static function getValue( string $name ) : string {

        $options = self::getOptions();

        $option_value = $options[$name]->value;

        if ( ! is_string( $option_value ) ) {
            return '';
        }

        return $option_value;
    }
}

