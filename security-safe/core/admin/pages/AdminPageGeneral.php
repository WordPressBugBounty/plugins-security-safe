<?php

namespace SovereignStack\SecuritySafe;

// Prevent Direct Access
defined( 'ABSPATH' ) || die;
/**
 * Class AdminPageGeneral
 * @package SecuritySafe
 * @since  0.2.0
 */
class AdminPageGeneral extends AdminPage {
    /**
     * All General Tab Content
     * @return string
     * @since  0.3.0
     */
    public function tab_general() {
        // General Settings ================
        $html = $this->form_section( __( 'General Settings', SECSAFE_TRANSLATE ), false );
        // Shutoff Switch - All Security Policies
        $classes = ( $this->page_settings['on'] ? '' : 'notice-warning' );
        $rows = $this->form_select(
            $this->page_settings,
            __( 'All Security Policies', SECSAFE_TRANSLATE ),
            'on',
            [
                '0' => __( 'Disabled', SECSAFE_TRANSLATE ),
                '1' => __( 'Enabled', SECSAFE_TRANSLATE ),
            ],
            __( 'If you experience a problem, you may want to temporarily turn off all security policies at once to troubleshoot the issue. You can temporarily disable each type of policy at the top of each settings tab.', SECSAFE_TRANSLATE ),
            $classes
        );
        // Reset Settings
        $classes = '';
        $rows .= $this->form_button(
            __( 'Reset Settings', SECSAFE_TRANSLATE ),
            'link-delete',
            admin_url( 'admin.php?page=' . SECSAFE_SLUG . '&reset=1&_nonce_reset_settings=' . wp_create_nonce( SECSAFE_SLUG . '-reset-settings' ) ),
            __( 'Click this button to reset the settings back to default. WARNING: You will lose all configuration changes you have made.', SECSAFE_TRANSLATE ),
            $classes
        );
        // Cleanup Database
        $classes = '';
        $rows .= $this->form_checkbox(
            $this->page_settings,
            __( 'Cleanup Database When Disabling Plugin', SECSAFE_TRANSLATE ),
            'cleanup',
            __( 'Remove Settings, Logs, and Stats When Disabled', SECSAFE_TRANSLATE ),
            __( 'If you ever decide to permanently disable this plugin, you may want to remove our settings, logs, and stats from the database. WARNING: Do not check this box if you are temporarily disabling the plugin, you will loase all data associated with this plugin.', SECSAFE_TRANSLATE ),
            $classes,
            false
        );
        $classes = '';
        $rows .= $this->form_checkbox(
            $this->page_settings,
            __( 'Support Us', SECSAFE_TRANSLATE ),
            'byline',
            __( 'Display link to us below the login form.', SECSAFE_TRANSLATE ),
            __( '(This is optional)', SECSAFE_TRANSLATE ),
            $classes,
            false
        );
        $html .= $this->form_table( $rows );
        // Save Button
        $html .= $this->button( __( 'Save Settings', SECSAFE_TRANSLATE ) );
        return $html;
    }

    /**
     * All General Tab Content
     * @return string
     * @since  1.1.0
     */
    public function tab_info() {
        // Get Plugin Settings
        $site_settings = get_option( SECSAFE_OPTIONS );
        $html = '<h3>' . __( 'Current Settings', SECSAFE_TRANSLATE ) . '</h3>
                <table class="wp-list-table widefat fixed striped file-perm-table" cellpadding="10px">
                <thead><tr><th>' . __( 'Policies', SECSAFE_TRANSLATE ) . '</th><th>' . __( 'Setting', SECSAFE_TRANSLATE ) . '</th><th>' . __( 'Value', SECSAFE_TRANSLATE ) . '</th></tr></thead>';
        $labels = [
            'privacy'  => __( 'Privacy', SECSAFE_TRANSLATE ),
            'files'    => __( 'Files', SECSAFE_TRANSLATE ),
            'content'  => __( 'Content', SECSAFE_TRANSLATE ),
            'access'   => __( 'User Access', SECSAFE_TRANSLATE ),
            'firewall' => __( 'Firewall', SECSAFE_TRANSLATE ),
            'backups'  => __( 'Backups', SECSAFE_TRANSLATE ),
            'general'  => __( 'General', SECSAFE_TRANSLATE ),
            'plugin'   => __( 'Plugin', SECSAFE_TRANSLATE ),
        ];
        foreach ( $site_settings as $label => $section ) {
            if ( $label == 'plugin' ) {
                $html .= '<tr style="background: #e5e5e5;"><td><b>' . strtoupper( $labels[$label] ) . '</b></td><td colspan="2"></td></tr>';
            }
            foreach ( $section as $setting => $value ) {
                if ( $setting != 'version_history' ) {
                    if ( $setting == 'on' ) {
                        $html .= '<tr style="background: #e5e5e5;"><td><b>' . esc_html( strtoupper( $labels[$label] ) ) . '</b></td><td>' . esc_html( $setting ) . '</td><td>' . esc_html( $value ) . '</td></tr>';
                    } else {
                        $html .= '<tr><td></td><td>' . esc_html( $setting ) . '</td><td>' . esc_html( $value ) . '</td></tr>';
                    }
                }
            }
        }
        $html .= '</table>
                <p></p>
                <h3>' . esc_html__( 'Installed Plugin Version History', SECSAFE_TRANSLATE ) . '</h3>
                <ul>';
        $history = $site_settings['plugin']['version_history'];
        foreach ( $history as $past ) {
            $html .= '<li>' . esc_html( $past ) . '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    /**
     * Export/Import Tab Content
     * @return string
     * @since  1.2.0
     */
    public function tab_export_import() {
        // Export Settings ================
        $html = $this->form_section( __( 'Export Settings', SECSAFE_TRANSLATE ), sprintf( __( 'Click this button to export your current %s settings into a JSON file.', SECSAFE_TRANSLATE ), SECSAFE_NAME ) );
        $classes = '';
        $rows = $this->form_button(
            __( 'Export Current Settings', SECSAFE_TRANSLATE ),
            'submit',
            false,
            '',
            $classes,
            false,
            'export-settings'
        );
        $html .= $this->form_table( $rows );
        // Import Settings ================
        $html .= $this->form_section( __( 'Import Settings', SECSAFE_TRANSLATE ), sprintf( __( 'Select the %s JSON file you would like to import.', SECSAFE_TRANSLATE ), SECSAFE_NAME ) );
        $rows = $this->form_file_upload( __( 'Upload Setting', SECSAFE_TRANSLATE ), 'import-file' );
        $html .= $this->form_table( $rows );
        // Import Settings Button
        $html .= $this->button(
            __( 'Import Settings', SECSAFE_TRANSLATE ),
            'submit',
            false,
            'import-settings'
        );
        return $html;
    }

    /**
     * This sets the variables for the page.
     * @since  0.1.0
     */
    protected function set_page() {
        $plugin_name = SECSAFE_NAME;
        $this->slug = SECSAFE_SLUG;
        $this->title = sprintf( __( 'Welcome to %s', SECSAFE_TRANSLATE ), $plugin_name );
        $this->description = sprintf( __( 'Thank you for choosing %s to help protect your website.', SECSAFE_TRANSLATE ), $plugin_name );
        $this->tabs[] = [
            'id'               => 'settings',
            'label'            => __( 'Settings', SECSAFE_TRANSLATE ),
            'title'            => __( 'Plugin Settings', SECSAFE_TRANSLATE ),
            'heading'          => __( 'These are the general plugin settings.', SECSAFE_TRANSLATE ),
            'intro'            => '',
            'content_callback' => 'tab_general',
        ];
        $this->tabs[] = [
            'id'               => 'export-import',
            'label'            => __( 'Export/Import', SECSAFE_TRANSLATE ),
            'title'            => __( 'Export/Import Plugin Settings', SECSAFE_TRANSLATE ),
            'heading'          => '',
            'intro'            => '',
            'content_callback' => 'tab_export_import',
        ];
        $this->tabs[] = [
            'id'               => 'debug',
            'label'            => __( 'Debug', SECSAFE_TRANSLATE ),
            'title'            => __( 'Plugin Information', SECSAFE_TRANSLATE ),
            'heading'          => __( 'This information may be useful when troubleshooting compatibility issues.', SECSAFE_TRANSLATE ),
            'intro'            => '',
            'content_callback' => 'tab_info',
        ];
    }

}
