<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	/**
	 * Class PolicyHideScriptVersions
	 * @package SecuritySafe
	 * @since 1.1.3
	 */
	class PolicyHideScriptVersions {

		/**
		 * PolicyHideWPVersion constructor.
		 */
		function __construct() {

			// Cache Busting
			add_action( 'upgrader_process_complete', [ $this, 'increase_cache_busting' ], 10, 2 );

			// Remove Version From Scripts
			add_filter( 'style_loader_src', [ $this, 'css_js_version' ], 99999 );
			add_filter( 'script_loader_src', [ $this, 'css_js_version' ], 99999 );

		}

		/**
		 * Remove All Versions From Enqueued Scripts
		 *
		 * @param string $src Original source of files with versions
		 *
		 * @return string
		 *
		 * @since  1.1.3
		 */
		function css_js_version( $src ) {

			global $SecuritySafe;

			$cache_buster = $SecuritySafe->get_cache_busting();

			// Replacement version
			$version = 'ver=' . date( 'YmdH' ) . $cache_buster;

			if ( strpos( $src, 'ver=' ) ) {

				$src = preg_replace( "/ver=.[^&, ]+/", $version, $src );

			}

			return $src;

		}

		/**
		 * Increase Cache Busting value wrapper
		 *
		 * @param object $upgrader_object WP_Upgrader instance. In other contexts, $this, might be a Theme_Upgrader, Plugin_Upgrader, Core_Upgrade, or Language_Pack_Upgrader instance.
		 * @param array $options Array of bulk item update data.
		 *
		 * @link https://developer.wordpress.org/reference/hooks/upgrader_process_complete/
		 */
		function increase_cache_busting( $upgrader_object, $options ) {

			global $SecuritySafe;

			$SecuritySafe->increase_cache_busting();

		}

	}
