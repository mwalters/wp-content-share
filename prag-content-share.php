<?php
/*
Plugin Name: Content Sharing Shortcode
Plugin URI: https://www.pragmatticode.com
Description: Allows content to be shared amongst pages. Supports WordPress Multisite as well.
Version: 1.0
Author: Pragmatticode
Author URI: https://www.pragmatticode.com
*/

if (!class_exists('PragContentShare')) {
    class PragContentShare {

        private $settings;
        private $network_settings;

        public function __construct() {

            // Load settings
            $this->get_settings();

            // Set location values
            $this->path = untrailingslashit( plugin_dir_path( __FILE__ ) );
            $this->url  = untrailingslashit( plugin_dir_url( __FILE__ ) );

            // Hook in where necessary
            add_shortcode( 'shared', array( &$this, 'handle_shortcode' ) );

            if ( is_multisite() ) {
                add_action( 'admin_menu', array( &$this, 'add_settings_page' ) );
                add_action( 'network_admin_menu', array( &$this, 'network_settings' ) );
                add_action( 'admin_post_update_content_share_network_settings',  array( &$this, 'update_content_share_network_settings' ) );
            }
        }

        /**
         * Retreives a piece of content from specified site by post ID and displays it
         * @param  array  $atts Parameters specifying which piece of content to retrieve
         * @return string       Content retreived
         */
        public function handle_shortcode( $atts = array() ) {

            if ( $this->settings['default_site'] != '' ) {
                $default_site = $this->settings['default_site'];
            } elseif ( $this->network_settings['default_site'] != '' ) {
                $default_site = $this->network_settings['default_site'];
            } else {
                $default_site = '';
            }

            $a = shortcode_atts( array(
                'site'               => $default_site,
                'strip_links'        => false,
                'relative_urls'      => false,
                'content'            => ''
            ), $atts );

            if ( $a['content'] == '' ) {
                return '';
            }

            $content = $this->process_post( $a );

            return '<div class="prag-shared-content">' . $content . '</div>';
        }

        /**
         * Process filters/shortcodes/etc on post
         * @param  object $post WordPress Post Object
         * @return object       Empty if something went wrong, otherwise, WordPress Post Object
         */
        public function process_post( $a = array() ) {

            $a['strip_links']   = $this->get_boolean( $a['strip_links'] );
            $a['relative_urls'] = $this->get_boolean( $a['relative_urls'] );

            if ( is_multisite() && ( get_current_blog_id() != $a['site'] ) ) {
                switch_to_blog( $a['site'] );
                $content = get_post( $a['content'] );
                if ( is_object( $content ) ) {
                    $content = apply_filters( 'the_content', $content->post_content );
                } else {
                    $content = '';
                }
                restore_current_blog();
            } else {
                $content = apply_filters( 'the_content', get_post( $a['content'] )->post_content );
            }

            if ( $a['strip_links'] ) {
                $content = preg_replace('#<a.*?>(.*?)</a>#i', '\1', $content);
            }

            if ( $a['relative_urls'] ) {
                $urls = $this->get_urls( $content );
                for ( $i=0; $i < count( $urls ); $i++ ) {
                    $new_url = $this->make_url_relative( $urls[$i], $a['site'] );
                    $content = str_replace( $urls[$i], $new_url, $content );
                }
            }

            return $content;
        }

        /**
         * Adds settings page to Network admin
         * @return void
         */
        public function network_settings() {

            add_submenu_page(
                'settings.php',
                __('Content Sharing', 'prag_content_share_lang'),
                __('Content Sharing', 'prag_content_share_lang'),
                'manage_network_options',
                'content_share-network-settings',
                array( &$this, 'render_settings_form' )
            );

            return;
        }

        /**
         * Adds settings page to WordPress administration area
         * @return void
         */
        public function add_settings_page() {

            add_submenu_page( 'options-general.php', 'Content Sharing', 'Content Sharing', 'manage_options', 'prag-contentshare-settings', array( &$this, 'settings_page' ) );

            return;
        }

        /**
         * Adds settings page for maintaining various options
         * @return void
         */
        public function settings_page() {

            if ( isset( $_POST['save-prag-content-share-settings']) ) {
                check_admin_referer( 'save-prag-content-share-settings-site' );
                $this->process_settings_form();
                $this->save_settings();
            }

            $this->render_settings_form();

            return;
        }

        /**
         * Renders settings form
         * @return void]
         */
        public function render_settings_form() {

            if ( is_network_admin() ) {
                $default_site = $this->network_settings['default_site'];
            } else {
                $default_site = $this->settings['default_site'];
            }

            require_once( 'templates/form-settings.php' );

            return;
        }

        /**
         * Retrieves settings from _options table
         * @return void
         */
        private function get_settings() {

            $this->settings = unserialize( get_option( 'prag-content_share-settings' ) );
            $this->network_settings = unserialize( get_site_option( 'prag-content_share-network_settings' ) );

            if ( ! isset( $this->settings['default_site'] ) ) $this->settings['default_site'] = '';
            if ( ! isset( $this->network_settings['default_site'] ) ) $this->network_settings['default_site'] = '';

            return;
        }

        /**
         * Save settings to _options table
         * @return void
         */
        private function save_settings() {

            update_option( 'prag-content_share-settings', serialize( $this->settings ) );
            update_site_option( 'prag-content_share-network_settings', serialize( $this->network_settings ) );

            return;
        }

        /**
         * Processes form fields from settings form
         * @return void
         */
        private function process_settings_form() {

            if ( isset( $_POST['default_site'] ) ) {

                if ( isset( $_POST['save-prag-content-share-network_settings'] ) && $_POST['save-prag-content-share-network_settings'] == 1 ) {
                    $this->network_settings['default_site'] = trim( $_POST['default_site'] );
                } else {
                    $this->settings['default_site'] = trim( $_POST['default_site'] );
                }
            }

            return;
        }

        /**
         * Updates network settings
         */
        public function update_content_share_network_settings() {

            check_admin_referer('save-prag-content-share-settings-network');

            if ( ! current_user_can( 'manage_network_options' ) ) { wp_die( 'Access denied' ); }

            $this->process_settings_form();
            $this->save_settings();

            wp_redirect( admin_url( 'network/settings.php?page=content_share-network-settings' ) );

            exit;
        }

        /**
         * Determines if a URL should be relative or not and makes it so if needed
         * @param  string $url URL to parse
         * @return string      Parsed URL
         */
        private function make_url_relative( $url = '', $site_id ) {

            if ( $url === '' ) return '';

            $site_url        = get_site_url();
            $parsed_url      = parse_url( $url );
            $parsed_site_url = parse_url( $site_url );

            if ( is_multisite() && ( get_current_blog_id() != $a['site'] ) ) {
                switch_to_blog( $site_id );
                $subscribed_site_base_url = parse_url( get_bloginfo( 'url' ), PHP_URL_PATH );
                restore_current_blog();
            } else {
                $subscribed_site_base_url = parse_url( get_bloginfo( 'url' ), PHP_URL_PATH );
            }

            if ( $parsed_url['host'] == $parsed_site_url['host'] ) {
                $return_url = str_replace( $subscribed_site_base_url, $parsed_site_url['path'], $parsed_url['path'] );
                $return_url .= ( isset( $parsed_url['query'] ) ) ? '?' . $parsed_url['query'] : '';
            } else {
                $return_url = $url;
            }

            return $return_url;
        }

        /**
         * Get URLs from a string
         * @param  string $string Text to parse URLs out of
         * @return array          Collection of URLs found in string
         */
        private function get_urls( $string ) {

            $regex = '/https?\:\/\/[^\" ]+/i';
            preg_match_all( $regex, $string, $matches );
            return ( $matches[0] );
        }

        /**
         * Get boolean value of variable
         * @param  mixed $v Variable to be tested for boolean
         * @return bool     Boolean value of variable passed in
         */
        private function get_boolean( $v = null ) {

            if ( $v === null ) return false;
            if ( strtolower($v) === 'yes' ) return true;
            if ( strtolower($v) === 'no' ) return false;
            if ( $v === true ) return true;
            if ( $v === false ) return false;
            if ( $v === 'true' ) return true;
            if ( $v === 'false' ) return false;
            if ( $v === 1 ) return true;
            if ( $v === '1') return true;
            if ( $v === 0 ) return false;
            if ( $v === '0' ) return false;
        }

    }
}

// Create object if needed
if ( ! @$PragContentShare && function_exists( 'add_action' )) { $PragContentShare = new PragContentShare(); }
