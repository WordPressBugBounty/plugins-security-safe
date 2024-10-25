<?php

namespace SovereignStack\SecuritySafe;

// Prevent Direct Access
defined( 'ABSPATH' ) || die;
/**
 * Class Yoda - Whats up, Yoda knows.
 *
 * @package SecuritySafe
 * @since 2.0.0
 */
class Yoda {
    /**
     * Yoda constructor.
     */
    // Construct, Yoda does not.
    /**
     * Constant variables, this method sets.
     *
     * @since  2.0.0
     */
    static function set_constants() {
        // General
        define( 'SECSAFE_PLUGIN', SECSAFE_SLUG . '/' . str_replace( '-premium', '', SECSAFE_SLUG ) . '.php' );
        define( 'SECSAFE_OPTIONS', 'securitysafe_options' );
        // Database Tables
        define( 'SECSAFE_DB_FIREWALL', 'sovstack_logs' );
        define( 'SECSAFE_DB_STATS', 'sovstack_stats' );
        // Directory Structure
        define( 'SECSAFE_DIR_LANG', SECSAFE_DIR . '/languages/' );
        define( 'SECSAFE_DIR_SECURITY', SECSAFE_DIR_CORE . '/security' );
        define( 'SECSAFE_DIR_PRIVACY', SECSAFE_DIR_SECURITY . '/privacy' );
        define( 'SECSAFE_DIR_FIREWALL', SECSAFE_DIR_SECURITY . '/firewall' );
        define( 'SECSAFE_DIR_ADMIN', SECSAFE_DIR_CORE . '/admin' );
        define( 'SECSAFE_DIR_ADMIN_INCLUDES', SECSAFE_DIR_ADMIN . '/includes' );
        define( 'SECSAFE_DIR_ADMIN_PAGES', SECSAFE_DIR_ADMIN . '/pages' );
        define( 'SECSAFE_DIR_ADMIN_TABLES', SECSAFE_DIR_ADMIN . '/tables' );
        define( 'SECSAFE_DIR_ADMIN_ASSETS', SECSAFE_DIR_ADMIN . '/assets' );
        define( 'SECSAFE_URL', plugin_dir_url( SECSAFE_PLUGIN ) );
        define( 'SECSAFE_URL_ASSETS', SECSAFE_URL . 'core/assets/' );
        define( 'SECSAFE_URL_ADMIN_ASSETS', SECSAFE_URL . 'core/admin/assets/' );
        define( 'SECSAFE_URL_AUTHOR', 'https://sovstack.com/' );
        define( 'SECSAFE_URL_MORE_INFO', 'https://wpsecuritysafe.com/' );
        define( 'SECSAFE_URL_MORE_INFO_PRO', admin_url( 'admin.php?page=' . SECSAFE_SLUG . '-pricing' ) );
        define( 'SECSAFE_URL_ACCOUNT', admin_url( 'admin.php?page=' . SECSAFE_SLUG . '-account' ) );
        define( 'SECSAFE_URL_TWITTER', 'https://twitter.com/wpsecuritysafe' );
        define( 'SECSAFE_URL_WP', 'https://wordpress.org/plugins/security-safe/' );
        define( 'SECSAFE_URL_WP_REVIEWS', 'https://wordpress.org/support/plugin/security-safe/reviews/' );
        define( 'SECSAFE_URL_WP_REVIEWS_NEW', 'https://wordpress.org/support/plugin/security-safe/reviews/#new-post' );
        // Admin URLs
        $admin_uri = 'admin.php?page=' . REQUEST::key( 'page', '', SECSAFE_SLUG );
        define( 'SECSAFE_ADMIN_URI', $admin_uri );
        define( 'SECSAFE_ADMIN_URL', admin_url( $admin_uri ) );
        // Setup the current admin page
        $admin_uri_current = $admin_uri . REQUEST::key(
            'tab',
            '',
            '',
            '&tab='
        );
        define( 'SECSAFE_ADMIN_URI_CURRENT', $admin_uri_current );
        define( 'SECSAFE_ADMIN_URL_CURRENT', admin_url( $admin_uri_current ) );
        // Settings URLs
        $settings_uri = $admin_uri . '&tab=settings';
        define( 'SECSAFE_SETTINGS_URI', $settings_uri );
        define( 'SECSAFE_SETTINGS_URL', admin_url( $settings_uri ) );
        define( 'SECSAFE_CURRENT_SITE_ID', get_current_blog_id() );
    }

    /**
     * Retrieves the visitor's IP address
     *
     * @return string
     *
     * @since  2.0.0
     */
    static function get_ip() : string {
        $ip = '';
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        foreach ( $keys as $key ) {
            if ( !$ip ) {
                // Get IP address(es) proxy server(s)
                $ip = REQUEST::SERVER_text_field( $key );
                if ( empty( $ip ) ) {
                    break;
                }
            }
        }
        if ( !$ip ) {
            $ip = __( 'IP Unavailable', SECSAFE_SLUG );
        }
        return $ip;
    }

    /**
     * Gets the User Agent of the current session
     *
     * @return string
     *
     * @since  2.1.0
     */
    static function get_user_agent() : string {
        $ua = ( defined( 'DOING_CRON' ) ? 'WP Cron' : false );
        return ( $ua ?: REQUEST::SERVER_text_field( 'HTTP_USER_AGENT' ) );
    }

    /**
     * Checks to see if the plugin has created a custom login error.
     *
     * @return bool
     *
     * @since 2.4.0
     */
    static function is_login_error() : bool {
        global $SecuritySafe;
        return $SecuritySafe->login_error;
    }

    /**
     * Retrieves the name of the table for firewall
     *
     * @return string
     *
     * @since  2.0.0
     */
    static function get_table_main() : string {
        global $wpdb;
        return $wpdb->prefix . SECSAFE_DB_FIREWALL;
    }

    /**
     * Retrieves the name of the table for stats
     *
     * @return string
     *
     * @since  2.0.0
     */
    static function get_table_stats() : string {
        global $wpdb;
        return $wpdb->prefix . SECSAFE_DB_STATS;
    }

    /**
     * Retrieves the limit of data types
     *
     * @param string $type
     * @param bool $mx
     *
     * @return int
     *
     * @since  2.0.0
     */
    static function get_display_limits( string $type, bool $mx = false ) : int {
        //Janitor::log( 'get_display_limits()' );
        $types = Yoda::get_types();
        // Require Valid Type
        if ( isset( $types[$type] ) ) {
            //Janitor::log( 'get_display_limits(): Valid Type' );
            $limits = [
                '404s'       => 500,
                'logins'     => 100,
                'allow_deny' => 10,
                'activity'   => 1000,
            ];
            if ( isset( $limits[$type] ) ) {
                return $limits[$type];
            }
        }
        //Janitor::log( 'get_display_limits(): Default' );
        // Default lowest value / false
        return 0;
    }

    /**
     * Retrieves the array of data types
     *
     * @return array
     *
     * @since  2.0.0
     */
    static function get_types() : array {
        return [
            '404s'       => __( '404s Errors', SECSAFE_TRANSLATE ),
            'logins'     => __( 'Login Attempts', SECSAFE_TRANSLATE ),
            'comments'   => __( 'Comments', SECSAFE_TRANSLATE ),
            'allow_deny' => __( 'Firewall Rules', SECSAFE_TRANSLATE ),
            'activity'   => __( 'User Activity', SECSAFE_TRANSLATE ),
            'blocked'    => __( 'Blocked Activity', SECSAFE_TRANSLATE ),
            'threats'    => __( 'Threats', SECSAFE_TRANSLATE ),
        ];
    }

    /**
     * A defined list usernames that should not be used for accounts as they are too common and often used to bruteforce access into the site.
     *
     * @return array
     */
    public static function get_bad_usernames() : array {
        return [
            'account',
            'adm',
            'admin',
            'admin1',
            'administrator',
            'author',
            'contributor',
            'demo',
            'editor',
            'guest',
            'manager',
            'hostname',
            'qwerty',
            'root',
            'seo',
            'support',
            'sysadmin',
            'test',
            'testuser',
            'user',
            'wordpress',
            'wp',
            'wpadmin'
        ];
    }

    /**
     * Get Latest PHP Version
     *
     * @return array
     *
     * @since 2.4.0
     */
    public static function get_php_versions() : array {
        // https://endoflife.software/programming-languages/server-side-scripting/php
        // https://secure.php.net/ChangeLog-7.php
        // https://secure.php.net/ChangeLog-8.php
        $versions = [
            '8.3.0' => '8.3.12',
            '8.2.0' => '8.2.24',
            '8.1.0' => '8.1.30',
            '8.0.0' => '8.0.30',
        ];
        $eol = self::get_php_eol();
        $now = time();
        // Figure out what the min version should be
        foreach ( $eol as $version => $date ) {
            if ( $now < strtotime( $date ) ) {
                $versions['min'] = $version;
            } else {
                break;
            }
        }
        return $versions;
    }

    /**
     * Provides the End of Life date for a given version.
     * @link https://endoflife.date/php
     *
     * @param string $php_version
     *
     * @return string|array
     */
    public static function get_php_eol( string $php_version = '' ) {
        $eol = [
            '8.3.0' => '2027-12-31',
            '8.2.0' => '2026-12-31',
            '8.1.0' => '2025-12-31',
            '8.0.0' => '2023-11-26',
            '7.4.0' => '2022-11-28',
            '7.3.0' => '2021-12-06',
            '7.2.0' => '2020-11-30',
            '7.1.0' => '2019-12-01',
            '7.0.0' => '2019-01-10',
            '5.6.0' => '2018-12-31',
        ];
        return $eol[$php_version] ?? $eol;
    }

    /**
     * Grabs an array of all the sites or an array with boolean false
     *
     * @since 2.5.0
     *
     * @return array
     */
    public static final function get_sites() : array {
        global $SecuritySafe;
        if ( empty( $SecuritySafe->sites ) ) {
            $sites = [];
            if ( is_multisite() ) {
                $sites_temp = get_sites();
                // Build the sites array with blog_id as key
                foreach ( $sites_temp as $site ) {
                    $site->blog_id = (int) $site->blog_id;
                    $sites[$site->blog_id] = $site;
                }
            } else {
                // Add the only site to the array with same structure
                $site = [];
                $site['blog_id'] = SECSAFE_CURRENT_SITE_ID;
                $site['domain'] = str_replace( ['http://', 'https://'], '', get_site_url() );
                $sites[SECSAFE_CURRENT_SITE_ID] = (object) $site;
            }
            $SecuritySafe->sites = $sites;
        }
        return $SecuritySafe->sites;
    }

}
