<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	require_once( SECSAFE_DIR_ADMIN_TABLES . '/Table.php' );

	/**
	 * Class TableBlocked
	 * @package SecuritySafe
	 */
	final class TableBlocked extends Table {

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
				'uri'        => __( 'URL', SECSAFE_TRANSLATE ),
				'user_agent' => __( 'User Agent', SECSAFE_TRANSLATE ),
				'referer'    => __( 'HTTP Referer', SECSAFE_TRANSLATE ),
				'ip'         => __( 'IP Address', SECSAFE_TRANSLATE ),
				'status'     => __( 'Status', SECSAFE_TRANSLATE ),
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

                <div class="chart chart-blocked-line td td-12 center">

                    <h3>' . sprintf( __( 'Threats Over The Past %d Days', SECSAFE_TRANSLATE ), $days ) . '</h3>
                    <div id="chart-line"></div>

                </div>

            </div>
        </div>';

			$charts = [];

			$columns = [
				[
					'id'    => 'threats',
					'label' => __( 'Threats', SECSAFE_TRANSLATE ),
					'color' => '#f6c600',
					'type'  => 'area-spline',
					'db'    => 'threats',
				],
				[
					'id'    => 'blocked',
					'label' => __( 'Blocked', SECSAFE_TRANSLATE ),
					'color' => '#0073aa',
					'type'  => 'area-spline',
					'db'    => 'blocked',
				],
			];

			$charts[] = [
				'id'      => 'chart-line',
				'type'    => 'line',
				'columns' => $columns,
				'y-label' => __( '# Threats', SECSAFE_TRANSLATE ),
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

			$this->type = 'threats';

		}

		protected function get_status() {

			return [
				//  'key'           => 'label'
				'not_blocked' => __( 'not blocked', SECSAFE_TRANSLATE ),
				'blocked'     => __( 'blocked', SECSAFE_TRANSLATE ),
			];

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

		/**
		 * Add filters and per_page options
		 */
		protected function bulk_actions( $which = '' ) {

			$this->bulk_actions_load( $which );

		}

		protected function column_status( $item ) {

			return ( $item->status == 'blocked' ) ? __( 'blocked', SECSAFE_TRANSLATE ) : __( 'not blocked', SECSAFE_TRANSLATE );

		}

		protected function column_details( $item ) {

			$details = $item->details;

			if ( $item->type == 'logins' ) {

				$details .= ' ';
				$details .= ( $item->status == 'success' ) ? __( 'Login attempt was successful.', SECSAFE_TRANSLATE ) : '';
				$details .= ( $item->status == 'failed' ) ? __( 'Login attempt failed.', SECSAFE_TRANSLATE ) : '';
				$details .= ( $item->status == 'blocked' ) ? __( 'Login attempt blocked.', SECSAFE_TRANSLATE ) : '';
				$details .= ( $item->username ) ? ' ' . sprintf( __( 'Username: %s', SECSAFE_TRANSLATE ), $item->username ) : '';

			}

			return esc_html( $details );

		}

	}
