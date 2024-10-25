<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	/**
	 * Class AdminPageFirewall
	 * @package SecuritySafe
	 * @since  0.2.0
	 */
	class AdminPageFirewall extends AdminPage {

		/**
		 * This tab displays the IP addresses black and white listed.
		 *
		 * @since  2.0.0
		 *
		 * @return string
		 */
		function tab_allow_deny_ips() : string {

			require_once( SECSAFE_DIR_ADMIN_TABLES . '/TableAllowDeny.php' );

			ob_start();

			$table = new TableAllowDeny();

			$table->add_ip();
			$table->prepare_items();
			$table->check_whitelist();
			$table->extra_tools();
			$table->display();

			return ob_get_clean();

		}

		/**
		 * This tab displays threats and blocks.
		 *
		 * @since  2.2.0
		 *
		 * @return string
		 */
		public function tab_threats() : string {

			require_once( SECSAFE_DIR_ADMIN_TABLES . '/TableBlocked.php' );

			ob_start();

			$table = new TableBlocked();
			$table->prepare_items();
			$table->display_charts();
			$table->search_box( __( 'Search threats', SECSAFE_TRANSLATE ), 'log' );
			$table->display();

			return ob_get_clean();

		}

		/**
		 * This sets the variables for the page.
		 *
		 * @since  0.1.0
		 *
		 * @return void
		 */
		protected function set_page() : void {

			$this->slug        = SECSAFE_SLUG . '-firewall';
			$this->title       = __( 'Firewall', SECSAFE_TRANSLATE );
			$this->description = __( 'This area provides you the ability to log activity on the site and block future attempts if desired.', SECSAFE_TRANSLATE );

			$this->tabs[] = [
				'id'               => 'blocked',
				'label'            => __( 'Threats', SECSAFE_TRANSLATE ),
				'title'            => __( 'Detected Threats', SECSAFE_TRANSLATE ),
				'heading'          => false,
				'intro'            => false,
				'classes'          => [ 'full' ],
				'content_callback' => 'tab_threats',
			];

			$this->tabs[] = [
				'id'               => 'allow_deny',
				'label'            => __( 'Allow / Deny IP', SECSAFE_TRANSLATE ),
				'title'            => __( 'Allow / Deny IP Addresses', SECSAFE_TRANSLATE ),
				'heading'          => false,
				'intro'            => false,
				'classes'          => [ 'full' ],
				'content_callback' => 'tab_allow_deny_ips',
			];

		}

	}
