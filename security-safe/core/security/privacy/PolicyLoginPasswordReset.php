<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	/**
	 * Class PolicyLoginPasswordReset
	 * @package SecuritySafe
	 */
	class PolicyLoginPasswordReset {

		/**
		 * PolicyLoginPasswordReset constructor.
		 */
		function __construct() {

			// Disable Password Reset Form
			add_filter( 'allow_password_reset', '__return_false' );

			// Replace Link Text With Null
			add_filter( 'gettext', [ $this, 'remove' ] );

		}

		/**
		 * Replaces reset password text with nothing
		 *
		 * @param string $text Text to translate.
		 *
		 * @return string
		 *
		 * @link https://developer.wordpress.org/reference/hooks/gettext/
		 */
		public function remove( $text ) {

			return str_replace( [ 'Lost your password?', 'Lost your password' ], '', trim( $text, '?' ) );

		}

	}
