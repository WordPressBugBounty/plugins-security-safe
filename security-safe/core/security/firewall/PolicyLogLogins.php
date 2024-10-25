<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	use WP_Error;

	// Run Policy
	PolicyLogLogins::init();

	/**
	 * Class PolicyLogLogins
	 * @package SecuritySafe
	 * @since  2.0.0
	 */
	class PolicyLogLogins {

		/**
		 * Register hooks
		 *
		 * @return void
		 */
		public static function init() : void {

			add_filter( 'authenticate', [ self::class, 'blacklist_check' ], 0, 3 );
			add_action( 'wp_login_failed', [ self::class, 'failed' ], 99999, 1 );
			add_action( 'wp_login', [ self::class, 'success' ], 10, 2 );

		}

		/**
		 * Logs a Failed Login Attempt
		 *
		 * @param string $username
		 *
		 * @since  2.0.0
		 */
		public static function failed( string $username ) : void {

			if ( ! Yoda::is_login_error() ) {

				self::record( $username, 'failed' );

			}

		}

		/**
		 * Logs the login attempt.
		 *
		 * @param string $username
		 * @param string $status
		 *
		 * @since  2.0.0
		 */
		private static function record( $username, $status ) : void {

			global $SecuritySafe;

			$args             = []; // reset
			$args['type']     = 'logins';
			$args['username'] = ( $username ) ? sanitize_user( $username ) : '';
			$args['status']   = ( $status == 'success' ) ? 'success' : 'failed';
			$args['score']    = 0;

			if ( ! $SecuritySafe->is_whitelisted() ) {

				if ( defined( 'XMLRPC_REQUEST' ) ) {

					$args['threats'] = 1;
					$args['score']   = 0;
					$args['details'] = ( $args['status'] == 'failed' ) ? __( 'XML-RPC Login Attempt.', SECSAFE_TRANSLATE ) : __( 'XML-RPC Login Successful.', SECSAFE_TRANSLATE );

				}

				// Check Status
				$args['score'] += ( $args['status'] == 'failed' ) ? 1 : 0;

				// Check usernames
				$username_threat = Threats::is_username( $username );

				if ( $args['status'] == 'success' && $username_threat ) {

					$args['details'] = __( 'This username is too common. Consider changing it.', SECSAFE_TRANSLATE );

				}

				$args['score'] += ( $username_threat ) ? 1 : 0;
				$args['threats'] = ( $args['score'] > 0 ) ? 1 : 0;

			}

			//Janitor::log( $args['status'] . ' - record() =======' );

			// Log Login Attempt
			Janitor::add_entry( $args );

		}

		/**
		 * Logs a successful login
		 *
		 * @since  2.0.0
		 *
		 * @param string   $username
		 * @param \WP_User $user
		 *
		 * @return void
		 */
		public static function success( string $username, \WP_User $user ) : void {

			self::record( $username, 'success' );

		}

		/**
		 * Checks if IP has been blacklisted and if so, prevents the login attempt.
		 *
		 * @param null|WP_User|WP_Error $user
		 * @param string $username
		 * @param string $password
		 *
		 * @uses  $this->block
		 *
		 * @return null|WP_User|WP_Error
		 *
		 * @since 2.0.0
		 */
		public static function blacklist_check( $user, string $username, string $password ) {

			global $SecuritySafe;

			//Janitor::log( 'running login blacklist_check()' );

			// Reset error status in case multiple login attempts are made during a single session
			$SecuritySafe->login_error = false; // Reset login errors

			if( defined('SECSAFE_BLACKLIST_CHECK') ) {

				// This is a multiple attempt to login on the same request

				// Update blacklist status in case multiple login attempts are made during a single session
				$SecuritySafe->blacklisted = ( Firewall::is_blacklisted() ) ? true : false;

				if ( ! $SecuritySafe->is_blacklisted() ) {

					// Run Rate Limiting to see if use gets blacklisted
					Firewall::rate_limit();

				}
			} else {
				// This flag lets us know that this method has ran once already
				define( 'SECSAFE_BLACKLIST_CHECK', true );
			}

			// Final check of blacklisting to block login
			if ( $SecuritySafe->is_blacklisted() ) {

				//Janitor::log( 'login blacklisted!!!!' );

				$args             = [];
				$args['type']     = 'logins';
				$args['details']  = __( 'IP is blacklisted.', SECSAFE_TRANSLATE ) . '[' . __LINE__ . ']';
				$args['username'] = ( $username ) ? sanitize_user( $username ) : '';

				// Block the attempt
				Firewall::block( $args, false );

				// Prevent default generic message
				$SecuritySafe->login_error = true;

				if ( isset( $SecuritySafe->date_expires ) && $SecuritySafe->date_expires ) {

					$secs = strtotime( $SecuritySafe->date_expires ) - time();
					$days = $secs / 86400;
					$hrs  = $secs / 3600;
					$mins = $secs / 60;

					if ( $days >= 1 ) {

						$wait = ( $days > 1 ) ? sprintf( __( '%d days', SECSAFE_TRANSLATE ), $days ) : __( '1 day', SECSAFE_TRANSLATE );

					} elseif ( $hrs >= 1 ) {

						$wait = ( $hrs > 1 ) ? sprintf( __( '%d hours', SECSAFE_TRANSLATE ), $hrs ) : __( '1 hour', SECSAFE_TRANSLATE );

					} else {

						$wait = ( $mins > 1 ) ? sprintf( __( '%d minutes', SECSAFE_TRANSLATE ), $mins ) : __( '1 minute', SECSAFE_TRANSLATE );

					}

					$user = new WP_Error();
					$user->add( 'wp_security_safe_lockout', sprintf( __( '<b>ERROR:</b> Too many failed login attempts. Please try again in %s.', SECSAFE_TRANSLATE ), $wait ) );

				} else {

					$user = new WP_Error();
					$user->add( 'wp_security_safe_lockout', __( '<b>ERROR:</b> Too many failed login attempts. Please try again later.', SECSAFE_TRANSLATE ) );

				}

				// Stop core from attempting to login
				Security::stop_authenticate_process();

			}

			return $user;

		}

	}
