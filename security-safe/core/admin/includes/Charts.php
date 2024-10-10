<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	/**
	 * Class Charts
	 * @package SecuritySafe
	 */
	class Charts {

		/**
		 * Displays all the charts
		 *
		 * @param array $args
		 *
		 * @since 2.0.0
		 */
		public static function display_charts( array $args ) : void {

			$stats = ( isset( $args['date_days'] ) ) ? Charts::get_stats( $args ) : '';

			// Bail if bad input
			if ( ! $stats || ! isset( $args['charts'][0] ) ) {
				return;
			}

			$html = '';

			foreach ( $args['charts'] as $chart ) {

				if ( isset( $chart['id'] ) && isset( $chart['type'] ) ) {

					if ( $chart['type'] == 'line' ) {
						$html .= Charts::display_line_chart( $chart, $stats );
					}

					if ( $chart['type'] == 'pie' ) {
						$html .= Charts::display_pie_chart( $chart, $stats );
					}

					if ( $chart['type'] == 'guage' ) {
						$html .= Charts::display_guage_chart( $chart, $stats );
					}

				}

			}

			if ( $html != '' ) {

				Charts::dependencies();

				// This is escaped throughout the display_[type]_chart() methods
				echo '<script>' . $html . '</script>';

			}

		}

		/**
		 * The stats are pulled from columns defined in the first chart in the charts array.
		 *
		 * @param array $args
		 *
		 * @return array
		 *
		 * @since  2.0.0
		 */
		private static function get_stats( array $args ) : array {

			global $wpdb;

			$table = Yoda::get_table_stats();

			$start = esc_sql( $args['date_start'] );
			$end   = esc_sql( $args['date_end'] );
			$day   = $args['date_days_ago'];

			$query = "  SELECT * FROM `" . $table . "` 
                    WHERE `date` 
                    BETWEEN '$start' 
                    AND '$end'
                    ORDER BY `date` ASC";

			$results = $wpdb->get_results( $query );

			if ( $results ) {

				$stats = [];
				$dates = [];

				// Converting object to associative array
				$results = json_decode( json_encode( $results ), true );

				$results_num = 0;

				while ( $day >= 0 ) {

					$date = ( $day == 0 ) ? date( 'Y-m-d' ) : date( 'Y-m-d', strtotime( '-' . $day . ' days' ) );
					$day  = $day - 1;

					if ( isset( $results[ $results_num ]['date'] ) ) {

						$result_date = substr( $results[ $results_num ]['date'], 0, 10 );

						if ( $result_date == $date ) {

							$dates[ $date ] = $results[ $results_num ];

							$results_num ++;

						}

					}

					foreach ( $args['charts'][0]['columns'] as $column ) {

						if ( $day == ( $args['date_days_ago'] - 1 ) ) {

							// Beginning
							$stats[ $column['id'] ] = '["' . $column['label'] . '", ';

						}

						$stats[ $column['id'] ] .= isset( $dates[ $date ] ) ? $dates[ $date ][ $column['db'] ] : 0;

						if ( $day >= 0 ) {

							// Comma
							$stats[ $column['id'] ] .= ', ';

						} else {

							// End
							$stats[ $column['id'] ] .= '],';

						}

					}

					if ( $day == ( $args['date_days_ago'] - 1 ) ) {

						// Beginning
						$stats['x'] = '["x", ';

					}

					$stats['x'] .= '"' . $date . '"';

					if ( $day >= 0 ) {

						// Add Comma
						$stats['x'] .= ', ';

					} else {

						// End
						$stats['x'] .= '],';

					}

				}

				return $stats;

			}

			return [];

		}

		/**
		 * Loads dependents for the charts.
		 *
		 * @param $chart array
		 * @param $stats array
		 *
		 * @return string
		 *
		 * @since 2.0.0
		 */
		private static function display_line_chart( array $chart, array $stats ) : string {

			if ( ! isset( $chart['columns'] ) || ! is_array( $stats ) ) {
				return '';
			}

			$colors = $types = $groups = $columns = '';

			$count = count( $chart['columns'] );
			$num   = 0;

			foreach ( $chart['columns'] as $c ) {

				if ( isset( $c['color'] ) ) {
					$colors .= '"' . esc_js( $c['label'] ) . '": "' . esc_js( $c['color'] ) . '",';
				}

				if ( isset( $c['type'] ) ) {
					$types .= '"' . esc_js( $c['label'] ) . '": "' . esc_js( $c['type'] ) . '",';
				}

				$groups .= '"' . esc_js( $c['label'] ) . '"';

				$num ++;

				if ( $count != $num ) {
					$groups .= ',';
				}

				$columns .= $stats[ $c['id'] ];

			}

			$var = str_replace( '-', '_', $chart['id'] );

			return '     
            var ' . esc_js( $var ) . ' = c3.generate({
                bindto: "#' . esc_js( $chart['id'] ) . '",
                data: {
                    x: "x",
                    xFormat: "%Y-%m-%d",
                    columns: [
                        ' . $stats['x'] . '
                        ' . $columns . '
                    ],
                    colors: {' . $colors . '},
                    types: {' . $types . '},
                },
                axis: {
                    x: {
                        type: "timeseries",
                        localtime: true,
                        tick: {
                            format: "%m-%d",
                            rotate: -45,
                            multiline: false
                        }
                      },
                    y: {
                        label: {
                            text: "' . esc_js( $chart['y-label'] ) . '",
                            position: "outer-middle"
                        },
                    }
                }
            });
        ';

		}

		/**
		 * Loads dependents for the charts.
		 *
		 * @param array $chart
		 * @param array $stats
		 *
		 * @return string
		 *
		 * @since 2.0.0
		 */
		private static function display_pie_chart( array $chart, array $stats ) : string {

			if ( ! isset( $chart['columns'] ) || ! is_array( $stats ) ) {
				return '';
			}

			$columns = $colors = '';

			foreach ( $chart['columns'] as $c ) {

				if ( isset( $c['color'] ) ) {

					$colors .= '"' . esc_js( $c['label'] ) . '": "' . esc_js( $c['color'] ) . '",';

				}

				$columns .= $stats[ $c['id'] ];

			}

			$var = str_replace( '-', '_', $chart['id'] );

			return '
            var ' . esc_js( $var ) . ' = c3.generate({
                bindto: "#' . esc_js( $chart['id'] ) . '",
                data: {
                    columns: [' . $columns . '],
                    type : "pie",
                    colors: {' . $colors . '},
                },
                pie: {
                    label: {
                        format: function (value, ratio, id) {
                            return d3.format("")(value);
                        }
                    }
                },
                tooltip: {
                    format: {
                        value: function (value, ratio, id) {
                            var percent = Math.round( 1000 * ratio ) / 10;
                            return ( percent + "% (" + value + ")" );
                        }
                    }
                }
            });
        ';

		}

		/**
		 * Displays the guage chart
		 *
		 * @param array $chart
		 * @param array $stats
		 *
		 * @return string
		 *
		 * @since 2.0.0
		 */
		private static function display_guage_chart( array $chart, array $stats ) : string {

			$total = $stats[ $chart['columns'][0]['id'] ];
			$total = ( substr( $total, - 1 ) == ',' ) ? substr( $total, 0, - 1 ) : $total;

			$used = $stats[ $chart['columns'][1]['id'] ];
			$used = ( substr( $used, - 1 ) == ',' ) ? substr( $used, 0, - 1 ) : $used;

			$var = str_replace( '-', '_', $chart['id'] );

			return '
        var total = ' . esc_js( $total ) . ';
        var used = ' . esc_js( $used ) . ';
        var ntotal = 0;
        var nused = 0;

        total.forEach(function(t) {

            if ( isNaN(t) == false ) {
                ntotal = ntotal + t;
            }

        });

        used.forEach(function(u) {

            if ( isNaN(u) == false ) {
                nused = nused + u;
            }

        });

        var percent = nused / ntotal;
        var percent = Math.round( 1000 * percent ) / 10;

        var ' . esc_js( $var ) . ' = c3.generate({
                bindto: "#' . esc_js( $chart['id'] ) . '",
                data: {
                    columns: [
                        ["' . esc_js( $chart['columns'][1]['label'] ) . '", percent]
                    ],
                    type: "gauge"
                },
                color: {
                    pattern: ["#FF0000", "#F97600", "#F6C600", "#60B044"],
                    threshold: {
                        values: [85, 90, 95, 100]
                    }
                },
                size: {
                    height: 200
                }
            });';

		}

		/**
		 * Loads dependents for the charts.
		 *
		 * @since 2.0.0
		 */
		static function dependencies() : void {

			/**
			 * Get updated css/JS here: https://cdnjs.com/libraries
			 * Current Installed Version
			 * https://cdnjs.cloudflare.com/ajax/libs/c3/0.7.0/c3.min.css
			 * https://cdnjs.cloudflare.com/ajax/libs/d3/5.9.2/d3.min.js
			 * https://cdnjs.cloudflare.com/ajax/libs/c3/0.7.0/c3.min.js
			 */

			echo '
        <link rel="stylesheet" href="' . esc_url( SECSAFE_URL_ADMIN_ASSETS . 'css/c3.min.css?ver=' . SECSAFE_VERSION ) . '" />
        <script src="' . esc_url( SECSAFE_URL_ADMIN_ASSETS . 'js/d3.min.js?ver=' . SECSAFE_VERSION ) . '"></script>
        <script src="' . esc_url( SECSAFE_URL_ADMIN_ASSETS . 'js/c3.min.js?ver=' . SECSAFE_VERSION ) . '"></script>
        ';

		}

	}
