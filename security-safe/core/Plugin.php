<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	/**
	 * Class Plugin - Main class for plugin
	 *
	 * @package SecuritySafe
	 */
	class Plugin {

		/**
		 * User
		 * @var array
		 */
		public array $user;

		/**
		 * Contains all the admin message values.
		 * @var array
		 */
		public array $messages = [];

		/**
		 * local settings values array.
		 * @var array
		 */
		protected array $settings = [];

		public bool $logged_in = false;

		/**
		 * Contains all the sites involved in this WordPress install
		 * @var array
		 */
		public array $sites = [];

		/**
		 * Plugin constructor.
		 *
		 * @param array $session
		 *
		 * @since  0.1.0
		 */
		public function __construct( array $session ) {

			// Sets Session Variables
			$this->set_session( $session );

			// Add Text Domain For Translations
			load_plugin_textdomain( SECSAFE_TRANSLATE, false, SECSAFE_DIR_LANG );

			// Check For Upgrades
			$this->upgrade_settings();

			add_action( 'login_enqueue_scripts', [ $this, 'login_scripts' ] );
			add_filter( 'login_body_class', [ $this, 'login_body_class' ] );
			add_action( 'login_footer', [ $this, 'login_footer' ] );

		}

		/**
		 * Sets variables related to this session.
		 *
		 * @param array $session
		 *
		 * @since  2.1.0
		 */
		private function set_session( array $session ) : void {

			$this->logged_in = ( isset( $session['logged_in'] ) ) ? $session['logged_in'] : false;
			$this->user = ( !empty($session['user']) && is_array($session['user']) ) ? $session['user'] : [];

		}

		/**
		 * Used to retrieve settings from the database.
		 *
		 * @since 0.1.0
		 *
		 * @param bool $from_db
		 * @return array
		 */
		public function get_settings(bool $from_db = false ) : array {

			//Janitor::log( 'running get_settings().' );

			$current_site_id = get_current_blog_id();

			if ( $from_db || ! isset( $SecuritySafe->settings[ $current_site_id ] ) ) {

				$site_settings = get_option( SECSAFE_OPTIONS );

				if ( false == $site_settings || empty( $site_settings ) ) {
					// Settings are missing, let's fix that
					$this->reset_settings( true );

					$site_settings = get_option( SECSAFE_OPTIONS );
				}
				// Set the default settings
				$this->settings[ $current_site_id ] = Plugin::get_settings_min();

				// Override the default settings with the current settings
				foreach ( $site_settings as $key => $value ) {
					foreach ( $value as $k => $v ) {
						$this->settings[ $current_site_id ][ $key ][ $k ] = $v;
					}
				}

			}

			return $this->settings[ $current_site_id ];

		}

		/**
		 * Used to update settings in the database.
		 *
		 * @param array $site_settings
		 *
		 * @return  boolean
		 *
		 * @since 0.1.0
		 */
		public function set_settings( array $site_settings ): bool {

			//Janitor::log( 'running set_settings()' );

			// Default value
			$return = false;

			if ( is_array( $site_settings ) && isset( $site_settings['plugin']['version'] ) ) {

				// Clean settings against the template minimum settings
				$clean_site_settings = $this->clean_settings( $site_settings );

				// Update DB
				$results = update_option( SECSAFE_OPTIONS, $clean_site_settings );

				if ( $results ) {

					//Janitor::log( 'Settings have been updated.' );

					//Update Site Settings Cache
					$this->get_settings(true);

					$return = true;

				}

			}

			return $return;

		}

		/**
		 * Resets the plugin settings to default configuration.
		 *
		 * @param bool $initial Flag used to indicate the initial setup of settings.
		 *
		 * @since  0.2.0
		 */
		protected function reset_settings( bool $initial = false ) : void {

			//Janitor::log( 'running reset_settings()' );

			$current_site_id = get_current_blog_id();

			// Keep Plugin Version History
			$plugin_history = ( isset( $this->settings[ $current_site_id ]['plugin']['version_history'] ) && $this->settings[ $current_site_id ]['plugin']['version_history'] ) ? $this->settings[ $current_site_id ]['plugin']['version_history'] : [ SECSAFE_VERSION ];

			if ( ! $initial ) {

				$delete = $this->delete_settings();

				if ( ! $delete ) {

					$this->messages[] = [ __( 'Error: Settings could not be deleted [1].', SECSAFE_TRANSLATE ), 3, 0 ];

					return;

				}

			}

			// Get Minimum Settings
			$min_site_settings = Plugin::get_settings_min( $plugin_history );

			$result = $this->set_settings( $min_site_settings );

			if ( $result && $initial ) {

				$this->messages[] = [
					sprintf( __( '%s settings have been set to the minimum standards.', SECSAFE_TRANSLATE ), SECSAFE_NAME ),
					1,
					1,
				];

			} elseif ( $result && ! $initial ) {

				$this->messages[] = [ __( 'The settings have been reset to default.', SECSAFE_TRANSLATE ), 1, 1 ];

			} elseif ( ! $result ) {

				$this->messages[] = [ __( 'Error: Settings could not be reset. [2]', SECSAFE_TRANSLATE ), 3, 0 ];

			}

			//Janitor::log( 'Settings changed to default.' );

		}

		/**
		 * Used to remove settings in the database.
		 *
		 * @return bool
		 *
		 * @since 0.2.0
		 */
		protected function delete_settings() : bool {

			//Janitor::log( 'running delete_settings()' );

			// Delete settings
			return delete_option( SECSAFE_OPTIONS );

		}

		/**
		 * Retrieves the minimun standard settings. Also used as a template for importing settings.
		 *
		 * @param array $plugin_history History of plugin versions installed.
		 *
		 * @return array
		 *
		 * @since  1.2.0
		 */
		static function get_settings_min( array $plugin_history = [] ) : array {

			// Privacy ---------------------------------|
			$privacy = [
				'on'                      => '1',           // Toggle on/off all privacy policies.
				'wp_generator'            => '1',
				'wp_version_admin_footer' => '0',
				'hide_script_versions'    => '0',
				'http_headers_useragent'  => '1',
			];

			// Files -----------------------------------|
			$files = [
				'on'                            => '1',     // Toggle on/off all file policies.
				'allow_dev_auto_core_updates'   => '0',
				'allow_major_auto_core_updates' => '0',
				'allow_minor_auto_core_updates' => '1',
				'auto_update_plugin'            => '0',
				'auto_update_theme'             => '0',
				'DISALLOW_FILE_EDIT'            => '1',
				'version_files_core'            => '1',
				'version_files_plugins'         => '0',     // Pro
				'version_files_themes'          => '0',     // Pro
			];

			// Content ---------------------------------|
			$content = [
				'on'                            => '1',     // Toggle on/off all content policies.
				'disable_text_highlight'        => '0',
				'disable_right_click'           => '0',
				'hide_password_protected_posts' => '0',
			];

			// Access -----------------------['-----------|
			$access = [
				'on'                     => '1',            // Toggle on/off all access policies.
				'login_errors'           => '1',
				'login_password_reset'   => '0',
				'login_remember_me'      => '0',
				'block_usernames'        => '0',            // Enable / Disable
				'block_usernames_list'   => implode("\n", Yoda::get_bad_usernames() ),
				'autoblock'              => '1',            // Enable / Disable
				'autoblock_method'       => '1',            // # Failed Logins / # Threats ( Pro ) / Score ( Pro )
				'autoblock_threat_score' => '5',
				'autoblock_timespan'     => '5',
				'autoblock_ban_1'        => '10',           // Mins
				'autoblock_ban_2'        => '1',            // Hrs
				'autoblock_ban_3'        => '1',            // Days ( Pro )
				'xml_rpc'                => '0',
			];

			// Backups ---------------------------------|
			$backups = [
				'on' => '1', // Toggle on/off all backup features.
			];

			// General Settings ------------------------|
			$general = [
				'on'             => '1', // Toggle on/off all policies in the plugin.
				'security_level' => '1', // This is not used yet. Intended as preset security levels for faster configurations.
				'cleanup'        => '0', // Remove Settings When Disabled
				'cache_busting'  => '1', // Bust cache when removing versions from JS & CSS files
				'byline'         => '1', // Display byline below login form
			];

			// Plugin Version Tracking -----------------|
			$plugin = [
				'version'         => SECSAFE_VERSION,
				'version_history' => $plugin_history,
			];

			return [
				'privacy' => $privacy,
				'files'   => $files,
				'content' => $content,
				'access'  => $access,
				'backups' => $backups,
				'general' => $general,
				'plugin'  => $plugin,
			];

		}

		/**
		 * Upgrade settings from an older version
		 *
		 * @since  1.1.0
		 */
		protected function upgrade_settings() : void {

			//Janitor::log( 'Running upgrade_settings()' );

			$site_settings = $this->get_settings();
			$upgrade  = false;

			// Upgrade Versions
			if ( SECSAFE_VERSION != $site_settings['plugin']['version'] ) {

				//Janitor::log( 'Upgrading version. ' . SECSAFE_VERSION . ' != ' . $site_settings['plugin']['version'] );

				$upgrade = true;

				// Add old version to history
				$site_settings['plugin']['version_history'][] = $site_settings['plugin']['version'];
				$site_settings['plugin']['version_history']   = array_unique( $site_settings['plugin']['version_history'] );

				// Update DB To New Version
				$site_settings['plugin']['version'] = SECSAFE_VERSION;

				// Upgrade to version 1.1.0
				if ( isset( $site_settings['files']['auto_update_core'] ) ) {

					//Janitor::log( 'Upgrading updates for 1.1.0 upgrades.' );

					// Remove old setting
					unset( $site_settings['files']['auto_update_core'] );

					( isset( $site_settings['files']['allow_dev_auto_core_updates'] ) ) || $site_settings['files']['allow_dev_auto_core_updates'] = '0';
					( isset( $site_settings['files']['allow_major_auto_core_updates'] ) ) || $site_settings['files']['allow_major_auto_core_updates'] = '0';
					( isset( $site_settings['files']['allow_minor_auto_core_updates'] ) ) || $site_settings['files']['allow_minor_auto_core_updates'] = '1';

				}

				// Upgrade to version 2.3.0
				( isset( $site_settings['general']['byline'] ) ) || $site_settings['general']['byline'] = '1';

				// Upgrade to version 2.4.0
				if ( version_compare( end( $site_settings['plugin']['version_history'] ), '2.4.0', '<' ) ) {

					global $wpdb;

					$table_name = Yoda::get_table_main();

					$exist = $wpdb->query( "SELECT `score` FROM $table_name" );

					if ( ! $exist ) {

						$wpdb->query( "ALTER TABLE $table_name ADD COLUMN `score` TINYINT(3) NOT NULL default '0'" );

						$this->messages[] = [
							sprintf( __( '%s: Your database has been upgraded.', SECSAFE_TRANSLATE ), SECSAFE_NAME ),
							0,
							1,
						];

					}

				}

				// Upgrade Settings to version 2.4.0
				( isset( $site_settings['access']['autoblock_threat_score'] ) ) || $site_settings['access']['autoblock_threat_score'] = '10';
				( isset( $site_settings['access']['autoblock_timespan'] ) ) || $site_settings['access']['autoblock_timespan'] = '5';
				( isset( $site_settings['access']['autoblock_ban_1'] ) ) || $site_settings['access']['autoblock_ban_1'] = '10';
				( isset( $site_settings['access']['autoblock_ban_2'] ) ) || $site_settings['access']['autoblock_ban_2'] = '1';
				( isset( $site_settings['access']['autoblock_ban_3'] ) ) || $site_settings['access']['autoblock_ban_3'] = '1';

			}

			if ( $upgrade ) {

				$result = $this->set_settings( $site_settings ); // Update DB

				if ( $result ) {

					$this->messages[] = [
						sprintf( __( '%s: Your settings have been upgraded.', SECSAFE_TRANSLATE ), SECSAFE_NAME ),
						0,
						1,
					];
					//Janitor::log( 'Added upgrade success message.' );

				} else {

					$this->messages[] = [
						sprintf( __( '%s: There was an error upgrading your settings. We would recommend resetting your settings to fix the issue.', SECSAFE_TRANSLATE ), SECSAFE_NAME ),
						3,
					];
					//Janitor::log( 'Added upgrade error message.' );

				}

			}

		}

		/**
		 * Retrieves the default settings for a page
		 *
		 * @param string $page
		 *
		 * @return array
		 *
		 * @since 2.4.0
		 */
		static function get_page_settings_min( string $page = 'false' ) : array {

			$default_site_settings = Plugin::get_settings_min();

			// Force Lowercase
			$page = strtolower( $page );

			return ( isset( $default_site_settings[ $page ] ) ) ? $default_site_settings[ $page ] : [];

		}

		/**
		 * Initializes the plugin.
		 *
		 * @since  1.8.0
		 */
		static function init() : void {

			global $SecuritySafe;

			// Set Session
			$session = Plugin::get_session();

			$admin_user = false;

			if ( is_admin() ) {

				// Multisite Compatibility
				if ( is_multisite() ) {

					// Only Super Admin has the power
					$admin_user = ( isset( $session['user']['roles']['super_admin'] ) ) ? true : false;

				} else {

					$admin_user = ( isset( $session['user']['roles']['administrator'] ) || current_user_can( 'manage_options' ) ) ? true : false;

				}

			}

			if ( $admin_user ) {

				// Load Admin
				require_once( SECSAFE_DIR_ADMIN . '/Admin.php' );

				$SecuritySafe = new Admin( $session );

			} else {

				$SecuritySafe = new Security( $session );

			}

			// Start Security
			$SecuritySafe->start_security();

		}

		/**
		 * Gets variables related to this session.
		 *
		 * @return array
		 *
		 * @since  2.1.0
		 */
		private static function get_session() : array {

			$session = [];

			// Get user once
			$user = wp_get_current_user();

			$session['logged_in'] = $user->exists();
			$session['user'] = [];

			if ( $session['logged_in'] ) {

				$new_roles = array_combine( $user->roles, $user->roles );

				// Cache roles
				$session['user']['roles'] = $new_roles;

				// Make multi-site compatible
				if ( is_super_admin( $user->ID ) ) {

					$session['user']['roles']['super_admin'] = 'super_admin';

				}

			}

			return $session;

		}

		/**
		 * Clears Cached PHP Functions
		 *
		 * @since 1.1.13
		 */
		static function clear_php_cache() : void {

			if ( version_compare( PHP_VERSION, '5.5.0', '>=' ) ) {

				if ( function_exists( 'opcache_reset' ) ) {

					opcache_reset();
				}

			} else {

				if ( function_exists( 'apc_clear_cache' ) ) {

					apc_clear_cache();
				}

			}

		}

		/**
		 * Retrieves the settings for a specific page
		 *
		 * @note This method is used by Firewall.php and other classes outside of the main class.
		 *
		 * @param string $page
		 *
		 * @return array
		 *
		 * @since  2.4.0
		 */
		public function get_page_settings( string $page = 'false' ) : array {

			$site_settings = $this->get_settings();

			// Force Lowercase
			$page = strtolower( $page );

			return ( isset( $site_settings[ $page ] ) ) ? $site_settings[ $page ] : [];

		}

		/**
		 * Get cache_buster value from database
		 *
		 * @note: This is used by PolicyHideScriptVersions
		 *
		 * @return int
		 *
		 */
		public function get_cache_busting() : int {

			$site_settings = $this->get_settings();

			return $site_settings['general']['cache_busting'] ?? $this->increase_cache_busting();

		}

		/**
		 * Increase cache_busting value by 1
		 *
		 * @return int
		 */
		function increase_cache_busting() : int {

			//Janitor::log( 'Running increase_cache_busting().' );

			$site_settings = $this->get_settings();

			$cache_busting = ( isset( $site_settings['general']['cache_busting'] ) && $site_settings['general']['cache_busting'] > 0 ) ? (int) $site_settings['general']['cache_busting'] : 0;

			// Increase Value
			$site_settings['general']['cache_busting'] = ( $cache_busting > 99 ) ? 1 : $cache_busting + 1; //Increase value

			$result = $this->set_settings( $site_settings );

			return ( $result ) ? $site_settings['general']['cache_busting'] : 0;

		}

		/**
		 * Adds scripts to login
		 *
		 * @todo Add @since version
		 */
		public function login_scripts() : void {

			$cache_buster = ( SECSAFE_DEBUG ) ? SECSAFE_VERSION . date( 'YmdHis' ) : SECSAFE_VERSION;

			// Load CSS
			wp_register_style( SECSAFE_SLUG . '-login', SECSAFE_URL_ADMIN_ASSETS . 'css/admin.css', [], $cache_buster, 'all' );
			wp_enqueue_style( SECSAFE_SLUG . '-login' );

		}

		/**
		 * Adds a class to the body tag
		 *
		 * @param array $classes
		 *
		 * @return array
		 */
		public function login_body_class( $classes ) : array {

			$classes[] = SECSAFE_SLUG;

			return $classes;

		}

		/**
		 * Display byline
		 *
		 * @todo Add @since version
		 */
		public function login_footer() : void {

			$site_settings = $this->get_settings();

			if ( $site_settings['general']['byline'] ) {

				echo '<p style="text-align:center;margin-bottom:21px"><a href="https://wordpress.org/plugins/security-safe/" target="_balnk" class="icon-lock">' . sprintf( __( 'Security by %s', SECSAFE_TRANSLATE ), SECSAFE_NAME ) . '</a></p>';

			}

		}

		/**
		 * Upgrade settings from an older version
		 *
		 * @param array $dirty_settings Unsanitized settings.
		 *
		 * @return array
		 *
		 * @since  1.2.2
		 */
		protected function clean_settings( array $dirty_settings ) : array {

			// Keep Plugin Version History
			$plugin_history = ( isset( $dirty_settings['plugin']['version_history'][0] ) ) ? $dirty_settings['plugin']['version_history'] : [ SECSAFE_VERSION ];

			// Get template for settings
			$min_site_settings = Plugin::get_settings_min( $plugin_history );

			// Filtered Settings
			$filtered_settings = [];

			// Filter all non settings values
			foreach ( $min_site_settings as $key => $value ) {

				foreach ( $value as $k => $v ) {

					if ( isset( $dirty_settings[ $key ][ $k ] ) ) {

						if( 'block_usernames_list' === $k ) {
							// Cleanup blocked username list

							if ( is_array( $dirty_settings[ $key ][ $k ] ) ) {
								// Convert array into a string so we can modify the string
								$dirty_settings[ $key ][ $k ] = implode( "\n", $dirty_settings[ $key ][ $k ] );
							}

							// Ensure that usernames separated by a space are converted to new lines
							$dirty_settings[ $key ][ $k ] = str_replace( " ", "\n", trim( $dirty_settings[ $key ][ $k ] ));

							// Convert the value into an array with new line delimiter
							$dirty_settings[ $key ][ $k ] = explode( "\n", $dirty_settings[ $key ][ $k ] );

							// get rid of extra spaces so that we can do a direct comparison
							$dirty_settings[ $key ][ $k ] = array_map( 'trim', $dirty_settings[ $key ][ $k ] );

							// Usernames are case-insensitive
							$dirty_settings[ $key ][ $k ] = array_map( 'strtolower', $dirty_settings[ $key ][ $k ] );

							// Get rid of duplicates
							$dirty_settings[ $key ][ $k ] = array_unique( $dirty_settings[ $key ][ $k ] );

							// Convert back into a string
							$dirty_settings[ $key ][ $k ] = implode( "\n", $dirty_settings[ $key ][ $k ] );

						}

						$filtered_settings[ $key ][ $k ] = $dirty_settings[ $key ][ $k ];
					} else {
						$filtered_settings[ $key ][ $k ] = '';
					}

				}

			}

			return $filtered_settings;

		}

		/**
		 * Set settings for a particular settings page
		 *
		 * @param string $settings_page The page posted to
		 *
		 * @since  0.1.0
		 */
		protected function post_settings( string $settings_page ) : void {

			//Janitor::log( 'Running post_settings().' );

			$settings_page = strtolower( $settings_page );

			if ( isset( $_POST ) && ! empty( $_POST ) && $settings_page ) {

				// Security Check
				if ( ! wp_verify_nonce( REQUEST::text_field('_nonce_save_settings'), SECSAFE_SLUG . '-save-settings' ) ) {

					$this->messages[] = [
						__( 'Error: Settings not saved. Your session expired. Please try again.', SECSAFE_TRANSLATE ),
						3,
					];

					return; // Bail

				}

				//Janitor::log( 'Form was submitted.' );

				//This is sanitized in clean_settings()
				$page_settings_new = REQUEST::POST();

				// Remove unnecessary values
				unset( $page_settings_new['submit'] );

				// Get settings
				$site_settings = $this->get_settings(); // Get copy of settings
				$site_settings_old = $site_settings;
				$site_settings_min = Plugin::get_settings_min(); // Default settings
				$options = $site_settings_min[ $settings_page ]; // Get page specific settings

				// Set Settings Array With New Values
				foreach ( $options as $label => $value ) {

					if ( isset( $page_settings_new[ $label ] ) ) {

						if ( $options[ $label ] != $page_settings_new[ $label ] ) {
							// Set Value
							//echo "set " . $label . "<br>";
							$options[ $label ] = $page_settings_new[ $label ];
						}

						unset( $page_settings_new[ $label ] );

					} else {

						// Turn Boolean values off (checkboxes)
						if ( $options[ $label ] == '1' ) {

							$options[ $label ] = '0';

						}

					}

				}

				// Add New Settings
				if ( ! empty( $page_settings_new ) ) {

					foreach ( $page_settings_new as $label => $value ) {

						// Ignore all inputs that start with an _ (underscore)
						if ( substr( $label, 0, 1 ) != '_' ) {
							$options[ $label ] = $page_settings_new[ $label ];
						}

					}

				}

				if ( $settings_page == 'access' ) {

					// Check to make sure that timespan is not greater than first lockout
					if ( $options['autoblock_ban_1'] < $options['autoblock_timespan'] ) {

						$options['autoblock_timespan'] = 5;
						$this->messages[]              = [
							sprintf( __( 'Warning: The lockout condition minutes cannot be greater than the first lockout minutes. The lockout condition was changed to %d minutes.', SECSAFE_TRANSLATE ), $options['autoblock_ban_1'] ),
							2,
						];

					}

					if ( $options['block_usernames'] ) {

						global $wpdb;

						// Blocked Usernames Policy
						require_once( SECSAFE_DIR_FIREWALL . '/PolicyBlockUsernames.php' );

						if ( is_multisite() ) {
							switch_to_blog( get_main_site_id() );
						}

						$count_usernames = $wpdb->get_col( 'SELECT count(`user_login`) FROM `' . $wpdb->prefix . 'users` WHERE 1=1;' );
						$count_usernames = $count_usernames[0] ?? 0;
						$count_usernames = (int) $count_usernames;
						$found_usernames = [];
						$start = 0;
						$end = 1000;

						$block_usernames_list = PolicyBlockUsernames::get_blocked_usernames_list( $options['block_usernames_list'] );

						while ( $start <= $count_usernames ) {

							$all_usernames = $wpdb->get_col( $wpdb->prepare( 'SELECT `user_login` FROM `' . $wpdb->prefix . 'users` WHERE 1=1 ORDER BY `user_login` ASC LIMIT %d, %d;', $start, $end ) );

							// Increment the limit (grab 1k users at a time)
							$start = $end + 1;
							$end += 1000;

							// Add to the list of found usernames
							$found_usernames = array_merge( PolicyBlockUsernames::in_block_list( $all_usernames, false, $block_usernames_list ), $found_usernames );
						}

						if ( ! empty( $found_usernames ) ) {
							$message = sprintf( __( 'Warning: The username block list contains users that actually exist, which will prevent those users from logging in. We recommend removing the following accounts from the block list or removing their accounts: %s', SECSAFE_TRANSLATE ), implode( ', ', $found_usernames ) );

							if ( is_super_admin() ) {
								if ( is_multisite() ) {
									$message .= ' (<a href="' . network_admin_url( 'users.php' ) . '">' . __( 'View Network Users', SECSAFE_TRANSLATE ) . '</a>)';
								} else {
									$message .= ' (<a href="' . admin_url( 'users.php' ) . '">' . __( 'View Users', SECSAFE_TRANSLATE ) . '</a>)';
								}
							}

							$this->messages[] = [
								$message,
								2,
							];
						}

						if ( is_multisite() ) {
							restore_current_blog();
						}
					}

				}

				// Update page settings
				$site_settings[ $settings_page ] = $options;

				// Compare New / Old Settings to see if anything actually changed
				if ( $site_settings == $site_settings_old ) {

					// Tell user that they were updated, but nothing actually changed
					$this->messages[] = [ __( 'Settings saved.', SECSAFE_TRANSLATE ), 0, 1 ];

				} else {

					// Actually Update Settings
					$success = $this->set_settings( $site_settings ); // Update DB

					if ( $success ) {

						$this->messages[] = [ __( 'Your settings have been saved.', SECSAFE_TRANSLATE ), 0, 1 ];
						//Janitor::log( 'Added success message.' );

					} else {

						$this->messages[] = [ __( 'Error: Settings not saved.', SECSAFE_TRANSLATE ), 3 ];
						//Janitor::log( 'Added error message.' );

					}

				}

			} else {

				//Janitor::log( 'Form NOT submitted.' );

			}

			//Janitor::log( 'Finished post_settings() for ' . $settings_page );

		}

	}
