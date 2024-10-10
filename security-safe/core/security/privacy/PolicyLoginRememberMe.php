<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	/**
	 * Class PolicyLoginRememberMe
	 * @package SecuritySafe
	 */
	class PolicyLoginRememberMe {

		/**
		 * PolicyLoginRememberMe constructor.
		 */
		function __construct() {

			// Clear Cache Attempt
			add_action( 'login_form', [ $this, 'login_form' ], 99 );

			// Clear Variable Attempt
			add_action( 'login_head', [ $this, 'reset' ], 99 );

		}

		/**
		 * Unsets the GET variable rememberme
		 */
		public function reset() {

			// Remove the rememberme post value
			if ( isset( $_POST['rememberme'] ) ) {

				unset( $_POST['rememberme'] );

			}

		}

		/**
		 * Filters the html before it reaches the browser.
		 */
		public function login_form() {

			ob_start( [ $this, 'remove' ] );

		}

		/**
		 * Removes the content from html
		 *
		 * @param string $html
		 *
		 * @return string
		 */
		public function remove( $html ) {

			return preg_replace( '/<p class="forgetmenot">(.*)<\/p>/', '', $html );

		}


	}
