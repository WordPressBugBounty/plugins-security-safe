<?php

namespace SovereignStack\SecuritySafe;

// Prevent Direct Access
defined( 'ABSPATH' ) || die;
/**
 * Class Security
 *
 * @package SecuritySafe
 * @todo Add @since version
 */
class Security extends Plugin {
    /**
     * Logged In Status
     * @var bool
     */
    public $logged_in = false;

    /**
     * Is the current IP allowed?
     * @var bool
     * @since  2.0.0
     */
    public $whitelisted = false;

    /**
     * Is the current IP blacklisted?
     * @var bool
     * @since  2.0.0
     */
    public $blacklisted = false;

    /**
     * @var string
     */
    public $date_expires = '';

    /**
     * Detect whether a login error has occurred
     * @var bool
     */
    public $login_error = false;

    /**
     * List of all policies running.
     * @var array
     * @todo Add @since version
     */
    protected $policies;

    /**
     * Security constructor
     *
     * @todo Add @since version
     *
     * @param array $session
     */
    function __construct( array $session ) {
        // Run parent class constructor first
        parent::__construct( $session );
    }

    /**
     * Start Security policies after the instance is created
     */
    function start_security() : void {
        //Janitor::log( 'running Security.php' );
        $settings_general = $this->get_page_settings( 'general' );
        $all_policies_on = $settings_general['on'] ?? '';
        if ( '1' == $all_policies_on ) {
            // Run All Policies
            $this->access();
            $this->privacy();
            $this->files();
            $this->content();
        }
    }

    /**
     * Access Policies
     *
     * @since  0.2.0
     */
    private function access() : void {
        //Janitor::log( 'running access().' );
        $settings_access = $this->get_page_settings( 'access' );
        if ( $settings_access['on'] == "1" ) {
            // Disable xmlrpc.php
            $this->add_firewall_policy( $settings_access, 'PolicyXMLRPC', 'xml_rpc' );
            // Block usernames
            $this->add_firewall_policy( $settings_access, 'PolicyBlockUsernames', 'block_usernames' );
            // Check only if not logged in
            if ( !$this->logged_in ) {
                $firewall = new Firewall();
                // Determine Allowed / Denied
                if ( $firewall->is_whitelisted() ) {
                    $this->whitelisted = true;
                } else {
                    //Janitor::log( 'Not Whitelisted' );
                    if ( !$firewall->is_blacklisted() ) {
                        // Run Rate Limiting to see if user gets blacklisted
                        $firewall->rate_limit();
                    }
                    if ( $firewall->is_blacklisted() ) {
                        $this->blacklisted = true;
                        // Stop core from attempting to login
                        Security::stop_authenticate_process();
                    }
                }
            }
            // Generic Login Errors
            $this->add_policy( $settings_access, 'PolicyLoginErrors', 'login_errors' );
            // Disable Login Password Reset
            $this->add_policy( $settings_access, 'PolicyLoginPasswordReset', 'login_password_reset' );
            // Disable Login Remember Me Checkbox
            $this->add_policy( $settings_access, 'PolicyLoginRememberMe', 'login_remember_me' );
            // Log Logins
            $this->add_firewall_policy( [], 'PolicyLogLogins' );
        }
    }

    /**
     * Runs specified firewall policy class then adds it to the policies list.
     *
     * @param array $page_settings
     * @param string $policy Name of security policy
     * @param string $slug Setting slug associated with policy
     * @param string $plan Used to distinguish premium files
     *
     * @since  2.0.0
     */
    private function add_firewall_policy(
        array $page_settings,
        string $policy,
        string $slug = '',
        string $plan = ''
    ) : void {
        //Janitor::log( 'add policy().' );
        // Include Specific Policy
        require_once SECSAFE_DIR_FIREWALL . '/' . $policy . $plan . '.php';
        //Janitor::log( 'add policy ' . $policy );
        $policy = __NAMESPACE__ . '\\' . $policy;
        if ( isset( $page_settings[$slug] ) ) {
            // Pass setting value
            new $policy($page_settings[$slug]);
        } else {
            new $policy();
        }
        $this->policies[] = $policy;
        //Janitor::log( $policy );
    }

    /**
     * Runs specified policy class then adds it to the policies list.
     *
     * @param $page_settings array
     * @param $policy string Name of security policy
     * @param $slug string Setting slug associated with policy
     * @param $plan string Used to distinguish premium files
     *
     * @since  0.2.0
     */
    private function add_policy(
        array $page_settings,
        string $policy,
        string $slug = '',
        string $plan = ''
    ) : void {
        //Janitor::log( 'add policy().' );
        if ( $slug == '' || isset( $page_settings[$slug] ) && $page_settings[$slug] ) {
            // Include Specific Policy
            require_once SECSAFE_DIR_PRIVACY . '/' . $policy . $plan . '.php';
            //Janitor::log( 'add policy ' . $policy );
            $policy = __NAMESPACE__ . '\\' . $policy;
            new $policy();
            $this->policies[] = $policy;
            //Janitor::log( $policy );
        }
    }

    /**
     * Privacy Policies
     *
     * @since  0.2.0
     */
    private function privacy() : void {
        //Janitor::log( 'running privacy().' );
        $settings_privacy = $this->get_page_settings( 'privacy' );
        if ( $settings_privacy['on'] == "1" ) {
            // Hide WordPress Version
            $this->add_policy( $settings_privacy, 'PolicyHideWPVersion', 'wp_generator' );
            if ( is_admin() ) {
                // Hide WordPress Version Admin Footer
                $this->add_policy( $settings_privacy, 'PolicyHideWPVersionAdmin', 'wp_version_admin_footer' );
            }
            // Hide Script Versions
            $this->add_policy( $settings_privacy, 'PolicyHideScriptVersions', 'hide_script_versions' );
            // Make Website Anonymous
            $this->add_policy( $settings_privacy, 'PolicyAnonymousWebsite', 'http_headers_useragent' );
        }
    }

    /**
     * File Policies
     *
     * @since  0.2.0
     */
    private function files() : void {
        //Janitor::log( 'running files().' );
        global $wp_version;
        $settings_files = $this->get_page_settings( 'files' );
        if ( $settings_files['on'] == '1' ) {
            // Disallow Theme File Editing
            $this->add_constant_policy( $settings_files, 'PolicyDisallowFileEdit', 'DISALLOW_FILE_EDIT' );
            // Protect WordPress Version Files
            $this->add_policy( $settings_files, 'PolicyWordPressVersionFiles', 'version_files_core' );
            // Auto Updates: https://codex.wordpress.org/Configuring_Automatic_Background_Updates
            if ( version_compare( $wp_version, '3.7.0' ) >= 0 && !defined( 'AUTOMATIC_UPDATER_DISABLED' ) ) {
                if ( !defined( 'WP_AUTO_UPDATE_CORE' ) ) {
                    // Automatic Nightly Core Updates
                    $this->add_filter_bool( $settings_files, 'PolicyUpdatesCoreDev', 'allow_dev_auto_core_updates' );
                    // Automatic Major Core Updates
                    $this->add_filter_bool( $settings_files, 'PolicyUpdatesCoreMajor', 'allow_major_auto_core_updates' );
                    // Automatic Minor Core Updates
                    $this->add_filter_bool( $settings_files, 'PolicyUpdatesCoreMinor', 'allow_minor_auto_core_updates' );
                }
                // Automatic Plugin Updates
                $this->add_filter_bool( $settings_files, 'PolicyUpdatesPlugin', 'auto_update_plugin' );
                // Automatic Theme Updates
                $this->add_filter_bool( $settings_files, 'PolicyUpdatesTheme', 'auto_update_theme' );
            }
        }
    }

    /**
     * Adds policy constant variable and then adds it to the policies list.
     *
     * @param array $page_settings
     * @param string $policy Name of security policy
     * @param string $slug Setting slug associated with policy
     * @param bool $value Set the value of new constant variable
     *
     * @since  0.2.0
     */
    private function add_constant_policy(
        array $page_settings,
        string $policy,
        string $slug,
        bool $value = true
    ) : void {
        if ( is_array( $page_settings ) && $policy && $slug && $value ) {
            if ( isset( $page_settings[$slug] ) && $page_settings[$slug] ) {
                if ( !defined( $slug ) ) {
                    define( $slug, $value );
                    $this->policies[] = $policy;
                } else {
                    //Janitor::log( $slug . ' already defined' );
                }
            } else {
                //Janitor::log( $slug . ': Setting not set.' );
            }
        } else {
            //Janitor::log( $slug . ': Problem adding Constant.' );
        }
    }

    /**
     * Adds a filter with a forced boolean result.
     *
     * @param array $page_settings
     * @param string $policy Name of security policy
     * @param string $slug Setting slug associated with policy
     *
     * @since  0.2.0
     */
    private function add_filter_bool( array $page_settings, string $policy, string $slug ) : void {
        // Get Value
        $value = ( isset( $page_settings[$slug] ) && $page_settings[$slug] == '1' ? '__return_true' : '__return_false' );
        // Add Filter
        add_filter( $slug, $value, 1 );
        // Add Policy
        $this->policies[] = $policy . $value;
    }

    /**
     * Content Policies
     *
     * @since  0.2.0
     */
    private function content() : void {
        //Janitor::log( 'running content().' );
        $settings_content = $this->get_page_settings( 'content' );
        $skip = false;
        if ( $settings_content['on'] == "1" ) {
            if ( isset( $this->user['roles']['author'] ) || isset( $this->user['roles']['editor'] ) || isset( $this->user['roles']['administrator'] ) || isset( $this->user['roles']['super_admin'] ) ) {
                // Skip Conditional Policies
                $skip = true;
            }
            if ( !$skip ) {
                // Disable Text Highlighting
                $this->add_policy( $settings_content, 'PolicyDisableTextHighlight', 'disable_text_highlight' );
                // Disable Right Click
                $this->add_policy( $settings_content, 'PolicyDisableRightClick', 'disable_right_click' );
            }
            // Hide Password Protected Posts
            $this->add_policy( $settings_content, 'PolicyHidePasswordProtectedPosts', 'hide_password_protected_posts' );
            // Log 404s
            $this->add_firewall_policy( [], 'PolicyLog404s' );
        }
    }

    /**
     * Stops the core authentication process
     *
     * @return void
     */
    public static function stop_authenticate_process() : void {
        remove_filter( 'authenticate', 'wp_authenticate_username_password', 20 );
        remove_filter( 'authenticate', 'wp_authenticate_email_password', 20 );
        remove_filter( 'authenticate', 'wp_authenticate_application_password', 20 );
        remove_filter( 'authenticate', 'wp_authenticate_spam_check', 99 );
    }

    /**
     * Checks to see if the IP has been whitelisted yet
     *
     * @return bool
     *
     * @since 2.0.0
     */
    function is_whitelisted() : bool {
        return $this->whitelisted;
    }

    /**
     * Checks to see if the IP has been blacklisted yet
     *
     * @return bool
     *
     * @since 2.0.0
     */
    function is_blacklisted() : bool {
        return $this->blacklisted;
    }

}
