<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	// Run Policy
	PolicyXMLRPC::init();

	/**
	 * Class PolicyXMLRPC
	 * @package SecuritySafe
	 */
	class PolicyXMLRPC {

		/**
		 * Register hooks
		 *
		 * @return void
		 */
		public static function init() : void {

			add_filter( 'xmlrpc_enabled', [ self::class, 'disable' ], 50 );

			// Remove Link From Head
			remove_action( 'wp_head', 'rsd_link' );

		}

		/**
		 * Disable XML-RPC
		 */
		public static function disable() : void {

			$args            = [];
			$args['type']    = 'logins';
			$args['score']   = 1;
			$args['details'] = __( 'XML-RPC Disabled.', SECSAFE_TRANSLATE );

			// Get Username
			$data = file_get_contents( 'php://input' );
			libxml_use_internal_errors( true ); // supress errors
			$xml              = simplexml_load_string( $data );
			$username         = ( $xml && isset( $xml->params->param[2]->value->string ) ) ? $xml->params->param[2]->value->string : 'unknown';
			$args['username'] = sanitize_user( $username );

			Firewall::rate_limit();

			// Block the attempt
			Firewall::block( $args );

		}

	}
