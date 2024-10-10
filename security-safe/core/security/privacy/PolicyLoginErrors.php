<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	/**
	 * Class PolicyLoginErrors
	 * @package SecuritySafe
	 */
	class PolicyLoginErrors {

		/**
		 * PolicyLoginErrors constructor.
		 */
		function __construct() {

			add_filter( 'authenticate', [ $this, 'login_errors', ], 99999, 1 );

		}

		/**
		 * Makes the error message generic.
		 *
		 * @param object|null $user
		 *
		 * @return object|null
		 */
		function login_errors( $user ) {

			// Only affect core error messages
			if ( ! Yoda::is_login_error() && ! empty( $user->errors ) ) {

				$error_messages = [
					'invalid_username'   => 1,
					'incorrect_password' => 1,
					'invalid_email'      => 1,
				];

				foreach ( $user->errors as $key => $val ) {

					if ( isset( $error_messages[ $key ] ) ) {

						$user->errors[ $key ][0] = __( 'Invalid username or password.', SECSAFE_TRANSLATE );
						break;

					}

				}

			}

			return $user;

		}

	}
