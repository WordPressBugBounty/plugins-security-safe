<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	// Run Policy
	PolicyDisableRightClick::init();

	/**
	 * Class PolicyDisableRightClick
	 * @package SecuritySafe
	 * @since 1.1.0
	 */
	class PolicyDisableRightClick {

		/**
		 * Register hooks
		 *
		 * @return void
		 */
		public static function init() : void {

			if ( ! is_admin() ) {

				add_action( 'wp_enqueue_scripts', [ self::class, 'scripts' ] );

			}

		}

		/**
		 * Loads JS To Disable Right Click.
		 */
		public static function scripts() : void {

			// JS File
			wp_enqueue_script( 'ss-pdrc', SECSAFE_URL_ASSETS . 'js/pdrc.js', [ 'jquery' ], SECSAFE_VERSION, true );

		}

	}
