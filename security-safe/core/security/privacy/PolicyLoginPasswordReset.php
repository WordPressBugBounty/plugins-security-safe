<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	// Run Policy
	PolicyLoginPasswordReset::init();

	/**
	 * Class PolicyLoginPasswordReset
	 * @package SecuritySafe
	 */
	class PolicyLoginPasswordReset {

		/**
		 * Register hooks
		 *
		 * @return void
		 */
		public static function init() : void {

			// Disable Password Reset Form
			add_filter( 'allow_password_reset', '__return_false' );

			// Replace Link Text With Null
			add_filter( 'gettext', [ self::class, 'remove' ] );

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
		public static function remove( string $text ) : string {

			return str_replace( [ 'Lost your password?', 'Lost your password' ], '', trim( $text, '?' ) );

		}

	}
