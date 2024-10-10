<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	/**
	 * Class PolicyHideWPVersionAdmin
	 * @package SecuritySafe
	 * @since 1.2.0
	 */
	class PolicyHideWPVersionAdmin {

		/**
		 * PolicyHideWPVersionAdmin constructor.
		 */
		function __construct() {

			// Update footer
			add_action( 'admin_init', [ $this, 'update_footer' ] );

		}

		/**
		 * Update WordPress Admin Footer Version
		 * @since  1.2.0
		 */
		function update_footer() {

			add_filter( 'admin_footer_text', [ $this, 'custom_footer' ], 11 );
			add_filter( 'update_footer', '__return_false', 11 );

		}

		/**
		 * Set a custom string value for the footer
		 *
		 * @return string
		 *
		 * @since  1.2.0
		 */
		function custom_footer() {

			// @todo Will add the ability to customize this in the future.

			return '';

		}

	}
