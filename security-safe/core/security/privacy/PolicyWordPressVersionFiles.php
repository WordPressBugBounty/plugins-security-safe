<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	use WP_Upgrader;

	// Run Policy
	PolicyWordPressVersionFiles::init();

	/**
	 * Class PolicyWordPressVersionFiles
	 * @package SecuritySafe
	 * @since 1.1.4
	 */
	class PolicyWordPressVersionFiles {

		/**
		 * Register hooks
		 *
		 * @return void
		 */
		public static function init() : void {

			add_action( 'upgrader_process_complete', [ self::class, 'protect_files' ], 10, 2 );

		}

		/**
		 * Changes the permissions of each file so that the world cannot read them.
		 *
		 * @link   https://developer.wordpress.org/reference/hooks/upgrader_process_complete/
		 *
		 * @param  WP_Upgrader $upgrader_object  WP_Upgrader instance. In other contexts, $this, might be a Theme_Upgrader, Plugin_Upgrader, Core_Upgrade, or Language_Pack_Upgrader instance.
		 * @param  array $options Array of bulk item update data.
		 *
		 * @uses set_permissions() to change the permissions of files.
		 *
		 * @since 1.1.4
		 */
		public static function protect_files( WP_Upgrader $upgrader_object, array $options ) : void {

			if ( $options['action'] == 'update' && $options['type'] == 'core' ) {

				$files = [
					ABSPATH . 'readme.html',
					ABSPATH . 'license.txt',
				];

				foreach ( $files as $file ) {

					$result = self::set_permissions( $file );

					if ( $result ) {

						// Display Success Status
						echo '<li>' . __( 'Fixed:', SECSAFE_TRANSLATE ) . ' ' . $file . '</li>';

					} else {

						// Display Failed Status
						echo '<li>' . __( 'Could Not Fix File:', SECSAFE_TRANSLATE ) . ' ' . $file . '</li>';

					}

				}

			}

		}

		/**
		 * Set Permissions For File or Directory
		 *
		 * @param string $path Absolute path to file or directory
		 *
		 * @return bool
		 */
		private static function set_permissions( string $path ) : bool {

			// Cleanup Path
			$path = str_replace( [ '/./', '////', '///', '//' ], '/', $path );

			return ( file_exists( $path ) ) ? chmod( $path, 0640 ) : false;

		}

	}
