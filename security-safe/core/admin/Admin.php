<?php

namespace SovereignStack\SecuritySafe;

// Prevent Direct Access
defined( 'ABSPATH' ) || die;
/**
 * Class Admin
 * @package SecuritySafe
 */
class Admin extends Security {
    /**
     * Is this page a settings page?
     *
     * @var bool
     */
    public bool $is_settings_page;

    /**
     * Is this page a plugin page?
     *
     * @var bool
     */
    public bool $is_plugin_page;

    /**
     * The page we are currently on
     *
     * @var object
     */
    protected object $page;

    /**
     * Admin constructor.
     *
     * @param array $session
     */
    function __construct( array $session ) {
        // Run parent class constructor first
        parent::__construct( $session );
        $this->check_settings();
        // Display Admin Notices
        add_action( 'admin_notices', [$this, 'display_notices'] );
        // Only load CSS and JS for our admin pages.
        if ( $this->is_plugin_page() ) {
            // Load CSS / JS
            add_action( 'admin_init', [$this, 'scripts'] );
            // Body Class
            add_filter( 'admin_body_class', [$this, 'admin_body_class'] );
        }
        // Create Admin Menus
        add_action( 'admin_menu', [$this, 'admin_menus'] );
        // Add links to plugins page
        add_filter(
            'plugin_action_links',
            [self::class, 'plugin_action_links'],
            10,
            2
        );
        add_filter(
            'network_admin_plugin_action_links',
            [self::class, 'plugin_action_links'],
            10,
            2
        );
    }

    /**
     * Checks settings and determines whether they need to be reset to default
     *
     * @since  0.1.0
     */
    public function check_settings() : void {
        $reset = REQUEST::key( 'reset' );
        $page = REQUEST::key( 'page' );
        $tab = REQUEST::key( 'tab' );
        if ( isset( $_POST ) && !empty( $_POST ) ) {
            if ( $this->is_settings_page() ) {
                if ( isset( $_GET['reset'] ) ) {
                    // Remove Reset Variable
                    unset($_GET['reset']);
                }
                // Create Page Slug
                $page_slug = str_replace( [
                    'security-safe-premium-',
                    'security-safe-premium',
                    'security-safe-',
                    'security-safe'
                ], '', $page );
                // Compensation For Oddball Scenarios
                $page_slug = ( $page_slug === '' ? 'general' : $page_slug );
                $page_slug = ( $page_slug === 'user-access' ? 'access' : $page_slug );
                $this->post_settings( $page_slug );
            } elseif ( $page === SECSAFE_SLUG && $tab === 'export-import' ) {
                if ( REQUEST::text_field( 'export-settings' ) ) {
                    $this->export_settings();
                } elseif ( REQUEST::text_field( 'import-settings' ) ) {
                    $this->import_settings();
                }
            }
        } elseif ( $this->is_settings_page() && $reset && $page === SECSAFE_SLUG ) {
            // Security Check
            if ( !wp_verify_nonce( REQUEST::text_field( '_nonce_reset_settings' ), SECSAFE_SLUG . '-reset-settings' ) ) {
                $this->messages[] = [__( 'Error: Settings could not be reset. Your session expired. Please try again.', SECSAFE_TRANSLATE ), 3];
            } else {
                // Reset On Plugin Settings Only
                $this->reset_settings();
            }
        }
    }

    /**
     * Determine if the current page is a settings page
     *
     * @since  2.2.3
     */
    public function is_settings_page() : bool {
        if ( !isset( $this->is_settings_page ) ) {
            // Array of pages that default to the settings tab.
            // This matters because the tab GET variable isn't in the URL.
            $default_settings_tab = [
                SECSAFE_SLUG              => 1,
                SECSAFE_SLUG . '-privacy' => 1,
                SECSAFE_SLUG . '-files'   => 1,
            ];
            // The key matters; not the value
            $exclude_pages = [
                SECSAFE_SLUG . '-pricing' => 1,
                SECSAFE_SLUG . '-account' => 1,
            ];
            $page = REQUEST::key( 'page' );
            $tab = REQUEST::key( 'tab' );
            $this->is_settings_page = ( $this->is_plugin_page() && !isset( $exclude_pages[$page] ) && ($tab == 'settings' || !$tab && isset( $default_settings_tab[$page] )) ? true : false );
        }
        return $this->is_settings_page;
    }

    /**
     * Determines if you are on a Security Safe page
     *
     * @return  boolean
     * @since  2.2.3
     */
    public function is_plugin_page() : bool {
        $page = REQUEST::key( 'page' );
        if ( !isset( $this->is_plugin_page ) ) {
            $this->is_plugin_page = $page && strpos( $page, SECSAFE_SLUG ) !== false;
        }
        return $this->is_plugin_page;
    }

    /**
     * Export Settings as JSON file (Pro Only)
     *
     * @since  1.2.0
     */
    private function export_settings() : void {
        // Get domain name for filename
        $domain_name = str_replace( ['http://', 'https://', '/'], '', get_site_url() );
        // Define headers so the file will get downloaded
        header( "Content-type: application/json" );
        header( 'Content-Disposition: attachment; filename=' . SECSAFE_SLUG . '-' . $domain_name . date( '-Ymd-His' ) . '.json' );
        header( "Pragma: no-cache" );
        header( "Expires: 0" );
        // Display JSON version of settings
        echo json_encode( $this->get_settings() );
        die;
    }

    /**
     * Import Settings as JSON file (Pro Only)
     *
     * @since  1.2.0
     */
    private function import_settings() : void {
        $import_file = $_FILES['import-file'];
        if ( $import_file['type'] == 'application/json' ) {
            $import_content = file_get_contents( $import_file["tmp_name"] );
            // Convert JSON to Array
            $import_site_settings = json_decode( $import_content, true );
            if ( isset( $import_site_settings['plugin'] ) ) {
                // Old Settings
                $site_settings_old = $this->get_settings();
                // Get Min Settings
                $site_settings_min = Plugin::get_settings_min();
                // Use Min Settings To Start
                $site_settings_new = $site_settings_min;
                // Sanitize Imported Settings
                foreach ( $site_settings_min as $label => $section ) {
                    foreach ( $section as $setting => $value ) {
                        if ( $section != 'plugin' ) {
                            if ( isset( $import_settings[$label][$setting] ) ) {
                                $site_settings_new[$label][$setting] = filter_var( $import_site_settings[$label][$setting], FILTER_SANITIZE_NUMBER_INT );
                            }
                        }
                    }
                }
                // Replace imported plugin details with current
                $site_settings_new['plugin'] = $site_settings_old['plugin'];
                // Compare to Current Settings
                if ( $site_settings_new === $site_settings_old ) {
                    $this->messages[] = [__( 'Current settings match the imported settings. No changes were made.', SECSAFE_TRANSLATE ), 1, 1];
                } else {
                    // Update Settings
                    $result = $this->set_settings( $site_settings_new );
                    if ( $result ) {
                        $this->messages[] = [__( 'Your settings imported successfully.', SECSAFE_TRANSLATE ), 0, 1];
                    } else {
                        // Import File is not the correct format
                        $this->messages[] = [__( 'Import Failed: File is corrupted [1].', SECSAFE_TRANSLATE ), 3, 1];
                    }
                }
            } else {
                // Import File is not the correct format
                $this->messages[] = [__( 'Import Failed: File is corrupted [2].', SECSAFE_TRANSLATE ), 3, 1];
            }
        } else {
            $this->messages[] = [__( 'Import Failed: Please upload a JSON file.', SECSAFE_TRANSLATE ), 3, 1];
        }
    }

    /**
     * Loads dependents for the chart.
     *
     * @param $args array
     *
     * @since 2.0.0
     */
    public static function load_charts( array $args ) : void {
        require_once SECSAFE_DIR_ADMIN_INCLUDES . '/Charts.php';
        Charts::display_charts( $args );
    }

    /**
     * Initializes admin scripts
     */
    public function scripts() : void {
        $cache_buster = ( SECSAFE_DEBUG ? SECSAFE_VERSION . date( 'YmdHis' ) : SECSAFE_VERSION );
        // Load CSS
        wp_enqueue_style(
            SECSAFE_SLUG . '-admin',
            SECSAFE_URL_ADMIN_ASSETS . 'css/admin.css',
            [],
            $cache_buster,
            'all'
        );
        // Load JS
        wp_enqueue_script( 'common' );
        wp_enqueue_script( 'wp-lists' );
        wp_enqueue_script( 'postbox' );
        wp_enqueue_script(
            SECSAFE_SLUG . '-admin',
            SECSAFE_URL_ADMIN_ASSETS . 'js/admin.js',
            ['jquery'],
            filemtime( SECSAFE_DIR_ADMIN_ASSETS . '/js/admin.js' ),
            true
        );
    }

    /**
     * Adds a class to the body tag
     *
     * @param string $classes
     *
     * @return string
     * @since  0.2.0
     */
    public function admin_body_class( string $classes ) : string {
        return $classes . ' ' . SECSAFE_SLUG;
    }

    /**
     * Creates Admin Menus
     */
    public function admin_menus() : void {
        $page = [];
        // Add the menu page
        $page['menu_title'] = SECSAFE_NAME;
        $page['title'] = SECSAFE_NAME . ' Dashboard';
        $page['capability'] = 'activate_plugins';
        $page['slug'] = SECSAFE_SLUG;
        $page['function'] = [$this, 'page_dashboard'];
        $page['icon_url'] = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4NCjwhLS0gR2VuZXJhdG9yOiBBZG9iZSBJbGx1c3RyYXRvciAxNS4wLjAsIFNWRyBFeHBvcnQgUGx1Zy1JbiAuIFNWRyBWZXJzaW9uOiA2LjAwIEJ1aWxkIDApICAtLT4NCjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+DQo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkxheWVyXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4Ig0KCSB3aWR0aD0iODMuNDExcHgiIGhlaWdodD0iOTQuMTNweCIgdmlld0JveD0iMC4wMDEgMzQ4LjkzNSA4My40MTEgOTQuMTMiIGVuYWJsZS1iYWNrZ3JvdW5kPSJuZXcgMC4wMDEgMzQ4LjkzNSA4My40MTEgOTQuMTMiDQoJIHhtbDpzcGFjZT0icHJlc2VydmUiPg0KPGc+DQoJPHBhdGggZmlsbD0iI0YyNjQxOSIgZD0iTTgzLjI3MSwzNTYuODk2YzAsMC0yMC41NjItNy45NjEtNDEuNjI4LTcuOTYxYy0yMS4wNjcsMC00MS42MjksNy45NjEtNDEuNjI5LDcuOTYxDQoJCXMtMC43OTUsMzAuMDMsMTAuMDMyLDUxLjgwNGMxMC44MjUsMjEuNzcxLDMyLjA5OSwzNC4zNjUsMzIuMDk5LDM0LjM2NXMyMS4wNzgtMTMuMjI3LDMyLjEtMzYuODU0DQoJCUM4NS4yNjYsMzgyLjU4MSw4My4yNzEsMzU2Ljg5Niw4My4yNzEsMzU2Ljg5NnogTTUuMjksMzYxLjgxNGwwLjAzOC0xLjQ4M2wxLjQwNi0wLjQ4MWMwLjQ0OS0wLjE1NCw3LjQzMS0yLjUwNywxNi45NTktNC4xOQ0KCQljLTIuMTU0LDEuMjcxLTQuMjQ0LDIuNzc1LTUuNjQyLDMuODk5Yy01LjU0OSw0LjQ1NC0xMC4wMTgsOS4wOTktMTIuNDg4LDExLjgzMUM1LjIwMSwzNjUuOTM1LDUuMjgsMzYyLjIwOSw1LjI5LDM2MS44MTR6DQoJCSBNNi4wMTIsMzc2LjYzMWMyLjQ2OCwyLjM1LDYuODU1LDUuNzk1LDEzLjc2Nyw4Ljg2OWMxMS40MDgsNS4wNzIsMjEuODIyLDcuMTc2LDIxLjgyMiw3LjE3NnM4LjgxLTIuNTYxLDE4LjA2MS03LjkyNg0KCQlzMTEuNTI2LTcuNTg4LDExLjUyNi03LjU4OHMtMTMuMjkzLDAuNzA3LTI0LjA4LTEuMTQ5Yy0xMi45MTktMi4yMjQtMTcuMzI1LTUuNDQtMTcuMzI1LTUuNDRzNC40MDYtNC4wNjIsMTAuNDI1LTcuNjY2DQoJCWM2LjMxNC0zLjc3NywxMy45MzctNi43NDIsMTYuNTQ1LTcuNzA5YzEwLjkzOCwxLjY3NiwxOS4yNzMsNC40ODQsMTkuNzY0LDQuNjUzbDEuMzM2LDAuNDU0bDAuMTA0LDEuNDA4DQoJCWMwLjAzMywwLjQ1NSwwLjQxMyw2LjAwMi0wLjMwNCwxMy44NzljLTIuNzUyLDIuNjUtMTMuMzc0LDEyLjAzMS0zMi41OTgsMTkuMTk5Yy0xOC4zNTQsNi44NDQtMjkuOTA2LDguNzU2LTMyLjQ4NCw5LjEyNQ0KCQlDOC42OTUsMzk0Ljk2Myw2Ljg2NiwzODQuNzYsNi4wMTIsMzc2LjYzMXogTTY5LjMyLDQwNi40ODljLTMuODQ4LDIuNDA2LTEyLjA2Nyw3LjA2MS0yMy41MzQsMTAuOTENCgkJYy0xMi41NDYsNC4yMTUtMTguNDY4LDUuMzAxLTIwLjM1OSw1LjU2NmMtMC42OTMtMC43MjktMS4zODUtMS40OTQtMi4wNzUtMi4yODVjMi40MDUtMC41OTIsMTEuNzkzLTIuOTk4LDIzLjkwMy03LjM0Ng0KCQljMTEuMDU4LTMuOTY5LDIwLjU1NS05LjgyNiwyNC42MTctMTIuNTFjLTAuNDczLDEuMTg4LTAuOTc5LDIuMzc3LTEuNTI2LDMuNTU3QzcwLjAxNCw0MDUuMDk4LDY5LjY3LDQwNS43OTcsNjkuMzIsNDA2LjQ4OXoiLz4NCjwvZz4NCjwvc3ZnPg0K';
        $page['position'] = '999';
        add_menu_page(
            $page['title'],
            $page['menu_title'],
            $page['capability'],
            $page['slug'],
            $page['function'],
            $page['icon_url'],
            $page['position']
        );
        $subpages = $this->get_category_pages();
        foreach ( $subpages as $slug => $title ) {
            $slug_uscore = str_replace( '-', '_', $slug );
            add_submenu_page(
                $page['slug'],
                // Parent Slug
                $page['menu_title'] . ' ' . $title,
                // Page Title
                $title,
                // Menu Title
                $page['capability'],
                // Capability
                $page['slug'] . '-' . $slug,
                // Menu Slug
                [$this, 'page_' . $slug_uscore]
            );
        }
    }

    /**
     * Get Category Pages
     *
     * @param $disabled bool
     *
     * @return array
     * @since  0.2.0
     */
    private function get_category_pages( bool $disabled = false ) : array {
        // All Category Pages
        $pages = [
            'plugin'      => __( 'Plugin', SECSAFE_TRANSLATE ),
            'privacy'     => __( 'Privacy', SECSAFE_TRANSLATE ),
            'files'       => __( 'Files', SECSAFE_TRANSLATE ),
            'user-access' => __( 'User Access', SECSAFE_TRANSLATE ),
            'content'     => __( 'Content', SECSAFE_TRANSLATE ),
            'firewall'    => __( 'Firewall', SECSAFE_TRANSLATE ),
        ];
        // Remove Specific Menus
        if ( !$disabled ) {
            unset($pages['plugin']);
        }
        return $pages;
    }

    /**
     * Wrapper for creating Dashboard page
     *
     * @since  0.1.0
     */
    public function page_dashboard() : void {
        $this->get_page( 'General' );
    }

    /**
     * Gets the admin page
     *
     * @param $page_slug string The title of the submenu
     *
     * @todo merge get_page() and display_page() into a single method
     *
     * @since  0.2.0
     */
    private function get_page( string $page_slug = '' ) : void {
        if ( $page_slug ) {
            // Format Title
            $title_camel = str_replace( ' ', '', $page_slug );
            // Include Admin Page
            require_once SECSAFE_DIR_ADMIN_PAGES . '/AdminPage.php';
            require_once SECSAFE_DIR_ADMIN_PAGES . '/AdminPage' . $title_camel . '.php';
            // Class For The Page
            $class = __NAMESPACE__ . '\\AdminPage' . $title_camel;
            $page_slug = strtolower( $page_slug );
            // @TODO: calling a class like this is a bad idea. Need to change this in future version 2.5
            $this->page = new $class($this->get_page_settings( $page_slug ));
            $this->display_page();
        } else {
            //Janitor::log( 'ERROR: Parameter title is empty.', __FILE__, __LINE__ );
        }
    }

    /**
     * Page template
     *
     * @since  0.2.0
     */
    protected function display_page() : void {
        $page = $this->page;
        $tab = REQUEST::key( 'tab' );
        ?>
			<div class="wrap">

				<div class="intro">

                    <h1><?php 
        echo esc_html( $page->title );
        ?></h1>

                    <p class="desc"><?php 
        echo esc_html( $page->description );
        ?></p>
					<?php 
        $logo_link = SECSAFE_URL_ACCOUNT;
        if ( !security_safe()->can_use_premium_code() ) {
            $logo_link = SECSAFE_URL_MORE_INFO_PRO;
        }
        ?>
					<a href="<?php 
        echo esc_url( $logo_link );
        ?>" target="_blank" class="ss-logo"><img
								src="<?php 
        echo esc_url( SECSAFE_URL_ADMIN_ASSETS . 'img/logo.svg?v=' . SECSAFE_VERSION );
        ?>"
								alt="<?php 
        echo esc_attr( SECSAFE_NAME );
        ?>"><br /><span class="version"><?php 
        $version_pro = sprintf( __( 'Pro Version %s', SECSAFE_TRANSLATE ), SECSAFE_VERSION );
        $version = ( security_safe()->is__premium_only() ? $version_pro : sprintf( __( 'Version %s', SECSAFE_TRANSLATE ), SECSAFE_VERSION ) );
        echo esc_html( $version );
        if ( !security_safe()->can_use_premium_code() ) {
            // Using free version
            echo '<br /><span style="color:orange; font-weight:bold;font-size: 13px;">' . esc_html__( 'Upgrade to Pro', SECSAFE_TRANSLATE ) . '</span>';
        }
        ?></span></a>

				</div><!-- .intro -->

				<?php 
        $this->display_heading_menu();
        $page->display_tabs();
        // Build action URL
        $enctype = ( REQUEST::key( 'tab' ) == 'export-import' ? ' enctype="multipart/form-data"' : '' );
        ?>

				<form method="post" action="<?php 
        echo esc_attr( SECSAFE_ADMIN_URL_CURRENT );
        ?>"<?php 
        echo $enctype;
        ?>>

					<div class="all-tab-content">

						<?php 
        if ( $this->is_settings_page() ) {
            wp_nonce_field( SECSAFE_SLUG . '-save-settings', '_nonce_save_settings' );
        }
        $page->display_tabs_content();
        $this->display_sidebar();
        ?>

						<div id="tab-content-footer" class="footer tab-content"></div>

					</div><!-- .all-tab-content -->

				</form>

				<div class="wrap-footer full clear">

					<hr />

					<p><?php 
        printf( __( 'If you like %1$s, please <a href="%2$s" target="_blank">post a review</a>.', SECSAFE_TRANSLATE ), SECSAFE_NAME, SECSAFE_URL_WP_REVIEWS_NEW );
        ?></p>

					<p><?php 
        printf( __( 'Need help? Visit the <a href="%1$s" target="_blank">support forum</a>', SECSAFE_TRANSLATE ), SECSAFE_URL_WP );
        ?>
						.</p>

					<p><?php 
        // Display
        $start = SECSAFE_TIME_START;
        $end = microtime( true );
        echo round( ($end - $start) * 1000 );
        ?>ms</p>
				</div>
			</div><!-- .wrap -->
			<?php 
    }

    /**
     * Display Heading Menu
     *
     * @since  0.2.0
     */
    protected function display_heading_menu() : void {
        $menus = $this->get_category_pages( true );
        $page = REQUEST::key( 'page' );
        echo '<ul class="featured-menu">';
        foreach ( $menus as $k => $l ) {
            $class = $k;
            if ( $k == 'plugin' ) {
                $href = 'admin.php?page=' . SECSAFE_SLUG . '&tab=settings';
            } elseif ( $k == 'firewall' ) {
                // No settings, so we must define tab
                $href = 'admin.php?page=' . SECSAFE_SLUG . '-' . $k . '&tab=blocked';
            } elseif ( $k == 'content' ) {
                // No settings, so we must define tab
                $href = 'admin.php?page=' . SECSAFE_SLUG . '-' . $k . '&tab=404s';
            } elseif ( $k == 'user-access' ) {
                // No settings, so we must define tab
                $href = 'admin.php?page=' . SECSAFE_SLUG . '-' . $k . '&tab=logins';
            } else {
                $href = 'admin.php?page=' . SECSAFE_SLUG . '-' . $k . '&tab=settings';
            }
            // Highlight Active Menu
            if ( $page == SECSAFE_SLUG && $k == 'plugin' ) {
                $active = ' active';
            } else {
                $active = ( strpos( $page, $k ) !== false ? ' active' : '' );
            }
            $class .= $active;
            // Convert All Menus to A Single Line
            $l = ( $l == __( 'User Access', SECSAFE_TRANSLATE ) ? __( 'Access', SECSAFE_TRANSLATE ) : $l );
            echo '<li><a href="' . esc_url( admin_url( $href ) ) . '" class="icon-' . esc_attr( $class ) . '"><span>' . esc_html( $l ) . '</span></a></li>';
        }
        echo '</ul>';
    }

    /**
     * Displays the sidebar depending on the class of the current tab
     *
     * @since  2.2.0
     */
    protected function display_sidebar() : void {
        //$tabs_with_sidebars = [ 'settings', 'export-import', 'debug' ];
        $tab = REQUEST::key( 'tab' );
        // Get the current tab
        if ( $tab ) {
            $tabs = $this->page->tabs;
            $num = 0;
            foreach ( $tabs as $tab ) {
                if ( $tab['id'] == $tab ) {
                    $current_tab = $this->page->tabs[$num];
                    break;
                }
                $num++;
            }
        }
        $current_tab = ( !isset( $current_tab ) || !$tab ? $this->page->tabs[0] : $current_tab );
        $display_sidebar = ( isset( $current_tab['classes'] ) && in_array( 'full', $current_tab['classes'] ) ? false : true );
        if ( $display_sidebar ) {
            ?>

				<div id="sidebar" class="sidebar">

					<div class="rate-us widget">
						<?php 
            if ( security_safe()->is_not_paying() ) {
                $heading = __( 'Support This Plugin', SECSAFE_TRANSLATE );
                $message = __( 'Your review encourages ongoing maintenance of this Free version.', SECSAFE_TRANSLATE );
            } else {
                $heading = sprintf( __( 'Like %s?', SECSAFE_TRANSLATE ), SECSAFE_NAME );
                $message = __( 'Share your positive experience!', SECSAFE_TRANSLATE );
            }
            ?>
						<h5><?php 
            echo esc_html( $heading );
            ?></h5>
						<p><?php 
            echo esc_html( $message );
            ?></p>
						<p class="cta ratings"><a href="<?php 
            echo esc_url( SECSAFE_URL_WP_REVIEWS );
            ?>" target="_blank"
						                          class="rate-stars"><span class="icon-star"></span><span
										class="icon-star"></span><span class="icon-star"></span><span
										class="icon-star"></span><span class="icon-star"></span></a></p>
					</div>

					<div class="follow-us widget">
						<p><a href="<?php 
            echo esc_url( SECSAFE_URL_TWITTER );
            ?>" class="icon-twitter"
						      target="_blank"><?php 
            esc_html( printf( __( 'Follow %s', SECSAFE_TRANSLATE ), SECSAFE_NAME ) );
            ?></a></p>
					</div>

					<?php 
            if ( security_safe()->is_not_paying() ) {
                ?>
						<div class="upgrade-pro widget">

							<h5><?php 
                esc_html_e( 'Get More Features', SECSAFE_TRANSLATE );
                ?></h5>
							<p><?php 
                esc_html_e( 'Pro features give you more control and save you time.', SECSAFE_TRANSLATE );
                ?></p>
							<p class="cta"><a href="<?php 
                echo esc_url( SECSAFE_URL_MORE_INFO_PRO );
                ?>" target="_blank"
							                  class="icon-right-open"><?php 
                esc_html_e( 'Upgrade to Pro!', SECSAFE_TRANSLATE );
                ?></a>
							</p>
						</div>
					<?php 
            }
            ?>

				</div>

			<?php 
        }
    }

    /**
     * Wrapper for creating Privacy page
     *
     * @since  0.2.0
     */
    public function page_privacy() : void {
        $this->get_page( 'Privacy' );
    }

    /**
     * Wrapper for creating Files page
     *
     * @since  0.2.0
     */
    public function page_files() : void {
        $this->get_page( 'Files' );
    }

    /**
     * Wrapper for creating Content page
     *
     * @since  0.2.0
     */
    public function page_content() : void {
        $this->get_page( 'Content' );
    }

    /**
     * Wrapper for creating User Access page
     *
     * @since  0.2.0
     */
    public function page_user_access() : void {
        $this->get_page( 'Access' );
    }

    /**
     * Wrapper for creating Firewall page
     *
     * @since  0.2.0
     */
    public function page_firewall() : void {
        $this->get_page( 'Firewall' );
    }

    /**
     * Displays all messages
     *
     * @param $skip boolean
     *
     * @since  0.2.0
     */
    public function display_notices( bool $skip = false ) : void {
        if ( !$skip ) {
            // Register / Display Admin Notices
            $this->all_notices();
        }
        if ( isset( $this->messages[0] ) ) {
            foreach ( $this->messages as $m ) {
                $message = ( isset( $m[0] ) ? $m[0] : false );
                $status = ( isset( $m[1] ) ? $m[1] : 0 );
                $dismiss = ( isset( $m[2] ) ? $m[2] : 0 );
                if ( $message ) {
                    // Display Message
                    $this->admin_notice( $message, $status, $dismiss );
                }
            }
            // Reset Messages
            $this->messages = [];
        }
    }

    /**
     * This registers all the notices for later display
     *
     * @since  2.0.0
     */
    protected function all_notices() : void {
        // Check if policies are turned off
        $this->policy_notices();
        $page = REQUEST::key( 'page' );
        $tab = REQUEST::key( 'tab' );
        // Display Notices on Our Plugin Pages Only
        if ( $page == SECSAFE_SLUG && $tab == 'debug' ) {
            // Check if WP Cron is disabled
            if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON == true ) {
                $message = sprintf( __( '%s: WP Cron is disabled. This will affect the routine database table cleanup. Please setup a manual cron to trigger WP Cron daily or enable WP Cron.', SECSAFE_TRANSLATE ), SECSAFE_NAME );
                $this->messages[] = [$message, 2, 0];
            }
        }
        if ( SECSAFE_DEBUG ) {
            $this->messages[] = [sprintf( __( '%s: Plugin Debug Mode is on.', SECSAFE_TRANSLATE ), SECSAFE_NAME ), 1, 0];
        }
    }

    /**
     * Sets notices for policies that are disabled as a group.
     *
     * @since  1.1.10
     */
    protected function policy_notices() : void {
        $page = REQUEST::key( 'page' );
        $site_settings = $this->get_settings();
        // All Plugin Policies
        if ( !isset( $site_settings['general']['on'] ) || $site_settings['general']['on'] != "1" ) {
            if ( $page == SECSAFE_SLUG ) {
                $message = sprintf( __( '%s: All security policies are disabled.', SECSAFE_TRANSLATE ), SECSAFE_NAME );
            } else {
                $message = sprintf(
                    __( '%s: All security policies are disabled. You can enable them in <a href="%s">Plugin Settings</a>. If you are experiencing an issue, <a href="%s">reset your settings.</a>', SECSAFE_TRANSLATE ),
                    SECSAFE_NAME,
                    admin_url( 'admin.php?page=' . SECSAFE_SLUG . '&tab=settings#settings' ),
                    admin_url( 'admin.php?page=' . SECSAFE_SLUG . '&reset=1&_nonce_reset_settings=' . wp_create_nonce( SECSAFE_SLUG . '-reset-settings' ) )
                );
            }
            $this->messages[] = [$message, 2, 0];
        } else {
            // Privacy Policies
            if ( !isset( $site_settings['privacy']['on'] ) || $site_settings['privacy']['on'] != "1" ) {
                if ( $page == SECSAFE_SLUG . '-privacy' ) {
                    $message = sprintf( __( '%s: All privacy policies are disabled.', SECSAFE_TRANSLATE ), SECSAFE_NAME );
                } else {
                    $message = sprintf( __( '%s: All privacy policies are disabled. You can enable them at the top of <a href="%s">Privacy Settings</a>.', SECSAFE_TRANSLATE ), SECSAFE_NAME, admin_url( 'admin.php?page=' . SECSAFE_SLUG . '-privacy&tab=settings#settings' ) );
                }
                $this->messages[] = [$message, 2, 0];
            }
            // Files Policies
            if ( !isset( $site_settings['files']['on'] ) || $site_settings['files']['on'] != "1" ) {
                if ( $page == SECSAFE_SLUG . '-files' ) {
                    $message = sprintf( __( '%s: All file policies are disabled.', SECSAFE_TRANSLATE ), SECSAFE_NAME );
                } else {
                    $message = sprintf( __( '%s: All file policies are disabled. You can enable them at the top of <a href="%s">File Settings</a>.', SECSAFE_TRANSLATE ), SECSAFE_NAME, admin_url( 'admin.php?page=' . SECSAFE_SLUG . '-files&tab=settings#settings' ) );
                }
                $this->messages[] = [$message, 2, 0];
            }
            // Access Policies
            if ( !isset( $site_settings['access']['on'] ) || $site_settings['access']['on'] != "1" ) {
                if ( $page == SECSAFE_SLUG . '-user-access' ) {
                    $message = sprintf( __( '%s: All user access policies are disabled.', SECSAFE_TRANSLATE ), SECSAFE_NAME );
                } else {
                    $message = sprintf( __( '%s: All user access policies are disabled. You can enable them at the top of <a href="%s">User Access Settings</a>.', SECSAFE_TRANSLATE ), SECSAFE_NAME, admin_url( 'admin.php?page=' . SECSAFE_SLUG . '-user-access&tab=settings#settings' ) );
                }
                $this->messages[] = [$message, 2, 0];
            }
            // Content Policies
            if ( !isset( $site_settings['content']['on'] ) || $site_settings['content']['on'] != "1" ) {
                if ( $page == SECSAFE_SLUG . '-content' ) {
                    $message = sprintf( __( '%s: All content policies are disabled.', SECSAFE_TRANSLATE ), SECSAFE_NAME );
                } else {
                    $message = sprintf( __( '%s: All content policies are disabled. You can enable them at the top of <a href="%s">Content Settings</a>.', SECSAFE_TRANSLATE ), SECSAFE_NAME, admin_url( 'admin.php?page=' . SECSAFE_SLUG . '-content&tab=settings#settings' ) );
                }
                $this->messages[] = [$message, 2, 0];
            }
        }
    }

    /**
     * Displays a message at the top of the screen.
     *
     * @param $message string
     * @param $status int
     * @param $dismiss int
     *
     * @since  0.1.0
     */
    protected function admin_notice( string $message, int $status = 0, int $dismiss = 0 ) : void {
        // Set Classes
        $class = 'notice-success';
        $class = ( $status == 1 ? 'notice-info' : $class );
        $class = ( $status == 2 ? 'notice-warning' : $class );
        $class = ( $status == 3 ? 'notice-error' : $class );
        $class = 'active notice ' . $class;
        if ( $dismiss ) {
            $class .= ' is-dismissible';
        }
        // @todo figure out a better way to sanitize so we can use esc_html() on $message here
        echo '<div class="' . esc_attr( $class ) . '"><p>' . $message . '</p></div>';
    }

    /**
     * Adds links to this plugin on the plugin's management page
     *
     * @param array  $links Array of links for the plugins, adapted when the current plugin is found.
     * @param string $file  The filename for the current plugin, which the filter loops through.
     *
     * @return array
     */
    public static function plugin_action_links( array $links, string $file ) : array {
        // Show Settings Link
        if ( SECSAFE_PLUGIN === $file ) {
            if ( !is_network_admin() ) {
                $label = __( 'Settings', SECSAFE_TRANSLATE );
                $settings_url = SECSAFE_SETTINGS_URL;
                // Settings Link
                $settings_link = '<a href="' . esc_url( $settings_url ) . '">' . esc_html( $label ) . '</a>';
                array_unshift( $links, $settings_link );
            }
            // Style Links
            $rate_link = '
<br /><a href="https://wordpress.org/support/plugin/security-safe/reviews/#new-post">' . esc_html__( 'Rate:', SECSAFE_TRANSLATE ) . ' <span class="rate-us" data-stars="5"><span class="dashicons dashicons-star-filled star-1" title="' . esc_html__( 'Poor', SECSAFE_TRANSLATE ) . '"></span><span class="dashicons dashicons-star-filled star-2" title="' . esc_html__( 'Works', SECSAFE_TRANSLATE ) . '"></span><span class="dashicons dashicons-star-filled star-3" title="' . esc_html__( 'Good', SECSAFE_TRANSLATE ) . '"></span><span class="dashicons dashicons-star-filled star-4" title="' . esc_html__( 'Great', SECSAFE_TRANSLATE ) . '"></span><span class="dashicons dashicons-star-filled star-5" title="' . esc_html__( 'Fantastic!', SECSAFE_TRANSLATE ) . '"></span></span></a>
<style>
	.plugins .plugin-title [class*=dashicons-star-]{
		float: none;
		width: auto;
		height: auto;
		padding: 0;
		background: none;
	}
	.plugins .plugin-title .rate-us [class*=dashicons-star-]:before {
        font-size: 20px;
        color: #ffb900;
        background: none;
        padding: 0;
        box-shadow: none;
	}
	.plugins .plugin-title .rate-us:hover span:before {
		content: "\\f154";
	}
	
	.plugins .plugin-title .rate-us:hover .star-1:before,
	.plugins .plugin-title .rate-us[data-stars="2"]:hover span.star-2:before,
	.plugins .plugin-title .rate-us[data-stars="3"]:hover span.star-2:before,
	.plugins .plugin-title .rate-us[data-stars="3"]:hover span.star-3:before,
	.plugins .plugin-title .rate-us[data-stars="4"]:hover span.star-2:before,
	.plugins .plugin-title .rate-us[data-stars="4"]:hover span.star-3:before,
	.plugins .plugin-title .rate-us[data-stars="4"]:hover span.star-4:before,
	.plugins .plugin-title .rate-us[data-stars="5"]:hover span:before {
		content: "\\f155";
	}
</style>
<script>
jQuery(".plugins .plugin-title .rate-us span").on("mouseover", function(){
    let stars = jQuery(this).index() + 1;
   jQuery(this).closest(".rate-us").attr("data-stars", stars);
});
</script>';
            $links[] = $rate_link;
        }
        return $links;
    }

}
