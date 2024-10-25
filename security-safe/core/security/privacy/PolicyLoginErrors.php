<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	// Run Policy
	PolicyLoginErrors::init();

	/**
	 * Class PolicyLoginErrors
	 * @package SecuritySafe
	 */
	class PolicyLoginErrors {

		/**
		 * Register hooks
		 *
		 * @return void
		 */
		public static function init() : void {

			add_filter( 'authenticate', [ self::class, 'login_errors', ], 999999, 1 );

		}

		/**
		 * Makes the error message generic.
		 *
		 * @param object|null $user
		 *
		 * @return object|null
		 */
		public static function login_errors( $user ) {

			if ( is_wp_error( $user ) ) {

				// Only affect core error messages
				if ( ! Yoda::is_login_error() && ! empty( $user->errors ) ) {

					$error_messages = [
						'invalid_email' => 1,
						'incorrect_password' => 1,
						'invalid_email' => 1,
					];

					foreach ( $user->errors as $key => $val ) {

						if ( isset( $error_messages[ $key ] ) ) {

							$user->errors[ $key ][0] = __( 'Invalid username or password.', SECSAFE_TRANSLATE );
							break;

						}

					}

				}

			}

			return $user;

		}

	}
