<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	require_once( SECSAFE_DIR_ADMIN_TABLES . '/Table.php' );

	/**
	 * Class TableLogins
	 * @package SecuritySafe
	 */
	final class TableLogins extends Table {

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
				'date'       => __( 'Date', SECSAFE_TRANSLATE ),
				'username'   => __( 'Username', SECSAFE_TRANSLATE ),
				'ip'         => __( 'IP Address', SECSAFE_TRANSLATE ),
				'user_agent' => __( 'User Agent', SECSAFE_TRANSLATE ),
				'status'     => __( 'Status', SECSAFE_TRANSLATE ),
				'threats'    => __( 'Threat', SECSAFE_TRANSLATE ),
				'details'    => __( 'Details', SECSAFE_TRANSLATE ),
			];

		}

		public function display_charts() {

			if ( $this->hide_charts() ) {
				return;
			}

			$days     = 30;
			$days_ago = $days - 1;

			echo '
        <div class="table">
            <div class="tr">

                <div class="chart chart-logins-line td td-9 center">

                    <h3>' . sprintf( __( 'Login Attempts Over The Past %d Days', SECSAFE_TRANSLATE ), $days ) . '</h3>
                    <div id="chart-line"></div>

                </div><div class="chart chart-logins-pie td td-3 center">

                    <h3>' . __( 'Login Distribution', SECSAFE_TRANSLATE ) . '</h3>
                    <div id="chart-pie"></div>

                </div>

            </div>
        </div>';

			$charts = [];

			$columns = [
				[
					'id'    => 'total',
					'label' => __( 'Total', SECSAFE_TRANSLATE ),
					'color' => '#aaaaaa',
					'type'  => 'area-spline',
					'db'    => 'logins',

				],
				[
					'id'    => 'threats',
					'label' => __( 'Threats', SECSAFE_TRANSLATE ),
					'color' => '#f6c600',
					'type'  => 'bar',
					'db'    => 'logins_threats',
				],
				[
					'id'    => 'blocked',
					'label' => __( 'Blocked', SECSAFE_TRANSLATE ),
					'color' => '#0073aa',
					'type'  => 'bar',
					'db'    => 'logins_blocked',
				],
				[
					'id'    => 'failed',
					'label' => __( 'Failed', SECSAFE_TRANSLATE ),
					'color' => '#dc3232',
					'type'  => 'bar',
					'db'    => 'logins_failed',
				],
				[
					'id'    => 'success',
					'label' => __( 'Success', SECSAFE_TRANSLATE ),
					'color' => '#029e45',
					'type'  => 'bar',
					'db'    => 'logins_success',
				],
			];

			$charts[] = [
				'id'      => 'chart-line',
				'type'    => 'line',
				'columns' => $columns,
				'y-label' => __( '# Login Attempts', SECSAFE_TRANSLATE ),
			];

			// Remove unused columns total, threats
			unset( $columns[0], $columns[1] );

			$charts[] = [
				'id'      => 'chart-pie',
				'type'    => 'pie',
				'columns' => $columns,
			];

			$args = [
				'date_start'    => date( 'Y-m-d 00:00:00', strtotime( '-' . $days_ago . ' days' ) ),
				'date_end'      => date( 'Y-m-d 23:59:59', time() ),
				'date_days'     => $days,
				'date_days_ago' => $days_ago,
				'charts'        => $charts,
			];


			// Load Charts
			Admin::load_charts( $args );

		}

		/**
		 * Set the type of data to display
		 *
		 * @since  2.0.0
		 */
		protected function set_type() {

			$this->type = 'logins';

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
				'username',
			];

		}

		protected function get_status() {

			return [
				//  'key'       => 'label'
				'success' => __( 'Success', SECSAFE_TRANSLATE ),
				'failed'  => __( 'Failed', SECSAFE_TRANSLATE ),
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
