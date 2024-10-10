<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	require_once( SECSAFE_DIR_ADMIN_TABLES . '/Table.php' );

	/**
	 * Class TableFilePerms
	 * @package SecuritySafe
	 */
	final class TableFilePerms extends Table {

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
				'location' => __( 'Relative Location', SECSAFE_TRANSLATE ),
				'type'     => __( 'Type', SECSAFE_TRANSLATE ),
				'current'  => __( 'Current', SECSAFE_TRANSLATE ),
				'status'   => __( 'Status', SECSAFE_TRANSLATE ),
				'modify'   => __( 'Modify', SECSAFE_TRANSLATE ),
			];

		}

		function get_sortable_columns() {

			return [];

		}

		/**
		 * Set the type of data to display
		 *
		 * @since  2.0.0
		 */
		protected function set_type() {

			$this->type = '404s';

		}

		/**
		 * Get the array of searchable columns in the database
		 * @return  array An unassociated array.
		 * @since  2.0.0
		 */
		protected function get_searchable_columns() {

			return [];

		}

	}
