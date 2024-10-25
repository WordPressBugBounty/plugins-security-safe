<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	// Run Policy
	PolicyLoginRememberMe::init();

	/**
	 * Class PolicyLoginRememberMe
	 * @package SecuritySafe
	 */
	class PolicyLoginRememberMe {

		/**
		 * Register hooks
		 *
		 * @return void
		 */
		public static function init() : void {

			// Clear Cache Attempt
			add_action( 'login_form', [ self::class, 'login_form' ], 99, 0 );

			// Clear Variable Attempt
			add_action( 'login_head', [ self::class, 'reset' ], 99, 0 );

		}

		/**
		 * Unsets the GET variable rememberme
		 */
		public static function reset() : void {

			// Remove the rememberme post value
			if ( isset( $_POST['rememberme'] ) ) {

				unset( $_POST['rememberme'] );

			}

		}

		/**
		 * Filters the html before it reaches the browser.
		 */
		public static function login_form() : void {

			ob_start( [ self::class, 'remove' ] );

		}

		/**
		 * Removes the content from html
		 *
		 * @param string $html
		 *
		 * @return string
		 */
		public static function remove( string $html ) : string {

			return preg_replace( '/<p class="forgetmenot">(.*)<\/p>/', '', $html );

		}


	}
