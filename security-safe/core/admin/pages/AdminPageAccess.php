<?php

	namespace SovereignStack\SecuritySafe;

	// Prevent Direct Access
	( defined( 'ABSPATH' ) ) || die;

	/**
	 * Class AdminPageAccess
	 * @package SecuritySafe
	 * @since  0.2.0
	 */
	class AdminPageAccess extends AdminPage {

		/**
		 * This populates all the metaboxes for this specific page.
		 * @since  0.2.0
		 */
		function tab_settings() {

			$disabled = true;

			$upgrade = ' <a href="' . SECSAFE_URL_MORE_INFO_PRO . '">' . esc_html__( 'Upgrade to control this setting.', SECSAFE_TRANSLATE ) . '</a>';

			if ( security_safe()->can_use_premium_code() ) {

				$disabled = false;
				$upgrade  = '';

			}

			$default_settings = Plugin::get_page_settings_min( 'access' );

			$html = '';

			// Shutoff Switch - All Access Policies
			$classes = ( $this->page_settings['on'] ) ? '' : 'notice-warning';
			$rows    = $this->form_select(
				$this->page_settings,
				__( 'User Access Policies', SECSAFE_TRANSLATE ),
				'on',
				[ '0' => __( 'Disabled', SECSAFE_TRANSLATE ), '1' => __( 'Enabled', SECSAFE_TRANSLATE ) ],
				__( 'If you experience a problem, you may want to temporarily turn off all user access policies at once to troubleshoot the issue.', SECSAFE_TRANSLATE ),
				$classes
			);
			$html    .= $this->form_table( $rows );

			// Login Security
			$html .= $this->form_section( __( 'Login Form', SECSAFE_TRANSLATE ), '' );

			$rows = $this->form_checkbox(
				$this->page_settings,
				__( 'Login Errors', SECSAFE_TRANSLATE ),
				'login_errors',
				__( 'Make login errors generic.', SECSAFE_TRANSLATE ),
				__( 'When someone attempts to log in, by default, the error messages will tell the user that the password is incorrect or that the username is not valid. This exposes too much information to the potential intruder.', SECSAFE_TRANSLATE )
			);

			$rows .= $this->form_checkbox(
				$this->page_settings,
				__( 'Password Reset', SECSAFE_TRANSLATE ),
				'login_password_reset',
				__( 'Disable Password Reset', SECSAFE_TRANSLATE ),
				__( 'If you are the only user of the site, you may want to disable this feature as you have access to the database and hosting control panel.', SECSAFE_TRANSLATE )
			);

			$rows .= $this->form_checkbox(
				$this->page_settings,
				__( 'Remember Me', SECSAFE_TRANSLATE ),
				'login_remember_me',
				__( 'Disable Remember Me Checkbox', SECSAFE_TRANSLATE ),
				__( 'If the device that uses the remember me feature gets stolen, then the person in possession can now log in.', SECSAFE_TRANSLATE )
			);
			$html .= $this->form_table( $rows );

			// Brute Force
			$html .= $this->form_section(
				__( 'Brute Force Protection', SECSAFE_TRANSLATE ),
				__( 'Brute Force login attempts are repetitive attempts to gain access to your site using the login form.', SECSAFE_TRANSLATE )
			);

			// Shutoff Switch - All Firewall Policies
			$classes = ( $this->page_settings['on'] ) ? '' : 'notice-warning';

			$rows = $this->form_checkbox(
				$this->page_settings,
				__( 'Block Usernames', SECSAFE_TRANSLATE ),
				'block_usernames',
				__( 'Block specific usernames from logging in.', SECSAFE_TRANSLATE ),
				__( 'Define usernames to block below.', SECSAFE_TRANSLATE )
			);

			$rows .= $this->form_textarea(
				$this->page_settings,
				__( 'Blocked Usernames', SECSAFE_TRANSLATE ),
				'block_usernames_list',
				'An attempt to login with these usernames, will be blocked by the firewall. Each username must be on a separate line.',
				'',
				$default_settings['block_usernames_list'],
				false );

			$rows .= $this->form_select(
				$this->page_settings,
				__( 'Limit Login Attempts', SECSAFE_TRANSLATE ),
				'autoblock',
				[ '0' => __( 'Disabled', SECSAFE_TRANSLATE ), '1' => __( 'Enabled', SECSAFE_TRANSLATE ) ],
				'',
				'',
				$default_settings['autoblock'],
				false );

			$rows .= $this->row_custom( 'lockout-condition', '<th scope="row"><label>Lockout Condition:</label></th>' );

			$content = '<td colspan="2"><p>' . esc_html__( 'Block an IP address after', SECSAFE_TRANSLATE ) . ' ';
			$content .= $this->form_select(
				$this->page_settings,
				__( 'Detection Threshold', SECSAFE_TRANSLATE ),
				'autoblock_threat_score',
				[ 3 => '3', 5 => '5', 10 => '10', 15 => '15', 20 => '20', 25 => '25' ],
				'',
				'',
				$default_settings['autoblock_threat_score'],
				false, true );

			$content .= $this->form_select(
				$this->page_settings,
				__( 'Lockout Method', SECSAFE_TRANSLATE ),
				'autoblock_method',
				[
					1 => __( 'Failed Logins', SECSAFE_TRANSLATE ),
					2 => __( 'Threats', SECSAFE_TRANSLATE ),
					3 => __( 'Threat Score', SECSAFE_TRANSLATE ),
				],
				'',
				'',
				$default_settings['autoblock_method'],
				$disabled, true );

			$content .= ' within ';
			$content .= $this->form_select(
				$this->page_settings,
				__( 'Detection Timespan', SECSAFE_TRANSLATE ),
				'autoblock_timespan',
				[
					1  => __( '1 minute', SECSAFE_TRANSLATE ),
					2  => sprintf( __( '%d minutes', SECSAFE_TRANSLATE ), 2 ),
					3  => sprintf( __( '%d minutes', SECSAFE_TRANSLATE ), 3 ),
					4  => sprintf( __( '%d minutes', SECSAFE_TRANSLATE ), 4 ),
					5  => sprintf( __( '%d minutes', SECSAFE_TRANSLATE ), 5 ),
					10 => sprintf( __( '%d minutes', SECSAFE_TRANSLATE ), 10 ),
				],
				'',
				'',
				$default_settings['autoblock_timespan'],
				false,
				true );

			$content .= '.</p><br /><p class="description">';
			$content .= ( security_safe()->can_use_premium_code() ) ? '' : '<a href="' . esc_url( SECSAFE_URL_MORE_INFO_PRO ) . '">' . esc_html__( 'Upgrade:', SECSAFE_TRANSLATE ) . '</a> ';
			$content .= esc_html__( 'Block IP addresses sooner using "threats" or "threats score" setting above.', SECSAFE_TRANSLATE ) . '</p></td>';

			$rows .= $this->row_custom( 'lockout-condition', $content );

			$rows .= $this->form_select(
				$this->page_settings,
				__( 'First Lockout', SECSAFE_TRANSLATE ),
				'autoblock_ban_1',
				[
					5  => sprintf( __( '%d minutes', SECSAFE_TRANSLATE ), 5 ),
					10 => sprintf( __( '%d minutes', SECSAFE_TRANSLATE ), 10 ),
					15 => sprintf( __( '%d minutes', SECSAFE_TRANSLATE ), 15 ),
					30 => sprintf( __( '%d minutes', SECSAFE_TRANSLATE ), 30 ),
					45 => sprintf( __( '%d minutes', SECSAFE_TRANSLATE ), 45 ),
				],
				sprintf( __( '%d minutes is the default value.', SECSAFE_TRANSLATE ), $default_settings['autoblock_ban_1'] ),
				'',
				$default_settings['autoblock_ban_1'],
				false );

			$rows .= $this->form_select(
				$this->page_settings,
				__( 'Second Lockout', SECSAFE_TRANSLATE ),
				'autoblock_ban_2',
				[
					1  => __( '1 hour', SECSAFE_TRANSLATE ),
					2  => sprintf( __( '%d hours', SECSAFE_TRANSLATE ), 2 ),
					3  => sprintf( __( '%d hours', SECSAFE_TRANSLATE ), 3 ),
					4  => sprintf( __( '%d hours', SECSAFE_TRANSLATE ), 4 ),
					6  => sprintf( __( '%d hours', SECSAFE_TRANSLATE ), 6 ),
					12 => sprintf( __( '%d hours', SECSAFE_TRANSLATE ), 12 ),
				],
				__( '1 hour is the default value. 4 hours is recommended.', SECSAFE_TRANSLATE ),
				'',
				$default_settings['autoblock_ban_2'],
				false );

			$rows .= $this->form_select(
				$this->page_settings,
				__( 'Third Lockout', SECSAFE_TRANSLATE ),
				'autoblock_ban_3',
				[
					1   => __( '1 day', SECSAFE_TRANSLATE ),
					2   => sprintf( __( '%d days', SECSAFE_TRANSLATE ), 2 ),
					3   => sprintf( __( '%d days', SECSAFE_TRANSLATE ), 3 ),
					7   => __( '1 week', SECSAFE_TRANSLATE ),
					14  => sprintf( __( '%d weeks', SECSAFE_TRANSLATE ), 2 ),
					30  => __( '1 month', SECSAFE_TRANSLATE ),
					90  => __( '3 months', SECSAFE_TRANSLATE ),
					180 => __( '6 months', SECSAFE_TRANSLATE ),
				],
				__( '1 day is the default value. 3 days or greater is recommended.', SECSAFE_TRANSLATE ) . $upgrade,
				'',
				$default_settings['autoblock_ban_3'],
				$disabled );

			$rows .= $this->form_checkbox(
				$this->page_settings,
				__( 'XML-RPC', SECSAFE_TRANSLATE ),
				'xml_rpc',
				__( 'Disable XML-RPC', SECSAFE_TRANSLATE ),
				__( 'The xmlrpc.php file allows remote execution of scripts. This can be useful in some cases, but most of the time it is not needed. Attackers often use XML-RPC to brute force login to your website.', SECSAFE_TRANSLATE )
			);

			$html .= $this->form_table( $rows );

			// Save Button
			$html .= $this->button( __( 'Save Settings', SECSAFE_TRANSLATE ) );

			return $html;

		}

		/**
		 * This tab displays the users.
		 * @since  2.1.3
		 */
		function tab_users() {

			/**
			 * @todo Create the ability to audit users
			 *
			 * require_once( SECSAFE_DIR_ADMIN_TABLES . '/TableUsers.php' );
			 *
			 * ob_start();
			 *
			 * include( ABSPATH . 'wp-admin/users.php' );
			 *
			 * return ob_get_clean();
			 */

		}

		/**
		 * This tab displays the login log.
		 * @since  2.0.0
		 */
		function tab_logins() {

			require_once( SECSAFE_DIR_ADMIN_TABLES . '/TableLogins.php' );

			ob_start();

			$table = new TableLogins();
			$table->display_charts();
			$table->prepare_items();
			$table->search_box( __( 'Search logins', SECSAFE_TRANSLATE ), 'log' );
			$table->display();

			return ob_get_clean();

		}

		/**
		 * This tab displays the admin activity log.
		 * @since  2.0.0
		 */
		function tab_activity() {

			if ( ! SECSAFE_DEBUG ) {
				return;
			}

			require_once( SECSAFE_DIR_ADMIN_TABLES . '/TableActivity.php' );

			ob_start();

			$table = new TableActivity();
			$table->prepare_items();
			$table->search_box( __( 'Search activity', SECSAFE_TRANSLATE ), 'log' );
			$table->display();

			return ob_get_clean();

		}

		/**
		 * This sets the variables for the page.
		 * @since  0.1.0
		 */
		protected function set_page() {

			$this->slug = SECSAFE_SLUG . '-user-access';
			$this->title = __( 'User Access Control', SECSAFE_TRANSLATE );
			$this->description = __( 'Control how users access your admin area.', SECSAFE_TRANSLATE );

			/**
			 * @todo Create the ability to audit users
			 * disabled for now 20190722
			 */
			$notused = [
				'id'               => 'users',
				'label'            => __( 'Users', SECSAFE_TRANSLATE ),
				'title'            => __( 'Users', SECSAFE_TRANSLATE ),
				'heading'          => false,
				'intro'            => false,
				'classes'          => [ 'full' ],
				'content_callback' => 'tab_users',
			];

			$this->tabs[] = [
				'id'               => 'logins',
				'label'            => __( 'Logins', SECSAFE_TRANSLATE ),
				'title'            => __( 'Login Log', SECSAFE_TRANSLATE ),
				'heading'          => false,
				'intro'            => false,
				'classes'          => [ 'full' ],
				'content_callback' => 'tab_logins',
			];

			$this->tabs[] = [
				'id'               => 'settings',
				'label'            => __( 'Settings', SECSAFE_TRANSLATE ),
				'title'            => __( 'User Access Settings', SECSAFE_TRANSLATE ),
				'heading'          => false,
				'intro'            => false,
				'content_callback' => 'tab_settings',
			];

			if ( SECSAFE_DEBUG ) {

				$this->tabs[] = [
					'id'               => 'activity',
					'label'            => __( 'Activity Log', SECSAFE_TRANSLATE ),
					'title'            => __( 'Admin Activity Log', SECSAFE_TRANSLATE ),
					'heading'          => false,
					'intro'            => false,
					'classes'          => [ 'full' ],
					'content_callback' => 'tab_activity',
				];

			}

		}

	}
