<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	require_once( SECSAFE_DIR_ADMIN_TABLES . '/Table.php' );

	/**
	 * Class TableAllowDeny
	 * @package SecuritySafe
	 */
	final class TableAllowDeny extends Table {

		/**
		 * Get a list of columns. The format is:
		 * 'internal-name' => 'Title'
		 *
		 * @return array
		 * @since 3.1.0
		 * @abstract
		 *
		 * @package WordPress
		 */
		function get_columns() {

			return [
				'cb'            => '<input type="checkbox" />',
				'date'          => __( 'Date Added', SECSAFE_TRANSLATE ),
				'expire_status' => __( 'Status', SECSAFE_TRANSLATE ),
				'status'        => __( 'Rule', SECSAFE_TRANSLATE ),
				'ip'            => __( 'IP Address', SECSAFE_TRANSLATE ),
				'date_expire'   => __( 'Expires', SECSAFE_TRANSLATE ),
				'details'       => __( 'Notes', SECSAFE_TRANSLATE ),
			];

		}

		function column_expire_status( $item ) {

			return ( $item->date_expire == '0000-00-00 00:00:00' || date( 'Y-m-d H:i:s' ) < $item->date_expire ) ? __( 'active', SECSAFE_TRANSLATE ) : __( 'expired', SECSAFE_TRANSLATE );


		}

		function extra_tools() {

			$this->add_ip_form();
			$this->search_box( __( 'Search IPs', SECSAFE_TRANSLATE ), 'log' );

		}

		/**
		 * Creates Add IP form for the Allow/Deny table
		 * @return  html
		 */
		protected function add_ip_form() {

			/**
			 * @todo  I need to make this affect all tables.
			 * @date( 2090916)
			 */
			$bulk_actions = $this->get_bulk_actions();

			if ( isset( $bulk_actions['delete'] ) ) {

				// Add bulk delete nonce
				wp_nonce_field( SECSAFE_SLUG . '-bulk-delete', '_nonce_bulk_delete' );

			}

			echo '<p class="add_ip_form">';

			wp_nonce_field( SECSAFE_SLUG . '-add-ip', '_nonce_add_ip' );

			echo
				'<input name="ip" type="text" value="" placeholder="' . __( 'IP Address', SECSAFE_TRANSLATE ) . '">' .
				'<select name="ip_rule">' .
				'<option value="">- ' . __( 'Rule', SECSAFE_TRANSLATE ) . ' -</option>' .
				'<option value="allow">' . __( 'allow', SECSAFE_TRANSLATE ) . '</option>' .
				'<option value="deny">' . __( 'deny', SECSAFE_TRANSLATE ) . '</option>' .
				'</select>' .
				'<select name="ip_expire">' .
				'<option value="">- ' . __( 'Timespan', SECSAFE_TRANSLATE ) . ' -</option>' .
				'<option value="1">1 ' . __( 'day', SECSAFE_TRANSLATE ) . '</option>' .
				'<option value="3">3 ' . __( 'days', SECSAFE_TRANSLATE ) . '</option>' .
				'<option value="7">7 ' . __( 'days', SECSAFE_TRANSLATE ) . '</option>' .
				'<option value="30">1 ' . __( 'month', SECSAFE_TRANSLATE ) . '</option>' .
				'<option value="90">3 ' . __( 'months', SECSAFE_TRANSLATE ) . '</option>' .
				'<option value="180">6 ' . __( 'months', SECSAFE_TRANSLATE ) . '</option>' .
				'<option value="999">' . __( 'forever', SECSAFE_TRANSLATE ) . '</option>' .
				'</select>' .
				'<input name="ip_details" type="text" value="" placeholder="' . __( 'Notes', SECSAFE_TRANSLATE ) . '">' .
				'<input type="submit" name="ip_submit" class="button" value="' . __( 'Add IP', SECSAFE_TRANSLATE ) . '" />' .
				'</p>';

		}

		/**
		 * Get an associative array ( option_name => option_title ) with the list
		 * of bulk actions available on this table.
		 *
		 * @return array
		 * @since 3.1.0
		 *
		 * @package WordPress
		 */
		function get_bulk_actions() {

			return [
				'delete' => __( 'Delete', SECSAFE_TRANSLATE ),
			];

		}

		/**
		 * Handles the logic for adding an IP allow/deny rule to db
		 */
		function add_ip() {

			global $SecuritySafe;

			if (
				! isset( $_POST['action'] ) &&
				isset( $_POST['ip'] ) && $_POST['ip'] !== '' &&
				isset( $_POST['ip_rule'] ) && $_POST['ip_rule'] !== '' &&
				isset( $_POST['ip_expire'] ) && $_POST['ip_expire'] !== ''
			) {

				// Security Check
				if ( ! wp_verify_nonce( REQUEST::text_field('_nonce_add_ip'), SECSAFE_SLUG . '-add-ip' ) ) {

					$this->messages[] = [
						__( 'Error: IP address not added. Your session expired. Please try again.', SECSAFE_TRANSLATE ),
						3,
					];

					return; // Bail

				}

				$ip     = filter_var( $_POST['ip'], FILTER_VALIDATE_IP );
				$expire = filter_var( $_POST['ip_expire'], FILTER_VALIDATE_INT );

				if ( $ip && $expire !== false ) {

					// Valid IP Address

					$args                = [];
					$args['date_expire'] = ( $expire == '999' ) ? '0000-00-00 00:00:00' : date( 'Y-m-d H:i:s', strtotime( "+" . abs( $expire ) . " day" ) );
					$args['ip']          = $ip;
					$args['status']      = ( $_POST['ip_rule'] == 'deny' ) ? 'deny' : 'allow';
					$args['details']     = REQUEST::text_field('ip_details');
					$args['type']        = $type = 'allow_deny'; // Sanitized

					$result = $this->is_ip_whitelisted( $ip );

					if ( $result ) {

						$SecuritySafe->messages[] = [
							sprintf( __( 'Notice: %1$s -  IP address is already in the database.', SECSAFE_TRANSLATE ), $ip ),
							2,
							0,
						];

					} else {

						$result = Janitor::add_entry( $args );

						if ( $result ) {

							$SecuritySafe->messages[] = [
								sprintf( __( '%1$s - IP address added with %2$s rule.', SECSAFE_TRANSLATE ), $ip, $args['status'] ),
								0,
								0,
							];

						} else {

							$SecuritySafe->messages[] = [
								sprintf( __( 'Error: %1$s - IP address could not be added. Unknown reason.', SECSAFE_TRANSLATE ), $ip ),
								3,
								0,
							];

						}

					}

				} else {

					if ( ! $ip ) {

						$SecuritySafe->messages[] = [
							sprintf( __( 'Error: %s - IP address not valid.', SECSAFE_TRANSLATE ), esc_html( REQUEST::text_field('ip') ) ),
							3,
							0,
						];

					} else {

						$SecuritySafe->messages[] = [
							sprintf( __( 'Error: %s - Timespan not valid.', SECSAFE_TRANSLATE ), esc_html( REQUEST::text_field('ip_expire') ) ),
							3,
							0,
						];

					}

				}

			}

		}

		/**
		 * @todo  This needs functionality needs to be moved elsewhere. It is possibly duplicate.
		 * 02/22/2020
		 */
		protected function is_ip_whitelisted( $ip ) {

			global $wpdb;

			$ip         = filter_var( $ip, FILTER_VALIDATE_IP );
			$table_main = Yoda::get_table_main(); // Sanitized

			// Verify the IP is not already in db
			$query  = $wpdb->prepare( "SELECT ip FROM $table_main WHERE type = 'allow_deny' AND ip = '%s' LIMIT 1", $ip );
			return $wpdb->get_results( $query, ARRAY_A );

		}

		/**
		 * @todo  This needs functionality needs to be made accessible outside of this page so that
		 * the message could be displayed on any page in the Admin.
		 * 02/22/2020
		 */
		public function check_whitelist() {

			global $SecuritySafe;

			$ip = Yoda::get_ip();

			if ( $ip != '::1' ) {

				$whitelisted = $this->is_ip_whitelisted( $ip );

				if ( ! $whitelisted ) {

					$SecuritySafe->messages[] = [
						sprintf( __( '%s We recommend adding your IP to the allow list using the form below.', SECSAFE_TRANSLATE ), $ip ),
						2,
						0,
					];

				}

			}

		}

		/**
		 * Set the type of data to display
		 *
		 * @since  2.0.0
		 */
		protected function set_type() {

			$this->type = 'allow_deny';

		}

		/**
		 * Get the array of searchable columns in the database
		 * @return  array An unassociated array.
		 * @since  2.0.0
		 */
		protected function get_searchable_columns() {

			return [
				'ip',
			];

		}

	}
