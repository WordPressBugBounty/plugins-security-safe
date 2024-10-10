<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	/**
	 * Class AdminPageContent
	 * @package SecuritySafe
	 * @since  0.2.0
	 */
	class AdminPageContent extends AdminPage {

		/**
		 * This tab displays file settings.
		 * @since  0.2.0
		 */
		function tab_settings() {

			$html = '';

			// Shutoff Switch - All Content Policies
			$classes = ( $this->page_settings['on'] ) ? '' : 'notice-warning';

			$rows = $this->form_select(
				$this->page_settings,
				__( 'Content Policies', SECSAFE_TRANSLATE ),
				'on',
				[ '0' => __( 'Disabled', SECSAFE_TRANSLATE ), '1' => __( 'Enabled', SECSAFE_TRANSLATE ) ],
				__( 'If you experience a problem, you may want to temporarily turn off all content policies at once to troubleshoot the issue. Be sure to clear your cache as well.', SECSAFE_TRANSLATE ),
				$classes
			);

			$html .= $this->form_table( $rows );

			// Copyright Protection
			$html .= $this->form_section(
				__( 'Copyright Protection', SECSAFE_TRANSLATE ),
				__( 'Copyright protection is meant to deter the majority of users from copying your content. These settings do not affect the admin area.', SECSAFE_TRANSLATE )
			);

			$rows = $this->form_checkbox(
				$this->page_settings,
				__( 'Highlight Text', SECSAFE_TRANSLATE ),
				'disable_text_highlight',
				__( 'Disable Text Highlighting', SECSAFE_TRANSLATE ),
				__( 'Prevent users from highlighting your content text.', SECSAFE_TRANSLATE )
			);

			$rows .= $this->form_checkbox(
				$this->page_settings,
				__( 'Right-Click', SECSAFE_TRANSLATE ),
				'disable_right_click',
				__( 'Disable Right-Click', SECSAFE_TRANSLATE ),
				__( 'Prevent users from right-clicking on your site to save images or copy text.', SECSAFE_TRANSLATE )
			);

			$html .= $this->form_table( $rows );

			// Password Protection
			$html .= $this->form_section(
				__( 'Password Protected Content', SECSAFE_TRANSLATE ),
				__( 'Sometimes, it is necessary to password protect content for special access without requiring a user to log in. The settings below enhance this WordPress feature.', SECSAFE_TRANSLATE )
			);

			$rows = $this->form_checkbox(
				$this->page_settings,
				__( 'Hide Posts', SECSAFE_TRANSLATE ),
				'hide_password_protected_posts',
				__( 'Hide All Protected Posts', SECSAFE_TRANSLATE ),
				__( 'Prevent password protected content from being listed in the blog, search results, and any other public areas. (only affects the loop)', SECSAFE_TRANSLATE )
			);

			$html .= $this->form_table( $rows );

			// @todo need to convert to esc_html__() but it would break the <b> tags. Need to change this.
			$html .= '<p>' . __( '<b>NOTICE:</b> Be sure to clear your cache after changing these settings.', SECSAFE_TRANSLATE ) . '</p>';

			// Save Button
			$html .= $this->button( __( 'Save Settings', SECSAFE_TRANSLATE ) );

			return $html;

		}

		/**
		 * This tab displays the 404 error log.
		 * @since  2.0.0
		 */
		function tab_404s() {

			require_once( SECSAFE_DIR_ADMIN_TABLES . '/Table404s.php' );

			ob_start();

			$table = new Table404s();
			$table->prepare_items();
			$table->display_charts();
			$table->search_box( __( 'Search 404s', SECSAFE_TRANSLATE ), 'log' );
			$table->display();

			return ob_get_clean();

		}

		/**
		 * This sets the variables for the page.
		 * @since  0.1.0
		 */
		protected function set_page() {

			$this->slug = SECSAFE_SLUG . '-content';

			$this->title       = __( 'Content Protection', SECSAFE_TRANSLATE );
			$this->description = __( 'Deter visitors from stealing your content.', SECSAFE_TRANSLATE );

			$this->tabs[] = [
				'id'               => '404s',
				'label'            => __( '404 Errors', SECSAFE_TRANSLATE ),
				'title'            => __( '404 Error Log', SECSAFE_TRANSLATE ),
				'heading'          => false,
				'intro'            => false,
				'classes'          => [ 'full' ],
				'content_callback' => 'tab_404s',
			];

			$this->tabs[] = [
				'id'               => 'settings',
				'label'            => __( 'Settings', SECSAFE_TRANSLATE ),
				'title'            => __( 'Content Settings', SECSAFE_TRANSLATE ),
				'heading'          => false,
				'intro'            => false,
				'content_callback' => 'tab_settings',
			];

		}

	}
