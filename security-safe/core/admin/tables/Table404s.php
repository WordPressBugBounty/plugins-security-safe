<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	require_once( SECSAFE_DIR_ADMIN_TABLES . '/Table.php' );

	/**
	 * Class Table404s
	 * @package SecuritySafe
	 */
	final class Table404s extends Table {

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
				'date'       => __( 'Date / Time', SECSAFE_TRANSLATE ),
				'uri'        => __( 'URL', SECSAFE_TRANSLATE ),
				'user_agent' => __( 'User Agent', SECSAFE_TRANSLATE ),
				'referer'    => __( 'HTTP Referer', SECSAFE_TRANSLATE ),
				'ip'         => __( 'IP Address', SECSAFE_TRANSLATE ),
				'status'     => __( 'Status', SECSAFE_TRANSLATE ),
				'threats'    => __( 'Threat', SECSAFE_TRANSLATE ),
			];

		}

		public function display_charts() {

			if ( $this->hide_charts() ) {
				return;
			}

			$days     = 30;
			$days_ago = $days - 1;

			$charts = [];

			$columns = [
				[
					'id'    => 'errors',
					'label' => __( '404 Errors', SECSAFE_TRANSLATE ),
					'color' => '#dc3232',
					'type'  => 'area-spline',
					'db'    => '404s',

				],
			];

			$charts[] = [
				'id'      => 'chart-line',
				'type'    => 'line',
				'columns' => $columns,
				'y-label' => __( '# 404 Errors', SECSAFE_TRANSLATE ),
			];

			$args = [
				'date_start'    => date( 'Y-m-d 00:00:00', strtotime( '-' . $days_ago . ' days' ) ),
				'date_end'      => date( 'Y-m-d 23:59:59', time() ),
				'date_days'     => $days,
				'date_days_ago' => $days_ago,
				'charts'        => $charts,
			];

			echo '
        <div class="table">
            <div class="tr">

                <div class="chart chart-404s-line td td-12 center">

                    <h3>' . sprintf( __( '404 Errors Over The Past %d Days', SECSAFE_TRANSLATE ), $days ) . '</h3>
                    <p>' . substr( $args['date_start'], 0, 10 ) . ' - ' . substr( $args['date_end'], 0, 10 ) . '
                    <div id="chart-line"></div>

                </div>

            </div>
        </div>';

			// Load Charts
			Admin::load_charts( $args );

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

			return [
				'uri',
				'ip',
				'referer',
			];

		}

		protected function get_status() {

			return [
				//  'value'     => 'label'
				'blocked' => __( 'Blocked', SECSAFE_TRANSLATE ),
			];

		}

		/**
		 * Add filters and per_page options
		 */
		protected function bulk_actions( $which = '' ) {

			$this->bulk_actions_load( $which );

		}

	}
