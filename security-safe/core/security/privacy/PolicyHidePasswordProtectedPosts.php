<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	/**
	 * Class PolicyHidePasswordProtectedPosts
	 * @package SecuritySafe
	 * @since 1.1.7
	 */
	class PolicyHidePasswordProtectedPosts {

		/**
		 * PolicyPasswordProtectChildren constructor.
		 */
		function __construct() {

			add_action( 'pre_get_posts', [ $this, 'exclude' ] );

		}

		/**
		 * Add to the query to require all posts that do not have a password.
		 *
		 * @param $where string
		 *
		 * @return string
		 */
		function query( $where ) {

			global $wpdb;

			$where .= " AND {$wpdb->posts}.post_password = '' ";

			return $where;

		}

		/**
		 * Exclude the password protected pages
		 *
		 * @param object $query The WP_Query instance
		 *
		 * @link https://developer.wordpress.org/reference/hooks/pre_get_posts/
		 */
		function exclude( $query ) {

			if ( ! is_single() && ! is_page() && ! is_admin() ) {

				add_filter( 'posts_where', [ $this, 'query' ] );

			}

		}

	}
