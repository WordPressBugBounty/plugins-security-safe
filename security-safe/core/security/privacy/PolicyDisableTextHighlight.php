<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	/**
	 * Class PolicyDisableTextHighlight
	 * @package SecuritySafe
	 * @since 1.1.0
	 */
	class PolicyDisableTextHighlight {

		/**
		 * PolicyDisableTextHighlight constructor.
		 */
		function __construct() {

			add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ] );

		}

		/**
		 * Loads CSS To Prevent Highlighting.
		 */
		function scripts() {

			// Load CSS
			wp_register_style( 'ss-pdth', SECSAFE_URL_ASSETS . 'css/pdth.css', [], SECSAFE_VERSION, 'all' );
			wp_enqueue_style( 'ss-pdth' );

		}

	}
