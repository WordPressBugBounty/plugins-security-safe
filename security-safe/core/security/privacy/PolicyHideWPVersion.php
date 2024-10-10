<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	/**
	 * Class PolicyHideWPVersion
	 * @package SecuritySafe
	 * @since 1.1.3
	 */
	class PolicyHideWPVersion {

		/**
		 * PolicyHideWPVersion constructor.
		 */
		function __construct() {

			// Remove Version From RSS
			add_filter( 'the_generator', [ $this, 'rss_version' ] );

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
		function rss_version() {

			return '';

		}

	}
