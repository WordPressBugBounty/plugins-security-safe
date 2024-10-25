<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	use WP_Query;

	// Run Policy
	PolicyHidePasswordProtectedPosts::init();

	/**
	 * Class PolicyHidePasswordProtectedPosts
	 * @package SecuritySafe
	 * @since 1.1.7
	 */
	class PolicyHidePasswordProtectedPosts {

		/**
		 * Register hooks
		 *
		 * @return void
		 */
		public static function init() : void {

			add_action( 'pre_get_posts', [ self::class, 'exclude' ] );

		}

		/**
		 * Add to the query to require all posts that do not have a password.
		 *
		 * @param $where string
		 *
		 * @return string
		 */
		public static function query( string $where ) : string {

			global $wpdb;

			$where .= " AND $wpdb->posts.post_password = '' ";

			return $where;

		}

		/**
		 * Exclude the password protected pages
		 *
		 * @link https://developer.wordpress.org/reference/hooks/pre_get_posts/
		 *
		 * @param \WP_Query $query
		 *
		 * @return void
		 */
		public static function exclude( WP_Query $query ) : void {

			if ( ! is_single() && ! is_page() && ! is_admin() ) {

				add_filter( 'posts_where', [ self::class, 'query' ] );

			}

		}

	}
