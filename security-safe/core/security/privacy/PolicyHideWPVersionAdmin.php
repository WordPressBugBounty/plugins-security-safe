<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	// Run Policy
	PolicyHideWPVersionAdmin::init();

	/**
	 * Class PolicyHideWPVersionAdmin
	 * @package SecuritySafe
	 * @since 1.2.0
	 */
	class PolicyHideWPVersionAdmin {

		/**
		 * Register hooks
		 *
		 * @return void
		 */
		public static function init() : void {

			// Update footer
			add_action( 'admin_init', [ self::class, 'update_footer' ] );

		}

		/**
		 * Update WordPress Admin Footer Version
		 * @since  1.2.0
		 */
		public static function update_footer() : void {

			add_filter( 'admin_footer_text', [ self::class, 'custom_footer' ], 11, 0 );
			add_filter( 'update_footer', '__return_false', 11 );

		}

		/**
		 * Set a custom string value for the footer
		 *
		 * @return string
		 *
		 * @since  1.2.0
		 */
		public static function custom_footer() : string {

			// @todo Will add the ability to customize this in the future.

			return '';

		}

	}
