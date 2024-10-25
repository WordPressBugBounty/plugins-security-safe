<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	use WP_Upgrader;

	// Run Policy
	PolicyHideScriptVersions::init();

	/**
	 * Class PolicyHideScriptVersions
	 * @package SecuritySafe
	 * @since 1.1.3
	 */
	class PolicyHideScriptVersions {

		/**
		 * Register hooks
		 *
		 * @return void
		 */
		public static function init() : void {

			// Cache Busting
			add_action( 'upgrader_process_complete', [ self::class, 'increase_cache_busting' ], 10, 2 );

			// Remove Version From Scripts
			add_filter( 'style_loader_src', [ self::class, 'css_js_version' ], 99999 );
			add_filter( 'script_loader_src', [ self::class, 'css_js_version' ], 99999 );

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
		public static function css_js_version( string $src ) : string {

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
		 * @link https://developer.wordpress.org/reference/hooks/upgrader_process_complete/
		 *
		 * @param \WP_Upgrader $upgrader_object WP_Upgrader instance. In other contexts, $this, might be a Theme_Upgrader, Plugin_Upgrader, Core_Upgrade, or Language_Pack_Upgrader instance.
		 * @param array $options
		 *
		 * @return void
		 */
		public static function increase_cache_busting( WP_Upgrader $upgrader_object, array $options ) : void {

			global $SecuritySafe;

			$SecuritySafe->increase_cache_busting();

		}

	}
