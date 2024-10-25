<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	use WP_Site;

	/**
	 * Class Janitor - Cleans up after the plugin
	 *
	 * @package SecuritySafe
	 * @since 2.0.0
	 */
	class Janitor {

		/**
		 * Janitor constructor.
		 */
		function __construct() {

			// Register enable plugin process
			add_action( "activated_plugin", [ self::class, 'enable_plugin'], 10, 2 );

			add_action( 'upgrader_process_complete', [ self::class, 'upgrade_complete' ], 10, 2 );

			// Cleanup Settings, Database, & Crons on Plugin Disable
			register_deactivation_hook( SECSAFE_FILE, [ self::class, 'disable_plugin' ] );

			// Add Cleanup Action
			add_action( 'secsafe_cleanup_tables_daily', [ self::class, 'cleanup_tables' ] );

			// Schedule Cleanup Services
			if ( ! wp_next_scheduled( 'secsafe_cleanup_tables_daily' ) ) {

				wp_schedule_event( time(), 'daily', 'secsafe_cleanup_tables_daily' );

			}

			// Create Tables For New Blog
			add_action( 'wp_insert_site', [ self::class, 'wp_insert_site' ] );

		}

		/**
		 * Writes to debug.log for troubleshooting
		 *
		 * @param string $message Message entered into the log
		 * @param string $file Location of the file where the error occured
		 * @param string $line Line number of where the error occured
		 *
		 * @since 0.1.0
		 */
		public static function log( string $message, string $file = '', string $line = '' ) : void {

			// Debug must be on
			if ( SECSAFE_DEBUG ) {

				$message = ( $message ) ? $message : 'Error: Log Message not defined!';
				$message .= ( $file && $line ) ? ' - ' . 'Occurred on line ' . $line . ' in file ' . $file : '';

				error_log( date( 'Y-M-j h:m:s' ) . " - " . $message . "\n", 3, SECSAFE_DIR . '/debug.log' );

			}

		}

		/**
		 * Run functions after upgrade
		 *
		 * @param object $upgrader_object
		 * @param array $options
		 *
		 * @since  2.0.1
		 */
		public static function upgrade_complete( object $upgrader_object, array $options ) : void {

			if (
				isset( $options['action'] ) && $options['action'] == 'update' &&
				isset( $options['type'] ) && $options['type'] == 'plugin'
			) {

				if ( isset( $options['plugins'] ) ) {

					if ( is_array( $options['plugins'] ) ) {

						foreach ( $options['plugins'] as $plugin ) {
							self::enable_plugin( $plugin );
						}

					} elseif ( is_string( $options['plugins'] ) ) {
						self::enable_plugin( $options['plugins'] );
					}

				}

			}

		}

		/**
		 * Creates database tables
		 *
		 * @since  2.5.0
		 *
		 * @param string $plugin
		 * @param bool   $network_wide
		 *
		 * @return void
		 */
		public static function enable_plugin( string $plugin, bool $network_wide = false ) : void {

			if (SECSAFE_PLUGIN === $plugin ) {

				global $SecuritySafe;

				if ( empty( $SecuritySafe ) ) {
					Plugin::init();
				}

				$sites = Yoda::get_sites();

				foreach( $sites as $site) {

					if ( is_multisite() ) {
						switch_to_blog( $site->blog_id );
					}

					if ( is_plugin_active( SECSAFE_PLUGIN ) ) {

						// Create Logs Table
						Janitor::create_table_logs();

						// Create Stats Table
						Janitor::create_table_stats();

						$args = [];
						$args['type'] = '404s';
						$args['threats'] = 0;
						$args['status'] = 'test';
						$args['details'] = ( isset( $args['details'] ) ) ? $args['details'] : sprintf( __( '%s plugin enabled.', SECSAFE_TRANSLATE ), SECSAFE_NAME );

						// Log Test 404
						Janitor::add_entry( $args );

						// Log Actual Activity
						Janitor::log_activity( $args );

					}

					if ( is_multisite() ) {
						restore_current_blog();
					}

				}

			}

		}

		/**
		 * Runs when a new blog is added in a multisite setup
		 *
		 * @since WP 5.1
		 * @since 1.0.0
		 *
		 * @param \WP_Site $new_site
		 *
		 * @return void
		 */
		public static function wp_insert_site( WP_Site $new_site ): void {

			self::enable_plugin( SECSAFE_PLUGIN );

		}

		/**
		 * Creates Firewall Table
		 *
		 * @since  2.0.0
		 */
		public static function create_table_logs() : void {

			global $wpdb;

			$table_main = Yoda::get_table_main();

			// Must have two spaces after PRIMARY KEY, UNIQUE and INDEX
			$wpdb->query( "CREATE TABLE IF NOT EXISTS `$table_main` (
	            ID BIGINT NOT NULL AUTO_INCREMENT,
	            `type` VARCHAR(10) NOT NULL default '',
	            `date` DATETIME NOT NULL,
	            `date_expire` DATETIME NOT NULL,
	            `ip` VARCHAR(50) NOT NULL default '',
	            `username` VARCHAR(50) NOT NULL default '',
	            `uri` VARCHAR(512) NOT NULL,
	            `referer` VARCHAR(512) NOT NULL default '',
	            `user_agent` VARCHAR(512) NOT NULL default '',
	            `threats` TINYINT(1) NOT NULL default '0',
	            `status` VARCHAR(10) NOT NULL default '',
	            `details` VARCHAR(512) NOT NULL default '',
	            `score` TINYINT(3) NOT NULL default '0',
	            PRIMARY KEY  (ID),
	            UNIQUE  (ID),
	            INDEX  (type, status)
	        ) ". $wpdb->get_charset_collate() .";" );

			// In case the table already existed, we are going to ensure it has the correct charset and collate
			$wpdb->query("ALTER TABLE `$table_main` CONVERT TO CHARACTER SET $wpdb->charset COLLATE $wpdb->collate;");

		}

		/**
		 * Creates Stats Table
		 *
		 * @since  2.0.0
		 */
		public static function create_table_stats() : void {

			global $wpdb;

			$table_stats = Yoda::get_table_stats();

			// Must have two spaces after PRIMARY KEY and UNIQUE
			$wpdb->query( "CREATE TABLE IF NOT EXISTS `$table_stats` (
	            `date` DATETIME NOT NULL,
	            `404s` BIGINT NOT NULL default '0',
	            `404s_threats` BIGINT NOT NULL default '0',
	            `blocked` BIGINT NOT NULL default '0',
	            `threats` BIGINT NOT NULL default '0',
	            `logins` BIGINT NOT NULL default '0',
	            `logins_failed` BIGINT NOT NULL default '0',
	            `logins_success` BIGINT NOT NULL default '0',
	            `logins_threats` BIGINT NOT NULL default '0',
	            `logins_blocked` BIGINT NOT NULL default '0',
	            PRIMARY KEY  (date),
	            UNIQUE  (date)
	        ) ". $wpdb->get_charset_collate() .";" );

			// In case the table already existed, we are going to ensure it has the correct charset and collate
			$wpdb->query("ALTER TABLE `$table_stats` CONVERT TO CHARACTER SET $wpdb->charset COLLATE $wpdb->collate;");

		}

		/**
		 * Add entry to database
		 *
		 * @param array $args
		 *
		 * @return bool
		 *
		 * @since  2.0.0
		 */
		public static function add_entry( array $args ) : bool {

			global $wpdb;

			// Prevent Caching
			Janitor::prevent_caching();

			$args   = ( isset( $args['type'] ) ) ? $args : [];
			$type   = ( isset( $args['type'] ) ) ? $args['type'] : false;
			$types  = Yoda::get_types();
			$result = false; // Default

			// Require Valid Type
			if ( isset( $types[ $type ] ) ) {

				/**
				 * Statically set for now
				 * @todo  log all wp cron activity not just Security Safe's
				 */
				$args['date'] = date( 'Y-m-d H:i:s' );

				if (
					$args['type'] != 'activity' &&
					$args['type'] != 'allow_deny'
				) {

					$args['uri']        = ( isset( $_SERVER['REQUEST_URI'] ) ) ? filter_var( $_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL ) : '';
					$args['referer']    = ( isset( $_SERVER['HTTP_REFERER'] ) ) ? filter_var( $_SERVER['HTTP_REFERER'], FILTER_SANITIZE_URL ) : '';
					$args['user_agent'] = Yoda::get_user_agent();
					$args['ip']         = Yoda::get_ip();

					$args['threats'] = ( isset( $args['threats'] ) && $args['threats'] ) ? 1 : 0;
					$args['score']   = ( isset( $args['score'] ) ) ? $args['score'] : 0;

					// Record Stats
					Janitor::add_stats( $args );

				}

				// Trim Data
				$targs = [];

				foreach ( $args as $key => $value ) {

					// Limit to 512 Characters
					$targs[ $key ] = substr( $value, 0, 512 );

				}

				// Add data to DB and insert() is sanitized by WP
				$result = $wpdb->insert( Yoda::get_table_main(), $targs );

				if ( ! $result ) {

					// Create Logs Table
					Janitor::create_table_logs();

					// Try Again now that a table exist
					$result = $wpdb->insert( Yoda::get_table_main(), $targs );

				}

			}

			return (bool) $result;

		}

		/**
		 * Prevent plugins like WP Super Cache and W3TC from caching any data on this page.
		 *
		 * @since  2.2.3
		 */
		public static function prevent_caching() : void {

			( defined( 'DONOTCACHEOBJECT' ) ) || define( 'DONOTCACHEOBJECT', true );
			( defined( 'DONOTCACHEDB' ) )  || define( 'DONOTCACHEDB', true );
			( defined( 'DONOTCACHEPAGE' ) ) || define( 'DONOTCACHEPAGE', true );

		}

		/**
		 * Add stats into the db
		 *
		 * @param array $args
		 *
		 * @since  2.0.0
		 */
		public static function add_stats( array $args ) : void {

			global $wpdb;

			$date = date( 'Y-m-d 00:00:00', strtotime( $args['date'] ) );

			$stats['blocked'] = $blocked = ( isset( $args['status'] ) && $args['status'] == 'blocked' ) ? 1 : 0;
			$stats['threats'] = $threats = ( isset( $args['threats'] ) && $args['threats'] ) ? 1 : 0;

			$stats['404s']         = $e404s = ( $args['type'] == '404s' ) ? 1 : 0;
			$stats['404s_threats'] = $e404s_threats = ( $threats && $args['type'] == '404s' ) ? 1 : 0;

			$stats['logins']         = $logins = ( isset( $args['type'] ) && $args['type'] == 'logins' ) ? 1 : 0;
			$stats['logins_failed']  = $logins_failed = ( $logins && isset( $args['status'] ) && $args['status'] == 'failed' ) ? 1 : 0;
			$stats['logins_success'] = $logins_success = ( $logins && isset( $args['status'] ) && $args['status'] == 'success' ) ? 1 : 0;
			$stats['logins_threats'] = $logins_threats = ( $logins && $threats ) ? 1 : 0;
			$stats['logins_blocked'] = $logins_blocked = ( $logins && $blocked ) ? 1 : 0;

			// Get the current day's stats
			$table = Yoda::get_table_stats();

			// Update
			$query = "
            UPDATE $table 
            SET 404s = 404s + $e404s,
            404s_threats = 404s_threats + $e404s_threats,
            blocked = blocked + $blocked,
            threats = threats + $threats,
            logins = logins + $logins,
            logins_failed = logins_failed + $logins_failed,
            logins_success = logins_success + $logins_success,
            logins_threats = logins_threats + $logins_threats,
            logins_blocked = logins_blocked + $logins_blocked
        ";

			$query_date = $query . " WHERE date like '$date'";

			$exist = $wpdb->query( $query_date );

			if ( ! $exist ) {

				$stats['date'] = $date;

				// Add
				$result = $wpdb->insert( Yoda::get_table_stats(), $stats );

				if ( ! $result ) {

					// Create Stats Table
					Janitor::create_table_stats();

					// Try Again now that a table exist
					$wpdb->insert( Yoda::get_table_stats(), $stats );

				}

			}

		}

		/**
		 * Logs activity into database
		 *
		 * @param array $args
		 *
		 * @since 2.1.1
		 */
		public static function log_activity( array $args ) : void {

			$user = wp_get_current_user();

			// Log Actual Activity
			$args               = ( is_array( $args ) ) ? $args : [];
			$args['type']       = 'activity';
			$args['threats']    = '0';
			$args['score']      = '0';
			$args['user_agent'] = Yoda::get_user_agent();
			$args['username']   = ( isset( $user->user_login ) ) ? $user->user_login : 'unknown';
			$args['ip']         = Yoda::get_ip();
			$args['status']     = ( defined( 'DOING_CRON' ) ) ? 'automatic' : 'unknown';
			$args['status']     = ( $args['status'] == 'unknown' && isset( $user->user_login ) ) ? 'manual' : $args['status'];

			Janitor::add_entry( $args );

		}

		/**
		 * Removes the settings from the database on plugin deactivation
		 *
		 * @since  0.3.5
		 */
		public static function disable_plugin() : void {

			// Remove Cron
			wp_clear_scheduled_hook( 'secsafe_cleanup_tables_daily' );

			$site_settings = get_option( SECSAFE_OPTIONS );

			// Check to see if the user wants us to cleanup the data
			if ( isset( $site_settings['general']['cleanup'] ) && $site_settings['general']['cleanup'] == '1' ) {

				// Delete Settings
				delete_option( SECSAFE_OPTIONS );

				// Delete Tables
				Janitor::drop_table( SECSAFE_DB_FIREWALL );
				Janitor::drop_table( SECSAFE_DB_STATS );

			} else {

				// Log Activity
				$args            = [];
				$args['details'] = sprintf( __( '%s plugin disabled.', SECSAFE_TRANSLATE ), SECSAFE_NAME );
				Janitor::log_activity( $args );

			}

		}

		/**
		 * Drop table in the database
		 *
		 * @param string $table
		 *
		 * @return bool
		 *
		 * @since  2.0.0
		 */
		private static function drop_table( string $table ) : bool {

			global $wpdb;

			$dropped = false;

			if ( in_array( $table, [ SECSAFE_DB_FIREWALL, SECSAFE_DB_STATS ] ) ) {

				$table = $wpdb->prefix . $table;

				$dropped = $wpdb->query( "DROP TABLE IF EXISTS $table" );

			}

			return $dropped;

		}

		/**
		 * This cleans up the database when the daily cron runs
		 *
		 * @since 2.0.0
		 */
		public static function cleanup_tables() : void {

			Janitor::cleanup_type( '404s' );
			Janitor::cleanup_type( 'logins' );
			Janitor::cleanup_type( 'activity' );
			Janitor::expire_type( 'allow_deny' );

		}

		/**
		 * Deletes all rows in excess of limit for a specific type
		 *
		 * @param string $type
		 *
		 * @since 2.0.0
		 */
		private static function cleanup_type( string $type ) : void {

			global $wpdb;

			$types = Yoda::get_types();

			// Require Valid Type
			if ( isset( $types[ $type ] ) ) {

				$args = [];

				$limit = Yoda::get_display_limits( $type, true );

				$table_main = Yoda::get_table_main();

				$query = "SELECT COUNT(type) FROM $table_main WHERE type = '$type'";

				// Count how many exist
				$exists = (int) $wpdb->get_var( $query );

				$args['details'] = '[';

				// If more than limit
				if ( $exists > $limit ) {

					// Calculate amount to delete
					$delete = $exists - $limit;

					$query = "DELETE FROM $table_main WHERE type = '$type' ORDER BY date ASC LIMIT $delete";

					$result = $wpdb->query( $query );

					$args['details'] .= (int) $result . '-' . $exists . '-' . $limit;

				} else {

					$args['details'] = '0-' . $exists . '-' . $limit;

				}

				$args['details'] .= '] ' . $type . ' ' . __( 'database maintenance', SECSAFE_TRANSLATE ) . '.';

				// Log Activity
				Janitor::log_activity( $args );

			}

		}

		/**
		 * Deletes all rows for a specific type that have an expire date that is older than now.
		 *
		 * @param string $type
		 *
		 * @since 2.0.0
		 */
		private static function expire_type( string $type ) : void {

			global $wpdb;

			$args = [];

			// Cleanup Valid Types
			$types = Yoda::get_types();

			if ( isset( $types[ $type ] ) ) {

				$table_main = Yoda::get_table_main();

				$ago = date( 'Y-m-d H:i:s', strtotime( '-3 days' ) );

				$query = "DELETE FROM $table_main WHERE type = '$type' AND ( status = 'allow' OR status = 'deny') AND date_expire < '$ago' AND date_expire != '0000-00-00 00:00:00'";

				$result = $wpdb->query( $query );

				$args['details'] = '[' . (int) $result . '] ' . $type . ' ' . __( 'database maintenance', SECSAFE_TRANSLATE ) . '.';

			} else {

				$args['details'] = sprintf( __( 'Error: %s is not a valid type.', SECSAFE_TRANSLATE ), $type );

			}

			// Log Activity
			Janitor::log_activity( $args );

		}

	}
