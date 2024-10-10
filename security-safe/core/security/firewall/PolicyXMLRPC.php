<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	/**
	 * Class PolicyXMLRPC
	 * @package SecuritySafe
	 */
	class PolicyXMLRPC extends Firewall {

		var $setting_on = false;

		/**
		 * PolicyXMLRPC constructor.
		 */
		function __construct( $setting = false ) {

			if ( $setting ) {

				add_filter( 'xmlrpc_enabled', [ $this, 'disable' ], 50 );

				// Remove Link From Head
				remove_action( 'wp_head', 'rsd_link' );

			}

		}

		/**
		 * Disable XML-RPC
		 */
		function disable() {

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

			$this->rate_limit();

			// Block the attempt
			$this->block( $args );

		}

	}
