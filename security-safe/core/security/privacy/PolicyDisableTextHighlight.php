<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	// Run Policy
	PolicyDisableTextHighlight::init();

	/**
	 * Class PolicyDisableTextHighlight
	 * @package SecuritySafe
	 * @since 1.1.0
	 */
	class PolicyDisableTextHighlight {

		/**
		 * Register hooks
		 *
		 * @return void
		 */
		public static function init() : void {

			add_action( 'wp_enqueue_scripts', [ self::class, 'scripts' ] );

		}

		/**
		 * Loads CSS To Prevent Highlighting.
		 */
		public static function scripts() : void {

			// Load CSS
			wp_register_style( 'ss-pdth', SECSAFE_URL_ASSETS . 'css/pdth.css', [], SECSAFE_VERSION, 'all' );
			wp_enqueue_style( 'ss-pdth' );

		}

	}
