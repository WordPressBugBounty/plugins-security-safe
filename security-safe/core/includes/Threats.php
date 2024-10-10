<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	/**
	 * Class Threats.
	 *
	 * @package SecuritySafe
	 * @since 2.0.0
	 */
	class Threats {

		/**
		 * Determines if the username is a threat
		 *
		 * @param string $username
		 *
		 * @return int
		 *
		 * @since  2.0.0
		 */
		public static function is_username( string $username ) : int {

			$usernames = Yoda::get_bad_usernames();

			return in_array( strtolower( $username ), $usernames ) ? 1 : 0;

		}

		/**
		 * Determines if the filename is a threat
		 *
		 * @param string $filename
		 *
		 * @return int
		 *
		 * @since  2.0.0
		 */
		public static function is_filename( string $filename ) : int {

			$matches_name = [
				'wp-config',
				'readme',
				'webconfig',
				'cgi-bin',
				'.git',
			];

			$threat = false;

			// Check for filename matches
			foreach ( $matches_name as $key => $name ) {

				$threat = ( strpos( $filename, $name ) !== false ) ? true : $threat;

				if ( $threat ) {
					break;
				}

			}

			return ( $threat ) ? 1 : 0;

		}

		/**
		 * Determines if the filename extention is a threat
		 *
		 * @param string $filename
		 *
		 * @return int
		 *
		 * @since  2.4.0
		 */
		public static function is_file_extention( string $filename ) : int {

			// Check File Extentions
			$length  = strlen( $filename );
			$ext_len = [ 4, 5, 7, 3 ]; // ordered in popularity

			$matches_ext = [
				'.zip'    => '', // 4
				'.bzip'   => '', // 5
				'.tar'    => '', // 4
				'.tar.gz' => '', // 7
				'.gz'     => '', // 3
				'.bak'    => '' // 4
			];

			$threat = false;

			foreach ( $ext_len as $l ) {

				if ( $length >= $l ) {

					$ext = substr( $filename, - $l );

					$threat = ( isset( $matches_ext[ $ext ] ) ) ? true : $threat;

					if ( $threat ) {
						break;
					}

				}

			}

			return ( $threat ) ? 1 : 0;

		}

		/**
		 * Determines if the filename is a threat
		 *
		 * @param string $uri
		 *
		 * @return int
		 *
		 * @since  2.0.0
		 */
		public static function is_uri( string $uri ) : int {

			/**
			 * @todo finish detect uri threats
			 */

			return 0;

		}

	}
