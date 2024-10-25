<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	// Run Policy
	PolicyAnonymousWebsite::init();

	/**
	 * Class PolicyAnonymousWebsite
	 * @package SecuritySafe
	 * @since 1.1.0
	 */
	class PolicyAnonymousWebsite {

		/**
		 * Register hooks
		 *
		 * @return void
		 */
		public static function init() : void {

			add_filter( 'http_headers_useragent', [ self::class, 'make_anonymous' ] );

		}

		/**
		 * Make Website Anonymous When Updates Are Performed
		 *
		 * @return string
		 */
		public static function make_anonymous() : string {

			global $wp_version;

			return 'WordPress/' . $wp_version . '; URL protected by ' . SECSAFE_NAME . '. More info at: ' . SECSAFE_URL_MORE_INFO;

		}

	}
