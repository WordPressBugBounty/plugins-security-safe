<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	/**
	 * Class PolicyLog404s
	 * @package SecuritySafe
	 * @since  2.0.0
	 */
	class PolicyLog404s extends Firewall {

		/**
		 * PolicyLog404s constructor.
		 */
		function __construct() {

			// Run parent class constructor first
			parent::__construct();

			add_action( 'get_header', [ $this, 'error' ] );

		}

		/**
		 * Logs the 404 error.
		 *
		 * @since  2.0.0
		 */
		function error() {

			global $SecuritySafe;

			if ( is_404() ) {

				$args         = [];
				$args['type'] = '404s';

				if ( $SecuritySafe->is_blacklisted() ) {

					$args['score']   = 0;
					$args['details'] = __( 'IP is blacklisted.', SECSAFE_TRANSLATE ) . '[' . __LINE__ . ']';

					// Block display of any 404 errors
					$this->block( $args );

				} else {

					$uri = filter_var( $_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL );

					$filename = explode( '/', $uri );
					$filename = end( $filename );

					// Check For Threats
					$args['score'] = ( Threats::is_filename( $filename ) ) ? 1 : 0;
					$args['score'] += ( Threats::is_file_extention( $filename ) ) ? 1 : 0;
					$args['score'] += ( Threats::is_uri( $uri ) ) ? 1 : 0;

					$args['threats'] = ( $args['score'] > 0 ) ? 1 : 0;

					if ( $args['score'] > 1 ) {

						$args['details'] = __( 'Multiple threat detection.', SECSAFE_TRANSLATE ) . '[' . __LINE__ . ']';
						$this->block( $args );

					} else {

						// Add 404 Entry
						Janitor::add_entry( $args );

					}

				}

			}

		}

	}
