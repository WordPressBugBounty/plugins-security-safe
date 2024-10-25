<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	// Run Policy
	PolicyHideWPVersion::init();

	/**
	 * Class PolicyHideWPVersion
	 * @package SecuritySafe
	 * @since 1.1.3
	 */
	class PolicyHideWPVersion {

		/**
		 * Register hooks
		 *
		 * @return void
		 */
		public static function init() : void {

			// Remove Version From RSS
			add_filter( 'the_generator', [ self::class, 'rss_version' ], 10, 0 );

			// Remove Generator Tag in HTML
			remove_action( 'wp_head', 'wp_generator' );

		}

		/**
		 * Remove WordPress Version From RSS
		 *
		 * @return string
		 *
		 * @since  1.1.3
		 */
		public static function rss_version() : string {

			return '';

		}

	}
