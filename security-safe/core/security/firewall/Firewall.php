<?php

namespace SovereignStack\SecuritySafe;

// Prevent Direct Access
defined( 'ABSPATH' ) || die;
use DateTime;
use DateInterval;
/**
 * Class Firewall
 *
 * @package SecuritySafe
 * @since 2.0.0
 */
final class Firewall {
    /**
     * Determine if user is whitelisted in the DB
     *
     * @since  2.0.0
     */
    public static function is_whitelisted() : bool {
        //Janitor::log( 'Checking DB for whitelist. is_whitelisted()' );
        return self::get_listed( 'allow' );
    }

    /**
     * Determine if user is denied/allowed
     *
     * @param string $status
     *
     * @return bool
     *
     * @since  2.4.0
     */
    private static function get_listed( string $status = 'deny' ) : bool {
        global $wpdb, $SecuritySafe;
        $status = ( $status == 'allow' ? 'allow' : 'deny' );
        $ip_valid = filter_var( Yoda::get_ip(), FILTER_VALIDATE_IP );
        if ( $ip_valid ) {
            $table_name = Yoda::get_table_main();
            $now = date( 'Y-m-d H:i:s' );
            /**
             * @todo this is currently sanitized but it should be refactored to use standard wp security measures.
             * @since 03/15/2020
             */
            $query = "SELECT * FROM {$table_name} WHERE `ip` = '{$ip_valid}' AND `type` = 'allow_deny' AND `status` = '{$status}' AND `date_expire` > '{$now}' LIMIT 1";
            $results = $wpdb->get_results( $query );
        }
        $SecuritySafe->date_expires = $results[0]->date_expire ?? '';
        return isset( $results[0] );
    }

    /**
     * Determine if user is blacklisted in the DB
     *
     * @since  2.0.0
     */
    public static function is_blacklisted() : bool {
        //Janitor::log( 'Checking DB for blacklist. is_blacklisted()' );
        return self::get_listed( 'deny' );
    }

    /**
     * Rate limit activity by IP address
     *
     * @since  2.4.0
     */
    public static function rate_limit() : void {
        global $wpdb, $SecuritySafe;
        //Janitor::log( 'rate_limit()' );
        $settings_access = $SecuritySafe->get_page_settings( 'access' );
        $settings_access_default = Plugin::get_page_settings_min( 'access' );
        // Bail if listed
        if ( $SecuritySafe->is_blacklisted() || $SecuritySafe->is_whitelisted() ) {
            //Janitor::log( 'blacklisted or whitelisted' );
            return;
        }
        $ip_valid = filter_var( Yoda::get_ip(), FILTER_VALIDATE_IP );
        $autoblock_enabled = ( isset( $settings_access['autoblock'] ) ? (int) $settings_access['autoblock'] : (int) $settings_access_default['autoblock'] );
        if ( $ip_valid && $autoblock_enabled > 0 ) {
            $method = ( isset( $settings_access['autoblock_method'] ) ? (int) $settings_access['autoblock_method'] : (int) $settings_access_default['autoblock_method'] );
            $table_name = Yoda::get_table_main();
            $now = date( 'Y-m-d H:i:s' );
            // Get User Defined Mins
            $mins = ( isset( $settings_access['autoblock_timespan'] ) ? $settings_access['autoblock_timespan'] : (int) $settings_access_default['autoblock_timespan'] );
            //Janitor::log( 'mins: ' . $mins );
            $mins = ( $mins && is_numeric( $mins ) ? filter_var( $mins, FILTER_SANITIZE_NUMBER_INT ) : (int) $settings_access_default['autoblock_timespan'] );
            //Janitor::log( 'mins: ' . $mins );
            //Janitor::log( 'autoblock_timespan settings: ' . $settings_access['autoblock_timespan'] );
            //Janitor::log( 'autoblock_timespan default: ' . $default_settings['autoblock_timespan'] );
            $ago = date( 'Y-m-d H:i:s', strtotime( '-' . $mins . ' minutes', strtotime( $now ) ) );
            /**
             * @todo this is currently sanitized but it should be refactored to use standard wp security measures.
             * @since 03/15/2020
             */
            // Default to Failed Login Blocking
            $query = "SELECT SUM(`threats`) FROM {$table_name} WHERE `ip` = '{$ip_valid}' AND `type` = 'logins' AND `status` = 'failed' AND `date` >= '{$ago}' AND `date` <= '{$now}'";
            /**
             * @todo this is currently sanitized but it should be refactored to use standard wp security measures.
             * @since 03/15/2020
             */
            $total_score = $wpdb->get_var( $query );
            $total_score = ( isset( $total_score ) ? (int) $total_score : 0 );
            // Get User Defined Score
            $ban_score = ( isset( $settings_access['autoblock_threat_score'] ) ? $settings_access['autoblock_threat_score'] : (int) $settings_access_default['autoblock_threat_score'] );
            $ban_score = ( $ban_score && is_numeric( $ban_score ) ? filter_var( $ban_score, FILTER_SANITIZE_NUMBER_INT ) : (int) $settings_access_default['autoblock_threat_score'] );
            //Janitor::log( $total_score . ' >= ' . $ban_score  );
            if ( $total_score >= $ban_score ) {
                $SecuritySafe->blacklisted = true;
                //Janitor::log( 'running blacklist' );
                // Blacklist IP For X mins / X hrs / X days
                $table_name = Yoda::get_table_main();
                /**
                 * @todo this is currently sanitized but it should be refactored to use standard wp security measures.
                 * @since 03/15/2020
                 */
                $query = "SELECT * FROM {$table_name} WHERE `ip` = '{$ip_valid}' AND `type` = 'allow_deny' AND `status` = 'deny' AND `date_expire` != '0000-00-00 00:00:00' ORDER BY `date` DESC LIMIT 1";
                $results = $wpdb->get_results( $query );
                // First offense known in the database
                // Get User Defined Mins
                $ban_mins = ( isset( $settings_access['autoblock_ban_1'] ) ? $settings_access['autoblock_ban_1'] : (int) $settings_access_default['autoblock_ban_1'] );
                $ban_mins = ( $ban_mins && is_numeric( $ban_mins ) ? filter_var( $ban_mins, FILTER_SANITIZE_NUMBER_INT ) : (int) $settings_access_default['autoblock_ban_1'] );
                if ( $results ) {
                    // The user has been banned before
                    $ban_mins_check = $ban_mins + 1;
                    // The threshold above the first offense ban time
                    foreach ( $results as $r ) {
                        $date = new DateTime($r->date);
                        $date_expire = new DateTime($r->date_expire);
                        $diff = $date->diff( $date_expire );
                        $mins = $diff->format( '%i' );
                        $mins = $mins * 1;
                        if ( $mins < $ban_mins_check && $mins !== 0 ) {
                            // Get User Defined Hours
                            $ban_hrs = ( isset( $settings_access['autoblock_ban_2'] ) ? $settings_access['autoblock_ban_2'] : (int) $settings_access_default['autoblock_ban_2'] );
                            $ban_hrs = ( $ban_hrs && is_numeric( $ban_hrs ) ? filter_var( $ban_hrs, FILTER_SANITIZE_NUMBER_INT ) : (int) $settings_access_default['autoblock_ban_2'] );
                            $ban_time = ( $ban_hrs > 1 ? 'PT' . $ban_hrs . 'H' : 'PT1H' );
                            $ban_text = ( $ban_hrs > 1 ? sprintf( __( '%d hours', SECSAFE_TRANSLATE ), $ban_hrs ) : __( '1 hour', SECSAFE_TRANSLATE ) );
                        } else {
                            $ban_days = 1;
                            $ban_days = ( $ban_days && is_numeric( $ban_days ) ? filter_var( $ban_days, FILTER_SANITIZE_NUMBER_INT ) : (int) $settings_access_default['autoblock_ban_3'] );
                            $ban_time = ( $ban_days > 1 ? 'P' . $ban_days . 'D' : 'P1D' );
                            $ban_text = ( $ban_days > 1 ? sprintf( __( '%d days', SECSAFE_TRANSLATE ), $ban_days ) : __( '1 day', SECSAFE_TRANSLATE ) );
                        }
                        break;
                    }
                } else {
                    $ban_time = 'PT' . $ban_mins . 'M';
                    $ban_text = sprintf( __( '%d minutes', SECSAFE_TRANSLATE ), $ban_mins );
                }
                $date = new DateTime();
                $date = $date->add( new DateInterval($ban_time) );
                $args = [];
                // reset
                $args['date_expire'] = $date->format( 'Y-m-d H:i:s' );
                $args['details'] = sprintf( __( 'Too many offenses . Blacklisted for %s . ', SECSAFE_TRANSLATE ), $ban_text );
                $args['ip'] = $ip_valid;
                self::blacklist_ip( $args );
            }
        }
    }

    /**
     * Blacklist IP for period of time
     *
     * @param array $args
     *
     * @since  2.4.0
     */
    public static function blacklist_ip( array $args ) : void {
        $args['ip'] = Yoda::get_ip();
        if ( isset( $args['date_expire'] ) && $args['date_expire'] && filter_var( $args['ip'], FILTER_VALIDATE_IP ) ) {
            $args['status'] = 'deny';
            $args['details'] = ( isset( $args['details'] ) ? sanitize_text_field( $args['details'] ) : '' );
            $args['type'] = 'allow_deny';
            Janitor::add_entry( $args );
        }
    }

    /**
     * Logs the blocked attempt.
     *
     * @param array $args
     * @param bool $die Used to kill the PHP session. True by default.
     *
     * @since  2.0.0
     */
    public static function block( array $args = [], bool $die = true ) : void {
        global $SecuritySafe;
        // Bail if whitelisted
        if ( $SecuritySafe->is_whitelisted() ) {
            return;
        }
        $args['status'] = 'blocked';
        $args['threats'] = 1;
        // Add blocked Entry & Prevent Caching
        Janitor::add_entry( $args );
        if ( $die ) {
            $message = sprintf( __( '%s: Access blocked.', SECSAFE_TRANSLATE ), SECSAFE_NAME );
            $message .= ( SECSAFE_DEBUG ? ' - ' . $args['type'] . ': ' . $args['details'] : '' );
            status_header( '406', $message );
            // Block Attempt
            die( $message );
        }
    }

    /**
     * Logs the threat attempt.
     *
     * @param array $type
     * @param string $details
     *
     * @since  2.0.0
     */
    protected function threat( array $type, string $details = '' ) : void {
        global $SecuritySafe;
        // Bail if whitelisted
        if ( $SecuritySafe->is_whitelisted() ) {
            return;
        }
        $args = [];
        $args['type'] = $type;
        $args['details'] = ( $details ? $details : '' );
        $args['threats'] = 1;
        // Add threat Entry & prevent Caching
        Janitor::add_entry( $args );
    }

}
