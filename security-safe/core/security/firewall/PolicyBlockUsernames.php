<?php

namespace SovereignStack\SecuritySafe;

// Prevent Direct Access
( defined( 'ABSPATH' ) ) || die;

use \WP_Error;

/**
 * Class PolicyXMLRPC
 *
 * @package SecuritySafe
 */
class PolicyBlockUsernames extends Firewall {

	var $setting_on = false;

	/**
	 * PolicyXMLRPC constructor.
	 */
	function __construct( $setting = false ) {

		if ( $setting ) {

			// Run parent class constructor first
			parent::__construct();

			add_filter( 'authenticate', [ $this, 'check_username', ], 30, 2 );
			add_filter( 'illegal_user_logins', [ $this, 'illegal_user_logins', ], 10, 1 );

		}

	}

	/**
	 * Tell WordPress that the usernames defined in the settings are not allowed to be used for new user accounts
	 *
	 * @param array $usernames
	 *
	 * @return array
	 */
	public function illegal_user_logins( array $usernames = [] ) : array {

		$usernames = array_merge( $usernames, self::get_blocked_usernames_list() );
		$usernames = array_unique( $usernames );

		if ( is_multisite() ) {
			// Make the username the key
			$usernames = array_flip( $usernames );

			// Certain usernames are already blocked and will cause a double error message in admin so we must remove them from the list
			foreach ( get_site_option( 'illegal_names' ) as $illegal_username ) {
				unset( $usernames[ strtolower( $illegal_username ) ] );
			}

			// Convert username back to the value
			$usernames = array_flip( $usernames );
		}

		return $usernames;

	}

	/**
	 * Converts the blocked username list from a string dilimited by new lines to an array
	 *
	 * @param string $blocked_usernames_list
	 * @param bool   $illegal_usernames
	 *
	 * @return array
	 */
	public static function get_blocked_usernames_list( string $blocked_usernames_list = '', bool $illegal_usernames = false ): array {

		global $SecuritySafe;

		if ( empty( $blocked_usernames_list ) ) {
			// Grab from existing settings
			$site_settings = $SecuritySafe->get_settings();
			$settings_access = $site_settings['access'] ?? [];

			// Grab the raw value
			$blocked_usernames_list = $settings_access['block_usernames_list'] ?? '';
		}

		if ( ! empty( $blocked_usernames_list ) ) {
			// Ensure that usernames separated by a space are converted to new lines
			$blocked_usernames_list = str_replace(" ", "\n", $blocked_usernames_list);

			// Convert the value into an array with new line delimiter
			$blocked_usernames_list = explode( "\n", $blocked_usernames_list );
		} else {
			// List is empty
			$blocked_usernames_list = [];
		}

		if ( $illegal_usernames && is_multisite() ) {
			// In a multisite environment, there are illegal usernames already defined
			$illegal_names = get_site_option( 'illegal_names' );

			// Add them to our list of blocked usernames
			foreach ( $illegal_names as $username ) {
				$blocked_usernames_list[] = $username;
			}
		}

		// get rid of extra spaces so that we can do a direct comparison
		$blocked_usernames_list = array_map( 'trim', $blocked_usernames_list );

		// Usernames are case-insensitive
		$blocked_usernames_list = array_map( 'strtolower', $blocked_usernames_list );

		// Get rid of duplicates
		return array_unique( $blocked_usernames_list );

	}



	/**
	 * Determine if the username is on the block list
	 *
	 * @param object|null $user
	 * @param string $username
	 *
	 * @return \WP_Error
	 */
	function check_username( $user, $username ) {

		global $SecuritySafe;

		if (
			( is_wp_error( $user ) && isset( $user->errors['invalid_username'] )) || // Username doesn't exist
			! is_wp_error( $user ) // Hasn't been blocked
		) {

			// Final check of blacklisting to block login
			if ( ! empty( self::in_block_list( [ $username ], true ) ) ) {

				//Janitor::log( 'login blacklisted!!!!' );

				$args = [];
				$args['type'] = 'logins';
				$args['details'] = __( 'Username is blocked.', SECSAFE_TRANSLATE ) . '[' . __LINE__ . ']';
				$args['username'] = ( $username ) ? sanitize_user( $username ) : '';
				$args['score'] = 10;

				// Block the attempt
				$this->block( $args, false );

				// Prevent default generic message
				$SecuritySafe->login_error = true;

				$user = new WP_Error();
				$user->add( 'wp_security_safe_lockout', __( '<b>ERROR:</b> Please contact your site administrator for assistance.', SECSAFE_TRANSLATE ) );

			}

		}

		return $user;

	}

	/**
	 * Check to see if the provided usernames are in the block list.
	 *
	 * @since 2.5.0
	 *
	 * @param array $usernames
	 * @param bool  $illegal_names
	 * @param array $block_usernames_list
	 *
	 * @return array Usernames that it found
	 */
	public static function in_block_list( array $usernames, bool $illegal_names = false, array $block_usernames_list = [] ): array {

		$blocked_usernames_list = ! empty( $block_usernames_list ) ? $block_usernames_list : self::get_blocked_usernames_list( '', $illegal_names );

		return array_intersect( $usernames, $blocked_usernames_list );

	}

}


