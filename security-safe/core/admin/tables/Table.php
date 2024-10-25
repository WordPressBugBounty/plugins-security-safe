<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	use WP_List_Table;

	if ( ! class_exists( 'WP_List_Table' ) ) {

		require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

	}

	/**
	 * Class Table
	 * @package SecuritySafe
	 * @since 2.0.0
	 */
	class Table extends WP_List_Table {

		/**
		 * The desired type of data to display
		 * @since  2.0.0
		 */
		protected $type = false;

		function __construct() {

			parent::__construct();

			// Set the type to retrieve
			$this->set_type();

			// Set the columns
			$this->_column_headers = [
				$this->get_columns(),
				[], //hidden columns if applicable
				$this->get_sortable_columns(),
			];

		}

		/**
		 * Set the type of data to display.
		 * @since  2.0.0
		 */
		protected function set_type() {

			// This must be overwritten by the child class.

		}

		/**
		 * Get a list of sortable columns. The format is:
		 * 'internal-name' => 'orderby'
		 * or
		 * 'internal-name' => array( 'orderby', true )
		 *
		 * The second format will make the initial sorting order be descending
		 *
		 * @return array
		 * @since 3.1.0
		 *
		 * @package WordPress
		 */
		function get_sortable_columns() {

			return [
				'date'       => [ 'date', true ],
				'uri'        => [ 'uri', true ],
				'ip'         => [ 'ip', true ],
				'status'     => [ 'status', true ],
				'username'   => [ 'username', true ],
				'user_agent' => [ 'user_agent', true ],
				'referer'    => [ 'referer', true ],
			];

		}

		/**
		 * Prepares the list of items for displaying.
		 *
		 * @package  WordPress
		 * @uses WP_List_Table::set_pagination_args()
		 *
		 * @since 3.1.0
		 * @abstract
		 */
		function prepare_items() {

			global $wpdb, $SecuritySafe;

			$types = Yoda::get_types();

			// Bail if the type is not valid
			if ( ! $this->type || ! isset( $types[ $this->type ] ) ) {
				return;
			}

			// Process Bulk Deletes
			if ( 'delete' === $this->current_action() ) {

				$this->bulk_delete();

			}

			// Clean Values
			$table            = Yoda::get_table_main();
			$page             = $this->get_pagenum();
			$per_page         = REQUEST::int('per_page', '',25 );
			$search           = $this->get_search_query();
			$order            = REQUEST::key('order');
			$order            = $order && strtolower( $order ) == 'asc' ? 'ASC' : 'DESC';
			$limit            = $wpdb->esc_like( ( ( $page - 1 ) * $per_page ) . ',' . $per_page );
			$sortable_columns = $this->get_sortable_columns();
			$orderby          = REQUEST::key('orderby');
			$orderby          = $orderby && isset( $sortable_columns[ $orderby ] ) ? $sortable_columns[ $orderby ][0] : 'date';

			// Status
			$status = ( isset( $_REQUEST['status'] ) && $_REQUEST['status'] ) ? $wpdb->esc_like( $_REQUEST['status'] ) : '';

			if ( $status ) {

				$status = ( 'not\_' == mb_substr( $status, 0, 5 ) ) ? " AND status NOT LIKE '" . str_replace( 'not\_', '', $status ) . "' " : " AND status LIKE '" . $status . "' "; // Sanitized

			}

			$threats = ( $this->type == 'threats' ) ? true : false;

			if ( $threats ) {

				$type = "threats = '1'";

			} else {

				$type = "type LIKE '" . $this->type . "'";

			}

			$type .= ( $this->type == 'activity' ) ? " OR ( type LIKE 'logins' AND status LIKE 'success' )" : "";

			$query = "SELECT SQL_CALC_FOUND_ROWS * FROM $table WHERE $type $status $search ORDER BY $orderby $order LIMIT $limit";
			// echo $query;

			$this->items = $wpdb->get_results( $query );
			$total_items = $wpdb->get_var( "SELECT FOUND_ROWS()" );

			$limit_total = ( $threats ) ? - 1 : Yoda::get_display_limits( $this->type );
			$total_items = ( $total_items > $limit_total && $limit_total != - 1 ) ? $limit_total : $total_items;

			$this->set_pagination_args( [
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			] );

			if ( isset( $SecuritySafe->messages[0] ) ) {

				// Display Messages
				$SecuritySafe->display_notices( true );

			}

		}

		/**
		 * This deletes entries in bulk
		 *
		 * @return void
		 */
		private function bulk_delete() {

			global $wpdb, $SecuritySafe;

			if ( ! isset( $_POST['bulk_action'] ) ) {
				return;
			}

			// Security Check
			if ( ! wp_verify_nonce( REQUEST::text_field('_nonce_bulk_delete'), SECSAFE_SLUG . '-bulk-delete' ) ) {

				$SecuritySafe->messages[] = [
					__( 'Error: Could not delete row. Your session expired. Please try again.', SECSAFE_TRANSLATE ),
					3,
				];

				return; // Bail

			}

			$table = Yoda::get_table_main();
			$ids   = array_map( 'intval', (array) $_POST['bulk_action'] );
			$ids   = implode( ',', $ids );

			$deleted = $wpdb->query( "DELETE FROM $table WHERE ID IN ( $ids )" );

			if ( $deleted ) {

				$SecuritySafe->messages[] = [ sprintf( __( '%d rows deleted', SECSAFE_TRANSLATE ), $deleted ), 0, 0 ];

			} else {

				$SecuritySafe->messages[] = [ __( 'Could not delete entry. Please try again.', SECSAFE_TRANSLATE ), 3, 0 ];

			}

		}

		/**
		 * Gets the search query
		 *
		 * @return string
		 * @since  2.0.0
		 */
		private function get_search_query() {

			global $wpdb, $SecuritySafe;

			$query              = '';
			$search             = ( isset( $_REQUEST['s'] ) && $_REQUEST['s'] ) ? $wpdb->esc_like( $_REQUEST['s'] ) : ''; // Sanitized
			$searchable_columns = $this->get_searchable_columns();

			// Add and Sanitize Search Query
			if ( $search !== '' && isset( $searchable_columns[0] ) ) {

				$num = 0;

				$query = " AND ( ";

				foreach ( $searchable_columns as $column ) {

					$num ++;
					$query .= ( $num > 1 ) ? " OR " : "";
					$query .= "($column LIKE '%$search%')";

				}

				$query .= " ) ";

				$SecuritySafe->messages[] = [ __( 'Search results are provided below.', SECSAFE_TRANSLATE ), 0, 0 ];

			}

			return $query;

		}

		/**
		 * Get the array of searchable columns in the database
		 *
		 * @return  array | void An unassociated array.
		 * @since  2.0.0
		 */
		protected function get_searchable_columns() {

			// Must be overwritten by child class
			return [];

		}

		/**
		 * Display the filters and per_page options. This should be wrapped by the bulk_actions() method in a child class.
		 *
		 * @param string $which The location of the bulk actions: 'top' or 'bottom'.
		 *                      This is designated as optional for backward compatibility.
		 *
		 * @since 2.0.0
		 *
		 */
		function bulk_actions_load( $which = '' ) {

			if ( $which != 'top' ) {
				return;
			}

			$html = '';
			$page = REQUEST::key('page', '', SECSAFE_SLUG );
			$tab  = REQUEST::key('tab');
			$requested_status = REQUEST::text_field('status');

			$status = $this->get_status();

			if ( is_array( $status ) && ! empty( $status ) ) {

				$html .= '<select name="status">
                        <option value="">-- ' . __( 'Select Status', SECSAFE_TRANSLATE ) . ' --</option>';

				foreach ( $status as $value => $label ) {

					$html .= '<option value="' . esc_html( $value ) . '"' . selected( $value, $requested_status, false ) . '>' . esc_html( $label ) . '</option>';

				}

				$html .= '</select>';

			}

			$html .= '<select name="per_page">';

			$per_page = [ '25', '50', '100', '250' ];

			foreach ( $per_page as $value ) {

				$html .= '<option value="' . esc_attr( $value ) . '" ' . selected( $value, REQUEST::text_field( 'per_page' ), false ) . '>' . esc_html( $value ) . ' ' . __( 'Per Page', SECSAFE_TRANSLATE ) . '</option>';

			}

			$search = REQUEST::text_field('s');

			$html .=
				'</select>

                <input type="submit" class="button" value="' . __( 'Apply Filters', SECSAFE_TRANSLATE ) . '">';

			// Display Reset Filters

			if ( $this->hide_charts() ) {

				$page = '?page=' . $page;
				$tab  = ( $tab ) ? '&tab=' . $tab : '';

				$html .= '<a href="' . admin_url( 'admin.php' . $page . $tab ) . '" class="reset-filters" style="padding:5px 13px;display:inline-block;">' . __( 'reset filters', SECSAFE_TRANSLATE ) . '</a>';

			}

			echo $html;

		}

		/**
		 * @return bool
		 * @todo Add documentation about this method
		 *
		 */
		protected function get_status() {

			return false;

		}

		/**
		 * @return bool
		 * @todo Add documentation about this method
		 *
		 */
		public function hide_charts() {

			if (
				isset( $_REQUEST['s'] ) ||
				isset( $_REQUEST['order'] ) ||
				isset( $_REQUEST['orderby'] ) ||
				isset( $_REQUEST['per_page'] ) ||
				isset( $_REQUEST['status'] )
			) {

				return true;

			}

			return false;

		}

		/**
		 * Displays the default column
		 *
		 * @param object $item
		 * @param string $column_name
		 *
		 * @return string
		 * @package WordPress
		 */
		protected function column_default( $item, $column_name ) {

			return esc_html( $item->$column_name );

		}

		/**
		 * The checkbox column
		 *
		 * @param object $item
		 *
		 * @return string
		 */
		protected function column_cb( $item ) {

			return '<input type="checkbox" name="bulk_action[]" value="' . esc_html( $item->ID ) . '" />';

		}

		/**
		 * The URI column
		 *
		 * @param object $item
		 *
		 * @return string
		 */
		protected function column_uri( $item ) {

			$uri = esc_html( $item->uri );

			return '<a href="' . $uri . '" target="_blank">' . $uri . '</a>';

		}

		/**
		 * The IP column
		 *
		 * @param object $item
		 *
		 * @return string
		 */
		protected function column_ip( $item ) {

			return esc_html( $item->ip );

		}

		/**
		 * The status column
		 *
		 * @param object $item
		 *
		 * @return string
		 */
		protected function column_status( $item ) {

			$status = [
				//  'value'     => 'label'
				'blocked'     => __( 'Blocked', SECSAFE_TRANSLATE ),
				'not_blocked' => __( 'not blocked', SECSAFE_TRANSLATE ),
				'success'     => __( 'Success', SECSAFE_TRANSLATE ),
				'failed'      => __( 'Failed', SECSAFE_TRANSLATE ),
				'test'        => __( 'System Test', SECSAFE_TRANSLATE ),
				'manual'      => __( 'Manual', SECSAFE_TRANSLATE ),
				'automatic'   => __( 'Automatic', SECSAFE_TRANSLATE ),
				'deny'        => __( 'Deny', SECSAFE_TRANSLATE ),
				'allow'       => __( 'Allow', SECSAFE_TRANSLATE ),
			];

			$value = ( isset( $status[ $item->status ] ) ) ? $status[ $item->status ] : $item->status;

			return esc_html( $value );

		}

		/**
		 * The threats column
		 *
		 * @param object $item
		 *
		 * @return string
		 */
		protected function column_threats( $item ) {

			return ( $item->threats ) ? __( 'Yes', SECSAFE_TRANSLATE ) : __( 'No', SECSAFE_TRANSLATE );

		}

		/**
		 * The expire date column
		 *
		 * @param object $item
		 *
		 * @return string
		 */
		protected function column_date_expire( $item ) {

			$date_expire = ( $item->date_expire == '0000-00-00 00:00:00' ) ? __( 'never', SECSAFE_TRANSLATE ) : $item->date_expire;

			return esc_html( $date_expire );

		}

	}
