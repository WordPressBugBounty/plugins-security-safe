<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	/**
	 * Class AdminPage
	 * @package SecuritySafe
	 */
	class AdminPage {

		public $title = 'Page Title';
		public $description = 'Description of page.';
		public $slug = '';
		public $tabs = [];

		/**
		 * Contains all the admin message values for the page.
		 * @var array
		 */
		public array $messages = [];
		protected array $page_settings = [];

		/**
		 * AdminPage constructor.
		 *
		 * @param array $page_settings
		 */
		function __construct( array $page_settings ) {

			$this->page_settings = $page_settings;

			// Prevent Caching
			Janitor::prevent_caching();

			// Set page variables
			$this->set_page();

		}

		/**
		 * Placeholder intended to be used by pages to override variables.
		 * @since  0.1.0
		 */
		protected function set_page() {

			// This is overwritten by specific page.

		}

		/**
		 * Displays all the tabs set by the specific page
		 *
		 * @since  0.2.0
		 */
		public function display_tabs() : void {

			if ( ! empty( $this->tabs ) ) {

				$html = '<h2 class="nav-tab-wrapper">';
				$num  = 1;
				$tab = REQUEST::key('tab');

				foreach ( $this->tabs as $t ) {

					if ( is_array( $t ) ) {

						$classes = 'nav-tab';

						// Add Active Class To Active Tab : Default First Tab
						if ( $tab === $t['id'] || ( '' === $tab && $num == 1 ) ) {
							$classes .= ' nav-tab-active';
						}

						$html .= '<a href="?page=' . esc_attr( $this->slug . '&tab=' . $t['id'] ) . '" class="' . esc_attr( $classes ) . '">' . esc_html( $t['label'] ) . '</a>';

						$num ++;

					}

				}

				$html .= '</h2>';

				echo $html;

			}

		}

		/**
		 * Display All Tabbed Content
		 *
		 * @since  0.2.0
		 */
		public function display_tabs_content() : void {

			if ( ! empty( $this->tabs ) ) {

				$num = 1;

				$html = '';

				$tab = REQUEST::key('tab');

				foreach ( $this->tabs as $t ) {

					if ( $tab == $t['id'] || ( '' === $tab && $num == 1 ) ) {

						$classes = 'tab-content';

						// Add Active Class To Active Tab : Default First Tab Content
						if ( $tab == $t['id'] || ( '' === $tab && $num == 1 ) ) {
							$classes .= ' active';
						}

						// Adds Custom Classes
						if ( isset( $t['classes'] ) ) {

							if ( is_array( $t['classes'] ) ) {

								foreach ( $t['classes'] as $class ) {

									$classes .= ' ' . $class;

								}

							} else {

								$classes .= ' ' . $t['classes'];

							}

						}

						$html .= '<div id="' . esc_attr( $t['id'] ) . '" class="' . esc_attr( $classes ) . '">';

						// Display Title
						if ( isset( $t['title'] ) && $t['title'] ) {

							$html .= '<h2>' . esc_html( $t['title'] ) . '</h2>';

						}

						// Display Heading Text
						if ( isset( $t['heading'] ) && $t['heading'] ) {

							$html .= '<p class="new-description description">' . esc_html( $t['heading'] ) . '</p>';

						}

						// Display Intro Text
						if ( isset( $t['intro'] ) && $t['intro'] ) {

							/**
							 * @todo Need to sanitize this in a way that doesn't break the intro
							 */
							$html .= '<p>' . $t['intro'] . '</p>';

						}

						// Display Page Messages As A Log
						$html .= $this->display_messages();

						// Run Callback Method To Display Content
						if ( isset( $t['content_callback'] ) && $t['content_callback'] ) {

							$content = $t['content_callback'];
							$html    .= $this->$content();

						}

						$html .= '</div><!-- #' . esc_html( $t['id'] ) . ' -->';

						$num ++;

					}

				}

				echo $html;

			}

		}

		/**
		 * Displays this page's messages in a log format. Only used on file permissions page.
		 *
		 * @return string
		 *
		 * @since 1.1.0
		 */
		private function display_messages() : string {

			$html = '';

			$page = REQUEST::key('page');
			$tab = REQUEST::key('tab');

			if (
				isset( $_POST ) &&
				! empty( $_POST ) &&
				$page == SECSAFE_SLUG . '-files' &&
				$tab != 'settings'
			) {

				$html = '<h3>' . esc_html__( 'Process Log', SECSAFE_TRANSLATE ) . '</h3>
					<p><textarea style="width: 100%; height: 120px;">';

				if ( ! empty( $this->messages ) ) {

					foreach ( $this->messages as $m ) {

						// Display Messages
						$html .= ( $m[1] == 3 ) ? "\n!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! \n" : '';
						$html .= '- ' . esc_html( $m[0] ) . "\n";
						$html .= ( $m[1] == 3 ) ? "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! \n\n" : '';

					}

				} else {

					$html .= __( 'No changes were made to file permissions.', SECSAFE_TRANSLATE );

				}// ! empty()

				$html .= '</textarea></p>';

			}

			return $html;

		}

		/**
		 * Creates the opening and closing tags for the form-table
		 *
		 * @param string $rows
		 *
		 * @return string
		 *
		 * @since  0.2.0
		 */
		protected function form_table( $rows ) : string {

			return '<table class="form-table">' . $rows . '</table>';

		}

		/**
		 * Creates a new section for a form-table
		 *
		 * @param string $title
		 * @param string $desc
		 *
		 * @return string
		 *
		 * @since  0.2.0
		 */

		protected function form_section( string $title, string $desc ) : string {

			// Create ID to allow links to specific areas of admin
			$id = str_replace( ' ', '-', trim( strtolower( $title ) ) );

			$html = '<h3 id="' . esc_attr( $id ) . '">' . esc_html( $title ) . '</h3>';
			$html .= '<p>' . esc_html( $desc ) . '</p>';

			return $html;

		}

		/**
		 * Displays form checkbox for a settings page.
		 *
		 * @param array $page_options An array of setting values specific to the particular page. This is not the full array of settings.
		 * @param string $name The name of the checkbox which corresponds with the setting name in the database.
		 * @param string $slug The value for the settings in the database.
		 * @param string $short_desc The text that is displayed to the right on the checkbox.
		 * @param string $long_desc The description text displayed below the title.
		 * @param string $classes
		 * @param bool $disabled
		 *
		 * @return string
		 *
		 * @since  0.1.0
		 */
		protected function form_checkbox( array $page_options, string $name, string $slug, string $short_desc, string $long_desc, string $classes = '', bool $disabled = false ) : string {

			$html = '<tr class="form-checkbox ' . $classes . '">';

			if ( is_array( $page_options ) && $slug && $short_desc ) {

				$html .= $this->row_label( $name );
				$html .= '<td>';

				$checked  = ( isset( $page_options[ $slug ] ) && $page_options[ $slug ] == '1' ) ? 'CHECKED' : '';
				$disabled = ( $disabled ) ? 'DISABLED' : '';

				/**
				 * @todo  Fix: Had to remove esc_html for short desc
				 */
				$html .= '<label><input name="' . esc_attr( $slug ) . '" type="checkbox" value="1" ' . esc_html( $checked ) . ' ' . esc_html( $disabled ) . '/>' . $short_desc . '</label>';

				if ( $long_desc ) {
					/**
					 * @todo  Fix: Had to remove esc_html for long desc
					 */
					$html .= '<p class="description">' . $long_desc . '</p>';

				}

				// Testing Only
				//$html .= 'Value: ' . $page_options[ $slug ];

				$html .= '</td>';

			} else {

				$html .= '<td colspan="2"><p>' . esc_html__( 'Error: There are parameters missing to properly display checkbox.', SECSAFE_TRANSLATE ) . '</p></td>';

			}

			$html .= '</tr>';

			return $html;

		}

		/**
		 * Adds row label
		 *
		 * @param string $name
		 *
		 * @return string
		 */
		protected function row_label( string $name ) : string {

			$html = '<th scope="row">';

			if ( $name ) {

				$html .= '<label>' . esc_html( $name ) . '</label>';

			}

			$html .= '</th>';

			return $html;

		}

		/**
		 * Adds custom message in a table row
		 *
		 * @param string $message
		 * @param string $class
		 * @param string $classes
		 *
		 * @return string
		 */
		protected function form_text( string $message, string $class = '', string $classes = '' ) : string {

			$html = '<tr class="form-text ' . esc_attr( $classes ) . '">';

			// @todo Need to make sure message is sanitized when form_text is called. $message needs to be refactored to be sanitized here instead
			$html .= '<td colspan="2"><p class="' . esc_attr( $class ) . '">' . $message . '</p></td>';

			$html .= '</tr>';

			return $html;

		}

		/**
		 * Adds an input row
		 *
		 * @param array  $page_options
		 * @param string $name
		 * @param string $slug
		 * @param string $placeholder
		 * @param string $long_desc
		 * @param string $styles
		 * @param string $classes
		 * @param bool   $required
		 *
		 * @return string
		 */
		protected function form_input( array $page_options, string $name, string $slug, string $placeholder, string $long_desc, string $styles = '', string $classes = '', bool $required = false ) : string {

			$html = '<tr class="form-input ' . esc_attr( $classes ) . '">';

			if ( is_array( $page_options ) && $slug ) {

				$value = ( isset( $page_options[ $slug ] ) ) ? $page_options[ $slug ] : '';

				$html .= $this->row_label( $name );

				$html .= '<td><input type="text" name="' . esc_attr( $slug ) . '" placeholder="' . esc_attr( $placeholder ) . '" value="' . esc_attr( $value ) . '" style="' . esc_attr( $styles ) . '">';

				if ( $long_desc ) {

					$html .= '<p class="description">' . esc_html( $long_desc ) . '</p>';

				}

				$html .= '</td>';

			} else {

				$html .= '<td>' . esc_html( sprintf( __( 'Error: There is an issue displaying this form field: %s.', SECSAFE_TRANSLATE ), 'input' ) ) . '</td>';

			}

			$html .= '</tr>';

			return $html;

		}

		/**
		 * Adds a select option row
		 *
		 * @param array  $page_options
		 * @param string $name
		 * @param string $slug
		 * @param array  $options
		 * @param string $long_desc
		 * @param string $classes
		 * @param string $default
		 * @param bool   $disabled
		 * @param bool   $input_only
		 *
		 * @return string
		 */
		protected function form_select( array $page_options, string $name, string $slug, array $options, string $long_desc, string $classes = '', string $default = '', bool $disabled = false, bool $input_only = false ) : string {

			$html = ( $input_only ) ? '' : '<tr class="form-select ' . esc_attr( $classes ) . '">';

			if ( is_array( $page_options ) && $slug && $options ) {

				$use_default = ! isset( $page_options[ $slug ] ) || $page_options[ $slug ] == '' || $disabled;

				if ( ! $input_only ) {

					$html .= $this->row_label( $name );
					$html .= '<td>';

				}

				$html .= '<select name="' . esc_attr( $slug ) . '">';

				if ( is_array( $options ) ) {

					foreach ( $options as $value => $label ) {

						$selected       = ( $use_default && $default == $value ) ? 'SELECTED' : '';
						$selected       = ( ! $use_default && isset( $page_options[ $slug ] ) && $page_options[ $slug ] == $value ) ? ' SELECTED' : $selected;
						$disable_option = ( $disabled && $default != $value ) ? 'DISABLED' : '';

						$html .= '<option value="' . esc_attr( $value ) . '" ' . esc_html( $selected ) . ' ' . esc_html( $disable_option ) . '>' . esc_html( $label ) . '</option>';

					}

				} else {

					$html .= '<option>' . esc_html__( 'Error: Form field "select" is not an array.', SECSAFE_TRANSLATE ) . '</option>';

				}

				$html .= '</select>';

				if ( ! $input_only ) {

					if ( $long_desc ) {

						$html .= '<p class="description">' . $long_desc . '</p>';

					}

					$html .= '</td>';

				}

			} else {

				$html .= '<td colspan="2">' . esc_html( sprintf( __( 'Error: There is an issue displaying this form field: %s.', SECSAFE_TRANSLATE ), 'select' ) ) . '</td>';

			}

			$html .= ( $input_only ) ? '' : '</tr>';

			return $html;

		}

		/**
		 * Adds a textarea input row
		 *
		 * @param array  $page_options
		 * @param string $name
		 * @param string $slug
		 * @param string $long_desc
		 * @param string $classes
		 * @param string $default
		 * @param bool   $disabled
		 * @param bool   $input_only
		 *
		 * @return string
		 */
		protected function form_textarea( array $page_options, string $name, string $slug, string $long_desc, string $classes = '', string $default = '', bool $disabled = false, bool $input_only = false ): string {

			$html = ( $input_only ) ? '' : '<tr class="form-textarea ' . esc_attr( $classes ) . '">';

			$value = $page_options[ $slug ] ?? $default;

			if ( ! $input_only ) {

				$html .= $this->row_label( $name );
				$html .= '<td>';

			}

			$html .= '<textarea name="' . esc_attr( $slug ) . '" style="width 300px; height: 150px">' . esc_textarea( $value ) . '</textarea>';

			if ( $default ) {
				$html .= '<br><a href="#_' . esc_attr( $slug ) . '-default" class="reset-textarea" style="font-size: 12px;">Reset To Default Value</a>';
				$html .= '<textarea id="_' . esc_attr( $slug ) . '-default" name="_' . esc_attr( $slug ) . '-default" style="display:none">' . esc_textarea( $default ) . '</textarea>';
			}

			if ( ! $input_only ) {

				if ( $long_desc ) {

					$plugins_allowedtags = [
						'a' => [
							'href' => [],
							'title' => [],
						],
						'abbr' => [ 'title' => [] ],
						'acronym' => [ 'title' => [] ],
						'code' => [],
						'em' => [],
						'strong' => [],
					];

					$html .= '<p class="description">' . wp_kses( $long_desc, $plugins_allowedtags ) . '</p>';

				}

				$html .= '</td>';

			}

			$html .= ( $input_only ) ? '' : '</tr>';

			return $html;

		}

		/**
		 *Add a row with custom HTML
		 *
		 * @param string $classes
		 * @param string $content
		 *
		 * @return string
		 *
		 * @since 2.4.0
		 */
		protected function row_custom( string $classes, string $content ) : string {

			$html = '<tr class="' . esc_attr( $classes ) . '">';
			$html .= ( $content ) ? $content : '';
			$html .= '</tr>';

			return $html;

		}

		/**
		 * Creates a File Upload Field
		 *
		 * @param string $text
		 * @param string $name
		 * @param string $long_desc
		 * @param string $classes
		 *
		 * @return string
		 */
		protected function form_file_upload( string $text, string $name, string $long_desc = '', string $classes = '' ) : string {

			$html = '<tr class="form-file-upload ' . esc_attr( $classes ) . '">';
			$html .= '<div class="file-upload-wrap cf"><label>' . esc_html( $text ) . '</label><input name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '" type="file" class="file-input"><input type="button" class="file-select" value="' . esc_attr__( 'Choose File', SECSAFE_TRANSLATE ) . '"><span class="file-selected">' . esc_html__( 'No File Chosen', SECSAFE_TRANSLATE ) . '</span>';
			$html .= '</div></tr>';

			return $html;

		}

		/**
		 * Creates Table Row For A Button
		 *
		 * @since  0.3.0
		 *
		 * @param string $text
		 * @param string $type
		 * @param string $value
		 * @param string $long_desc
		 * @param string $classes
		 * @param bool   $label
		 * @param string $name
		 *
		 * @return string
		 */
		protected function form_button( string $text, string $type, string $value, string $long_desc = '', string $classes = '', bool $label = true, string $name = '' ) : string {

			$html = '<tr class="form-button ' . esc_attr( $classes ) . '">';

			if ( $label ) {

				$html .= $this->row_label( $text );

			}

			$html .= '<td>';
			$html .= $this->button( $text, $type, $value, $name );

			if ( $long_desc ) {

				$html .= '<p class="description">' . esc_html( $long_desc ) . '</p>';

			}

			$html .= '</td>';
			$html .= '</tr>';

			return $html;

		}

		/**
		 * Return HTML for Submit Button
		 *
		 * @param string $text
		 * @param string $type
		 * @param string $value
		 * @param string $name
		 *
		 * @return string
		 */
		protected function button( string $text = '', string $type = '', string $value = '', string $name = '' ) : string {

			// Default Values
			$text  = ( $text ) ? $text : __( 'Save Changes', SECSAFE_TRANSLATE );
			$type  = ( $type ) ? $type : 'submit';
			$value = ( $value ) ? $value : $text;
			$name  = ( $name ) ? $name : $type;

			$html    = '<p class="' . esc_attr( $type ) . '">';
			$classes = 'button ';

			if ( $type == 'submit' ) {

				$classes .= 'button-primary';
				$html    .= '<input type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $type ) . '" class="' . esc_attr( $classes ) . '" value="' . esc_attr( $value ) . '" />';

			} elseif ( $type == 'link' ) {

				$classes .= 'button-secondary';
				$html    .= '<a href="' . esc_url( $value ) . '" class="' . esc_attr( $classes ) . '">' . esc_html( $text ) . '</a>';

			} elseif ( $type == 'link-delete' ) {

				$classes .= 'button-secondary button-link-delete';
				$html    .= '<a href="' . esc_url( $value ) . '" class="' . esc_attr( $classes ) . '">' . esc_html( $text ) . '</a>';

			}

			$html .= '</p>';

			return $html;

		}

	}
