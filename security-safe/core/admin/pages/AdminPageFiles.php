<?php

namespace SovereignStack\SecuritySafe;

// Prevent Direct Access
defined( 'ABSPATH' ) || die;
/**
 * Class AdminPageFiles
 * @package SecuritySafe
 * @since  0.2.0
 */
class AdminPageFiles extends AdminPage {
    /**
     * This tab displays file settings.
     *
     * @since  0.2.0
     */
    function tab_settings() {
        global $wp_version;
        $html = '';
        // Shutoff Switch - All File Policies
        $classes = ( $this->page_settings['on'] ? '' : 'notice-warning' );
        $rows = $this->form_select(
            $this->page_settings,
            __( 'File Policies', SECSAFE_TRANSLATE ),
            'on',
            [
                '0' => __( 'Disabled', SECSAFE_TRANSLATE ),
                '1' => __( 'Enabled', SECSAFE_TRANSLATE ),
            ],
            __( 'If you experience a problem, you may want to temporarily turn off all file policies at once to troubleshoot the issue.', SECSAFE_TRANSLATE ),
            $classes
        );
        $html .= $this->form_table( $rows );
        // Automatic WordPress Updates ================
        $rows = '';
        $html .= $this->form_section( __( 'Automatic WordPress Updates', SECSAFE_TRANSLATE ), __( 'Updates are one of the main culprits to a compromised website.', SECSAFE_TRANSLATE ) );
        if ( version_compare( $wp_version, '3.7.0' ) >= 0 && !defined( 'AUTOMATIC_UPDATER_DISABLED' ) ) {
            $disabled = ( defined( 'WP_AUTO_UPDATE_CORE' ) ? true : false );
            $classes = '';
            $rows .= ( $disabled ? $this->form_text( __( '<b>NOTICE:</b> WordPress Automatic Core Updates are being controlled by the constant variable WP_AUTO_UPDATE_CORE in the wp-config.php file or by another plugin. As a result, Automatic Core Update feature settings for this plugin have been disabled.', SECSAFE_TRANSLATE ), 'notice-info' ) : '' );
            $rows .= $this->form_checkbox(
                $this->page_settings,
                __( 'Dev Core Updates', SECSAFE_TRANSLATE ),
                'allow_dev_auto_core_updates',
                __( 'Automatic Nightly Core Updates', SECSAFE_TRANSLATE ),
                __( 'Select this option if the site is in development only.', SECSAFE_TRANSLATE ),
                $classes,
                $disabled
            );
            $rows .= $this->form_checkbox(
                $this->page_settings,
                __( 'Major Core Updates', SECSAFE_TRANSLATE ),
                'allow_major_auto_core_updates',
                __( 'Automatic Major Core Updates', SECSAFE_TRANSLATE ),
                __( 'If you feel very confident in your code, you could automate the major version upgrades. (not recommended in most cases)', SECSAFE_TRANSLATE ),
                $classes,
                $disabled
            );
            $rows .= $this->form_checkbox(
                $this->page_settings,
                __( 'Minor Core Updates', SECSAFE_TRANSLATE ),
                'allow_minor_auto_core_updates',
                __( 'Automatic Minor Core Updates', SECSAFE_TRANSLATE ),
                __( 'This is enabled by default in WordPress and only includes minor version and security updates.', SECSAFE_TRANSLATE ),
                $classes,
                $disabled
            );
            $rows .= $this->form_checkbox(
                $this->page_settings,
                __( 'Plugin Updates', SECSAFE_TRANSLATE ),
                'auto_update_plugin',
                __( 'Automatic Plugin Updates', SECSAFE_TRANSLATE ),
                $classes,
                false
            );
            $rows .= $this->form_checkbox(
                $this->page_settings,
                __( 'Theme Updates', SECSAFE_TRANSLATE ),
                'auto_update_theme',
                __( 'Automatic Theme Updates', SECSAFE_TRANSLATE ),
                $classes,
                false
            );
        } else {
            if ( defined( 'AUTOMATIC_UPDATER_DISABLED' ) ) {
                $rows .= $this->form_text( __( '<b>NOTICE:</b> WordPress Automatic Updates are disabled by the constant variable AUTOMATIC_UPDATER_DISABLED in the wp-config.php file or by another plugin. As a result, Automatic Update features for this plugin have been disabled.', SECSAFE_TRANSLATE ), 'notice-info' );
            }
            if ( version_compare( $wp_version, '3.7.0' ) < 0 ) {
                $rows .= $this->form_text( sprintf( __( '<b>NOTICE:</b> You are using WordPress Version %s. The WordPress Automatic Updates feature controls require version 3.7 or greater.', SECSAFE_TRANSLATE ), $wp_version ), 'notice-info' );
            }
        }
        $html .= $this->form_table( $rows );
        // File Access
        $html .= $this->form_section( __( 'File Access', SECSAFE_TRANSLATE ), false );
        $classes = '';
        $rows = $this->form_checkbox(
            $this->page_settings,
            __( 'Theme File Editing', SECSAFE_TRANSLATE ),
            'DISALLOW_FILE_EDIT',
            __( 'Disable Theme Editing', SECSAFE_TRANSLATE ),
            __( 'Disable the ability for admin users to edit your theme files from the WordPress admin.', SECSAFE_TRANSLATE ),
            $classes,
            false
        );
        $rows .= $this->form_checkbox(
            $this->page_settings,
            __( 'WordPress Version Files', SECSAFE_TRANSLATE ),
            'version_files_core',
            __( 'Prevent Access', SECSAFE_TRANSLATE ),
            sprintf( __( 'Prevent access to files that disclose WordPress versions: readme.html and license.txt. <a href="%s">Also, see Software Privacy</a>', SECSAFE_TRANSLATE ), admin_url( 'admin.php?page=' . SECSAFE_SLUG . '-privacy#software-privacy' ) ),
            $classes,
            false
        );
        if ( !security_safe()->can_use_premium_code() ) {
            $rows .= $this->form_checkbox(
                $this->page_settings,
                __( 'Plugin Version Files', SECSAFE_TRANSLATE ),
                'version_files_plugins',
                sprintf( __( 'Prevent Access (<a href="%1$s">Pro Feature</a>)', SECSAFE_TRANSLATE ), SECSAFE_URL_MORE_INFO_PRO ),
                __( 'Prevent access to files that disclose plugin versions.', SECSAFE_TRANSLATE ),
                $classes,
                true
            );
            $rows .= $this->form_checkbox(
                $this->page_settings,
                __( 'Theme Version Files', SECSAFE_TRANSLATE ),
                'version_files_themes',
                sprintf( __( 'Prevent Access (<a href="%s">Pro Feature</a>)', SECSAFE_TRANSLATE ), SECSAFE_URL_MORE_INFO_PRO ),
                __( 'Prevent access to files that disclose plugin versions.', SECSAFE_TRANSLATE ),
                $classes,
                true
            );
        }
        $html .= $this->form_table( $rows );
        // Save Button
        $html .= $this->button( __( 'Save Settings', SECSAFE_TRANSLATE ) );
        return $html;
    }

    /**
     * This tab displays current and suggested file permissions.
     *
     * @since  1.0.3
     */
    function tab_core() {
        // Determine File Structure
        $plugins_dir = ( defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : dirname( dirname( __DIR__ ) ) );
        $content_dir = ( defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : ABSPATH . 'wp-content' );
        $muplugins_dir = ( defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR : $content_dir . '/mu-plugins' );
        $uploads_dir = wp_upload_dir();
        $uploads_dir = $uploads_dir["basedir"];
        $themes_dir = dirname( get_template_directory() );
        // Array of Files To Be Checked
        $paths = [
            $uploads_dir,
            $plugins_dir,
            $muplugins_dir,
            $themes_dir
        ];
        // Remove Trailing Slash
        $base = str_replace( '//', '', ABSPATH . '/' );
        // Get All Files / Folders In Base Directory
        $base = $this->get_dir_files( $base, false );
        // Combine File List
        $paths = array_merge( $base, $paths );
        // Get Rid of Duplicates
        $paths = array_unique( $paths );
        return $this->display_permissions_table( $paths );
    }

    /**
     * Display all file permissions in a table
     *
     * @param array | bool $paths An array of absolute paths
     * @param string | bool $tab Tab identification: used to determine features for one tab versus another.
     *
     * @return string
     *
     * @since  1.0.3
     */
    private function display_permissions_table( $paths = false, $tab = false ) {
        $html = '';
        $tr_error = '';
        $tr_warning = '';
        $tr_notice = '';
        $tr_secure = '';
        // By Default hide rows
        $show_row = false;
        // Flag to signal there are no file period
        $no_files = false;
        // Count of rows that can be modified
        $modify_rows = 0;
        if ( is_array( $paths ) && !empty( $paths ) ) {
            $file_count = 0;
            $notice = [];
            $notice['dirs'] = 0;
            $notice['files'] = 0;
            $error = [];
            $error['dirs'] = 0;
            $error['files'] = 0;
            $warning = [];
            $warning['dirs'] = 0;
            $warning['files'] = 0;
            foreach ( $paths as $p ) {
                if ( file_exists( $p ) ) {
                    // Get Relative Path
                    $rel_path = str_replace( [ABSPATH, '//'], '/', $p );
                    // Get File Type
                    $is_dir = is_dir( $p );
                    // Get Details of Path
                    $info = @stat( $p );
                    $permissions = sprintf( '%o', $info['mode'] );
                    // Get all info about permissions
                    $current = substr( $permissions, -3 );
                    // Get current o/g/w permissions
                    $perm = str_split( $current );
                    // Convert permissions to an array
                    // Specific Role Permissions
                    $owner = ( isset( $perm[0] ) ? $perm[0] : 0 );
                    $group = ( isset( $perm[1] ) ? $perm[1] : 0 );
                    $world = ( isset( $perm[2] ) ? $perm[2] : 0 );
                    $notice_class = '';
                    if ( $rel_path == '/' ) {
                        $type = 'directory';
                        $status = 'default';
                    } else {
                        // Determine Directory or File
                        if ( $is_dir ) {
                            $type = 'directory';
                            $min = '775';
                            // Standard
                            $min = $this->get_pantheon_permissions( $p, $min );
                            $sec = $this->get_secure_perms( $p, 'dir' );
                            if ( $current == $min || $current == $sec ) {
                                $status = ( $current == $sec ? 'secure' : 'notice' );
                                // Count Good Directories and Display Notice that they could be better
                                if ( $status == 'notice' ) {
                                    $notice['dirs'] = $notice['dirs'] + 1;
                                    $notice_class = 'notice-info';
                                }
                            } else {
                                // Ceiling
                                $status = ( $world > 5 ? 'error' : 'warning' );
                                if ( $status == 'error' ) {
                                    $error['dirs'] = $error['dirs'] + 1;
                                    $notice_class = 'notice-error';
                                } else {
                                    $warning['dirs'] = $warning['dirs'] + 1;
                                    $notice_class = 'notice-warning';
                                }
                            }
                        } else {
                            $type = 'file';
                            $min = '644';
                            // Standard
                            $min = $this->get_pantheon_permissions( $p, $min );
                            $sec = $this->get_secure_perms( $p, 'file' );
                            if ( $current == $min || $current == $sec ) {
                                if ( $min == $sec ) {
                                    $status = 'secure';
                                } else {
                                    $status = ( $current == $sec ? 'secure' : 'notice' );
                                    if ( $status == 'notice' ) {
                                        $notice['files'] = $notice['files'] + 1;
                                        $notice_class = 'notice-info';
                                    }
                                }
                            } else {
                                // Ceiling
                                $status = ( $owner > 6 || $group > 4 || $world > 4 ? 'error' : 'warning' );
                                // Floor
                                $status = ( $owner < 4 || $group < 0 || $world < 0 ? 'error' : $status );
                                if ( $status == 'error' ) {
                                    $error['files'] = $error['files'] + 1;
                                    $notice_class = 'notice-error';
                                } else {
                                    $warning['files'] = $warning['files'] + 1;
                                    $notice_class = 'notice-warning';
                                }
                            }
                        }
                        // Create Standard Option
                        $option_min = ( $status != 'notice' && $min != $current ? '<option value="' . esc_attr( $min . '|' . $rel_path ) . '">' . esc_html( $min . ' - ' . __( 'Standard', SECSAFE_TRANSLATE ) ) . '</option>' : false );
                        if ( !security_safe()->can_use_premium_code() ) {
                            if ( $tab != 'tab_plugins' && $tab != 'tab_theme' ) {
                                // Create Secure Option
                                $option_sec = ( $status != 'secure' ? '<option value="' . esc_attr( $sec . '|' . $rel_path ) . '">' . esc_html( $sec . ' - ' . __( 'Secure', SECSAFE_TRANSLATE ) ) . '</option>' : false );
                                $option_sec = ( $min == $sec ? false : $option_sec );
                            } else {
                                $option_sec = false;
                            }
                        }
                        if ( $option_min || $option_sec ) {
                            $file_count++;
                            $modify_rows++;
                            $show_row = true;
                            // Create Select Dropdown
                            $select = '<select name="file-' . esc_attr( $file_count ) . '"><option value="-1"> -- ' . esc_html__( 'Select One', SECSAFE_TRANSLATE ) . ' -- </option>';
                            $select .= ( $option_min ? $option_min : '' );
                            $select .= ( $option_sec ? $option_sec : '' );
                            $select .= '</select>';
                        } else {
                            $select = '-';
                            if ( !security_safe()->can_use_premium_code() ) {
                                // Upgrade to Pro to modify this file
                                $select = ( $status != 'secure' && $min != $sec ? '<a href="' . esc_url( SECSAFE_URL_MORE_INFO_PRO ) . '">' . esc_html__( 'Upgrade to Pro', SECSAFE_TRANSLATE ) . '</a>' : '-' );
                            }
                            // Hide rows that cannot be modified.
                            if ( $select == '-' ) {
                                // Use flag to hide rows you cannot modify
                                $show_row = ( REQUEST::int( 'show_no_modify' ) ? true : false );
                            } else {
                                // Show rows that could get modified
                                $show_row = true;
                            }
                        }
                    }
                    if ( $show_row ) {
                        $groups = '<tr class="' . esc_attr( $notice_class ) . '">
                                        <td>' . esc_html( $rel_path ) . '</td>
                                        <td style="text-align: center;">' . esc_html( $type ) . '</td>
                                        <td style="text-align: center;">' . esc_html( $owner . $group . $world ) . '</td>
                                        <td class="' . esc_attr( strtolower( $status ) ) . '" style="text-align: center;">' . AdminPageFiles::display_status( $status ) . '</td>';
                        $groups .= ( $rel_path == '/' ? '<td style="text-align: center;"> - </td>' : '<td style="text-align: center;">' . $select . '</td>' );
                        $groups .= '</tr>';
                        // Separate types of problems into groups
                        if ( $notice_class == 'notice-error' ) {
                            $tr_error .= $groups;
                        } elseif ( $notice_class == 'notice-warning' ) {
                            $tr_warning .= $groups;
                        } elseif ( $notice_class == 'notice-info' ) {
                            $tr_notice .= $groups;
                        } else {
                            $tr_secure .= $groups;
                        }
                    }
                }
            }
        } else {
            // No files to check
            $no_files = true;
        }
        // Display Notices
        $this->display_notices_perms( $notice, $warning, $error );
        $table = '
            <table class="wp-list-table widefat fixed striped file-perm-table">
                <thead>
                    <tr>
                        <th class="manage-column">' . esc_html__( 'Relative Location', SECSAFE_TRANSLATE ) . '</th>
                        <th class="manage-column" style="width: 100px;">' . esc_html__( 'Type', SECSAFE_TRANSLATE ) . '</th>
                        <th class="manage-column" style="width: 75px;">' . esc_html__( 'Current', SECSAFE_TRANSLATE ) . '</th>
                        <th class="manage-column" style="width: 70px;">' . esc_html__( 'Status', SECSAFE_TRANSLATE ) . '</th>
                        <th class="manage-column" style="width: 160px;">' . esc_html__( 'Modify', SECSAFE_TRANSLATE ) . '</th>
                    </tr>
                </thead>';
        // Show All Files
        $show_limited_link = admin_url( 'admin.php?page=' . SECSAFE_SLUG . '-files&tab=' . REQUEST::key( 'tab' ) );
        $show_all_link = $show_limited_link . '&show_no_modify=1';
        // Show message that no files exist to display or check
        if ( $no_files ) {
            $table .= '<tr><td colspan="5">' . esc_html__( 'Error: There were not any files to check.', SECSAFE_TRANSLATE ) . '</td></tr>';
        } else {
            $table .= ( REQUEST::int( 'show_no_modify' ) ? '<tr><td><i><a href="' . esc_url( $show_limited_link ) . '" style="text-decoration: underline;">' . esc_html__( 'Hide files that cannot be modified.', SECSAFE_TRANSLATE ) . '</a></i></td>' : '<tr><td>' . sprintf( __( '<i>NOTICE: Files which cannot be modified are hidden. <a href="%s" style="text-decoration: underline;">Show all files</a>.</i>', SECSAFE_TRANSLATE ), $show_all_link ) . '</td>' );
            if ( $modify_rows > 0 ) {
                if ( security_safe()->can_use_premium_code() ) {
                    // Show Fix All Option
                    $table .= '<td colspan="3"><select id="fixall" name="fixall" ><option value="-1">-- ' . esc_html__( 'Batch Options', SECSAFE_TRANSLATE ) . ' --</option><option value="1">' . esc_html__( 'Set All To Standard', SECSAFE_TRANSLATE ) . '</option><option value="2">' . esc_html__( 'Set All To Secure', SECSAFE_TRANSLATE ) . '</option></select></td>';
                } else {
                    // Show Fix All Option
                    $table .= '<td colspan="3"><select id="fixall" name="fixall" ><option value="-1">-- ' . esc_html__( 'Batch Options (Pro)', SECSAFE_TRANSLATE ) . ' --</option><option value="1">' . esc_html__( 'Set All To Standard', SECSAFE_TRANSLATE ) . ' (Pro)</option><option value="2">' . esc_html__( 'Set All To Secure', SECSAFE_TRANSLATE ) . ' (Pro)</option></select></td>';
                }
                // Show Update Button
                $table .= '<td>' . $this->button( __( 'Update Permissions', SECSAFE_TRANSLATE ) ) . '</td></tr>';
            } else {
                $table .= '<td colspan="3"></td><td></td>';
            }
        }
        // Display Table
        $html .= $table;
        if ( $tr_error || $tr_warning || $tr_notice || $tr_secure ) {
            $html .= $tr_error . $tr_warning . $tr_notice . $tr_secure;
            if ( $modify_rows > 0 ) {
                // Show Update Permissions Button
                $html .= '<tr><td colspan="4"></td><td>' . $this->button( __( 'Update Permissions', SECSAFE_TRANSLATE ) ) . '</td></tr>';
            }
        } else {
            // No rows to display
            if ( $modify_rows == 0 ) {
                // Only show if there are files, but they are all hidden
                $html .= '<tr><td colspan="4">' . esc_html__( 'No files to modify. The rest of the files are hidden.' ) . '</td><td>-</td></tr>';
            }
        }
        $html .= '</table>';
        return $html;
    }

    /**
     * Returns the label of the status that is I18n compatible
     *
     * @param string $current
     *
     * @return string
     *
     * @since  2.2.0
     */
    private static function display_status( $current ) {
        $status = [
            'warning' => __( 'Warning', SECSAFE_TRANSLATE ),
            'error'   => __( 'Error', SECSAFE_TRANSLATE ),
            'notice'  => __( 'Notice', SECSAFE_TRANSLATE ),
            'secure'  => __( 'Secure', SECSAFE_TRANSLATE ),
        ];
        return ( isset( $status[$current] ) ? $status[$current] : ucwords( $current ) );
    }

    /**
     * Displays the current status of files that are not secure.
     *
     * @param array $notice
     * @param array $warning
     * @param array $error
     *
     * @since  1.1.4
     */
    private function display_notices_perms( $notice, $warning, $error ) {
        global $SecuritySafe;
        // Good Directories
        if ( isset( $notice['dirs'] ) && $notice['dirs'] > 0 ) {
            if ( $notice['dirs'] > 1 ) {
                // Plural
                $message = sprintf( __( 'You have %d directories that could be more secure.', SECSAFE_TRANSLATE ), $notice['dirs'] );
            } else {
                // Singular
                $message = sprintf( __( 'You have %d directory that could be more secure.', SECSAFE_TRANSLATE ), $notice['dirs'] );
            }
            $SecuritySafe->messages[] = [$message, 1, 1];
        }
        // Good Files
        if ( isset( $notice['files'] ) && $notice['files'] > 0 ) {
            if ( $notice['files'] > 1 ) {
                // Plural
                $message = sprintf( __( 'You have %d files that could be more secure.', SECSAFE_TRANSLATE ), $notice['files'] );
            } else {
                // Singular
                $message = sprintf( __( 'You have %d file that could be more secure.', SECSAFE_TRANSLATE ), $notice['files'] );
            }
            $SecuritySafe->messages[] = [$message, 1, 1];
        }
        // OK Directories
        if ( isset( $warning['dirs'] ) && $warning['dirs'] > 0 ) {
            if ( $warning['dirs'] > 1 ) {
                // Plural
                $message = sprintf( __( 'You have %d directories with safe but unique file permissions. This might cause functionality issues.', SECSAFE_TRANSLATE ), $warning['dirs'] );
            } else {
                // Singular
                $message = sprintf( __( 'You have %d directory with safe but unique file permissions. This might cause functionality issues.', SECSAFE_TRANSLATE ), $warning['dirs'] );
            }
            $SecuritySafe->messages[] = [$message, 2, 1];
        }
        // OK Files
        if ( isset( $warning['files'] ) && $warning['files'] > 0 ) {
            if ( $warning['files'] > 1 ) {
                // Plural
                $message = sprintf( __( 'You have %d files with safe but unique file permissions. This might cause functionality issues.', SECSAFE_TRANSLATE ), $warning['files'] );
            } else {
                // Singular
                $message = sprintf( __( 'You have %d file with safe but unique file permissions. This might cause functionality issues.', SECSAFE_TRANSLATE ), $warning['files'] );
            }
            $SecuritySafe->messages[] = [$message, 2, 1];
        }
        // Bad Directories
        if ( isset( $error['dirs'] ) && $error['dirs'] > 0 ) {
            if ( $error['dirs'] > 1 ) {
                // Plural
                $message = sprintf( __( 'You have %d directories that are vulnerable.', SECSAFE_TRANSLATE ), $error['dirs'] );
            } else {
                // Singular
                $message = sprintf( __( 'You have %d directory that is vulnerable.', SECSAFE_TRANSLATE ), $error['dirs'] );
            }
            $SecuritySafe->messages[] = [$message, 3, 0];
        }
        // Bad Files
        if ( isset( $error['files'] ) && $error['files'] > 0 ) {
            if ( $error['files'] > 1 ) {
                // Plural
                $message = sprintf( __( 'You have %d files that are vulnerable.', SECSAFE_TRANSLATE ), $error['files'] );
            } else {
                // Singular
                $message = sprintf( __( 'You have %d files that is vulnerable.', SECSAFE_TRANSLATE ), $error['files'] );
            }
            $SecuritySafe->messages[] = [$message, 3, 0];
        }
        // PHP Notices
        if ( isset( $warning['php'] ) && is_array( $warning['php'] ) ) {
            $PHP_major = substr( $warning['php'][1], 0, 1 );
            $PHP_changelog = 'https://secure.php.net/ChangeLog-' . $PHP_major . '.php';
            $message = sprintf(
                __( 'You have PHP version %1$s and it needs to be updated to version %2$s or higher. If version %2$s was released more than 30 days ago and there is more than a 90-day timespan between PHP version %1$s and %2$s (<a href="%3$s" target="_blank">see changelog</a>), contact your hosting provider to upgrade PHP.', SECSAFE_TRANSLATE ),
                $warning['php'][0],
                $warning['php'][1],
                $PHP_changelog
            );
            $SecuritySafe->messages[] = [$message, 2, 0];
        }
        if ( isset( $error['php'] ) && is_array( $error['php'] ) ) {
            $message = sprintf( __( 'You are using PHP version %1$s, which is no longer supported or has critical vulnerabilities. Immediately contact your hosting company to upgrade PHP to version %2$s or higher.', SECSAFE_TRANSLATE ), $error['php'][0], $error['php'][1] );
            $SecuritySafe->messages[] = [$message, 3, 0];
        }
        // Display Notices Created In This File
        $SecuritySafe->display_notices( true );
    }

    /**
     * This tab displays current and suggested file permissions.
     *
     * @since  1.0.3
     */
    function tab_theme() {
        $theme_parent = get_template_directory();
        $theme_child = get_stylesheet_directory();
        $files = $this->get_dir_files( $theme_parent );
        if ( $theme_parent != $theme_child ) {
            // Child Theme Present
            $child_files = $this->get_dir_files( $theme_child );
            $files = array_merge( $child_files, $files );
        }
        return $this->display_permissions_table( $files, 'tab_theme' );
    }

    /**
     * This tab displays current and suggested file permissions.
     *
     * @since  1.1.0
     */
    function tab_uploads() {
        $uploads_dir = wp_upload_dir();
        return $this->display_permissions_table( $this->get_dir_files( $uploads_dir["basedir"] ) );
    }

    /**
     * This tab displays current and suggested file permissions.
     *
     * @since  1.0.3
     */
    function tab_plugins() {
        $plugins_dir = ( defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : dirname( dirname( __DIR__ ) ) );
        return $this->display_permissions_table( $this->get_dir_files( $plugins_dir ), 'tab_plugins' );
    }

    /**
     * This tab displays software installed on the server.
     *
     * @since  1.0.3
     */
    function tab_server() {
        $html = '';
        // Latest Versions
        $latest_versions = [];
        $latest_versions['PHP'] = Yoda::get_php_versions();
        $php_min = $latest_versions['PHP']['min'];
        unset($latest_versions['PHP']['min']);
        $warning = [];
        $warning['php'] = false;
        $error = [];
        $error['php'] = false;
        $PHP_VERSION = ( defined( 'PHP_VERSION' ) ? PHP_VERSION : false );
        //$PHP_VERSION = '7.4.16'; // test only
        $notice_class = '';
        $html .= '
            <table class="wp-list-table widefat fixed striped file-perm-table" cellpadding="10px">
                <thead>
                    <tr>
                        <th class="manage-column">' . esc_html__( 'Description', SECSAFE_TRANSLATE ) . '</th>
                        <th class="manage-column" style="width: 250px;">' . esc_html__( 'Current Version', SECSAFE_TRANSLATE ) . '</th>
                        <th class="manage-column" style="width: 250px;">' . esc_html__( 'Recommend', SECSAFE_TRANSLATE ) . '</th>
                        <th class="manage-column" style="width: 75px;">' . esc_html__( 'Status', SECSAFE_TRANSLATE ) . '</th>
                    </tr>
                </thead>';
        $versions = [];
        // PHP Version
        if ( $PHP_VERSION ) {
            $status = '';
            $recommend = '';
            if ( in_array( $PHP_VERSION, $latest_versions['PHP'] ) ) {
                // PHP Version Is Secure
                $status = __( 'Secure', SECSAFE_TRANSLATE );
                $recommend = $PHP_VERSION;
            } elseif ( version_compare( $PHP_VERSION, $php_min, '<' ) ) {
                // This Version Is Vulnerable
                $status = __( 'Bad', SECSAFE_TRANSLATE );
                $recommend = $latest_versions['PHP'][$php_min];
                $error['php'] = [$PHP_VERSION, $php_min];
                $notice_class = 'notice-error';
            } else {
                // Needs Update To Latest Secure Patch Version
                foreach ( $latest_versions['PHP'] as $minor => $patch ) {
                    if ( version_compare( $PHP_VERSION, $minor, '>=' ) ) {
                        if ( version_compare( $PHP_VERSION, $patch, '>=' ) ) {
                            // Prevent us from recommending a lower version
                            $status = __( 'Secure', SECSAFE_TRANSLATE );
                            $recommend = $PHP_VERSION;
                        } else {
                            $status = __( 'OK', SECSAFE_TRANSLATE );
                            $recommend = $patch;
                            $warning['php'] = [$PHP_VERSION, $patch];
                            $notice_class = 'notice-warning';
                        }
                        break;
                    }
                }
            }
            $versions[] = [
                'name'      => 'PHP',
                'current'   => $PHP_VERSION,
                'recommend' => $recommend,
                'status'    => $status,
                'class'     => $notice_class,
            ];
        }
        // Get All Versions From phpinfo
        $phpinfo = $this->get_phpinfo( 8 );
        if ( !empty( $phpinfo ) ) {
            foreach ( $phpinfo as $name => $section ) {
                foreach ( $section as $key => $val ) {
                    if ( strpos( strtolower( $key ), 'version' ) !== false && strpos( strtolower( $key ), 'php version' ) === false ) {
                        if ( is_array( $val ) ) {
                            $current = $val[0];
                        } elseif ( is_string( $key ) ) {
                            $current = $val;
                        }
                        // Remove Duplicate Text
                        $name = $name . ': ' . str_replace( $name, '', $key );
                        $versions[] = [
                            'name'      => $name,
                            'current'   => $current,
                            'recommend' => '-',
                            'status'    => '-',
                            'class'     => '',
                        ];
                    }
                }
            }
        }
        // Display All Version
        foreach ( $versions as $v ) {
            $html .= '<tr class="' . esc_attr( $v['class'] ) . '">
                        <td>' . esc_html( $v['name'] ) . '</td>
                        <td style="text-align: center;">' . esc_html( $v['current'] ) . '</td>
                        <td style="text-align: center;">' . esc_html( $v['recommend'] ) . '</td>
                        <td class="' . esc_attr( strtolower( $v['status'] ) ) . '" style="text-align: center;">' . esc_html( $v['status'] ) . '</td>
                        </tr>';
        }
        // If phpinfo is disabled, display notice
        if ( empty( $phpinfo ) ) {
            $html .= '<tr><td colspan="4">' . sprintf( __( 'The phpinfo() function is disabled. You may need to contact the hosting provider to enable this function for more advanced version details. <a href="%s">See the documentation.</a>', SECSAFE_TRANSLATE ), 'https://php.net/manual/en/function.phpinfo.php' ) . '</td></tr>';
        }
        $html .= '</table>';
        // Display Notices
        $this->display_notices_perms( [], $warning, $error );
        return $html;
    }

    /**
     * Returns phpinfo as an array
     *
     * @param int $type
     *
     * @return array
     *
     * @since  1.0.3
     */
    private function get_phpinfo( $type = 1 ) {
        ob_start();
        phpinfo( $type );
        $phpinfo = [];
        $pattern = '#(?:<h2>(?:<a name=".*?">)?(.*?)(?:</a>)?</h2>)|(?:<tr(?: class=".*?")?><t[hd](?: class=".*?")?>(.*?)\\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\\s*</t[hd]>(?:<t[hd](?: class=".*?")?>(.*?)\\s*</t[hd]>)?)?</tr>)#s';
        if ( preg_match_all(
            $pattern,
            ob_get_clean(),
            $matches,
            PREG_SET_ORDER
        ) ) {
            foreach ( $matches as $m ) {
                if ( strlen( $m[1] ) ) {
                    $phpinfo[$m[1]] = [];
                } else {
                    $keys = array_keys( $phpinfo );
                    if ( isset( $m[3] ) ) {
                        $phpinfo[end( $keys )][$m[2]] = ( isset( $m[4] ) ? [$m[3], $m[4]] : $m[3] );
                    } else {
                        $phpinfo[end( $keys )][] = $m[2];
                    }
                }
            }
        }
        return $phpinfo;
    }

    /**
     * This sets the variables for the page.
     *
     * @since  0.1.0
     */
    protected function set_page() {
        // Fix Permissions
        $this->fix_permissions();
        $this->slug = SECSAFE_SLUG . '-files';
        $this->title = __( 'Files & Folders', SECSAFE_TRANSLATE );
        $this->description = __( 'It is essential to keep all files updated and ensure only authorized users can access them.', SECSAFE_TRANSLATE );
        $this->tabs[] = [
            'id'               => 'settings',
            'label'            => __( 'Settings', SECSAFE_TRANSLATE ),
            'title'            => __( 'File Settings', SECSAFE_TRANSLATE ),
            'heading'          => false,
            'intro'            => false,
            'content_callback' => 'tab_settings',
        ];
        $this->tabs[] = [
            'id'               => 'core',
            'label'            => __( 'Core', SECSAFE_TRANSLATE ),
            'title'            => __( 'WordPress Base Directory & Files', SECSAFE_TRANSLATE ),
            'heading'          => __( 'Check to make sure all file permissions set correctly.', SECSAFE_TRANSLATE ),
            'intro'            => __( 'Incorrect directory or file permission values can lead to security vulnerabilities or even plugins or themes not functioning as intended. If you are not sure what values to set for a file or directory, use the standard recommended value.', SECSAFE_TRANSLATE ),
            'classes'          => ['full'],
            'content_callback' => 'tab_core',
        ];
        $this->tabs[] = [
            'id'               => 'theme',
            'label'            => __( 'Theme', SECSAFE_TRANSLATE ),
            'title'            => __( 'Theme Audit', SECSAFE_TRANSLATE ),
            'heading'          => __( 'Check to make sure all theme file permissions set correctly.', SECSAFE_TRANSLATE ),
            'intro'            => __( 'If you use "Secure" permission settings, and experience problems, just set the file permissions back to "Standard."', SECSAFE_TRANSLATE ),
            'classes'          => ['full'],
            'content_callback' => 'tab_theme',
        ];
        $this->tabs[] = [
            'id'               => 'uploads',
            'label'            => __( 'Uploads', SECSAFE_TRANSLATE ),
            'title'            => __( 'Uploads Directory Audit', SECSAFE_TRANSLATE ),
            'heading'          => __( 'Check to make sure all uploaded files have proper permissions.', SECSAFE_TRANSLATE ),
            'intro'            => '',
            'classes'          => ['full'],
            'content_callback' => 'tab_uploads',
        ];
        $tab_plugins_intro = __( 'WordPress sets file permissions to minimum safe values by default when you install or update plugins. You will likely find file permission issues after migrating a site from one server to another. The file permissions for a plugin will get fixed when you perform an update on that particular plugin. We would recommend correcting any issues labeled "error" immediately, versus waiting for an update.', SECSAFE_TRANSLATE );
        if ( security_safe()->is_not_paying() ) {
            $tab_plugins_intro .= '<br /><br /><b>' . esc_html__( 'Batch Plugin Permissions', SECSAFE_TRANSLATE ) . '</b> (<a href="' . esc_url( SECSAFE_URL_MORE_INFO_PRO ) . '">' . esc_html__( 'Pro Feature', SECSAFE_TRANSLATE ) . '</a>) - ' . esc_html__( 'You can change all plugin permissions to Standard or Secure permissions with one click.', SECSAFE_TRANSLATE );
            $tab_plugins_intro .= '<br /><br /><b>' . esc_html__( 'Prevent Plugin Version Snooping', SECSAFE_TRANSLATE ) . '</b> (<a href="' . esc_url( SECSAFE_URL_MORE_INFO_PRO ) . '">' . esc_html__( 'Pro Feature', SECSAFE_TRANSLATE ) . '</a>) - ' . esc_html__( 'Prevent access to plugin version files.', SECSAFE_TRANSLATE );
            $tab_plugins_intro .= '<br /><br /><b>' . esc_html__( 'Maintain Secure Permissions', SECSAFE_TRANSLATE ) . '</b> (<a href="' . esc_url( SECSAFE_URL_MORE_INFO_PRO ) . '">' . esc_html__( 'Pro Feature', SECSAFE_TRANSLATE ) . '</a>) - ' . esc_html__( 'Pro will automatically fix your file permissions after an core, plugin, and theme update.', SECSAFE_TRANSLATE );
        }
        $this->tabs[] = [
            'id'               => 'plugins',
            'label'            => __( 'Plugins', SECSAFE_TRANSLATE ),
            'title'            => __( 'Plugins Audit', SECSAFE_TRANSLATE ),
            'heading'          => __( 'When plugin updates run, they will overwrite your permission changes.', SECSAFE_TRANSLATE ),
            'intro'            => $tab_plugins_intro,
            'classes'          => ['full'],
            'content_callback' => 'tab_plugins',
        ];
        $this->tabs[] = [
            'id'               => 'server',
            'label'            => __( 'Server', SECSAFE_TRANSLATE ),
            'title'            => __( 'Server Information', SECSAFE_TRANSLATE ),
            'heading'          => __( "It is your hosting provider's job to keep your server up-to-date.", SECSAFE_TRANSLATE ),
            'intro'            => __( 'This table below will help identify the software versions currently on your hosting server. <br>NOTE: System administrators often do server updates once per month. If something is a version behind, then you might be between update cycles or there may be compatibility issues due to version dependencies.', SECSAFE_TRANSLATE ),
            'classes'          => ['full'],
            'content_callback' => 'tab_server',
        ];
    }

    /**
     * Fix File Permissions
     *
     * @since  1.1.0
     */
    private function fix_permissions() {
        global $SecuritySafe;
        if ( isset( $_POST ) && !empty( $_POST ) ) {
            $tab = REQUEST::key( 'tab' );
            if ( $tab && in_array( $tab, [
                'core',
                'theme',
                'plugins',
                'uploads'
            ] ) ) {
                if ( isset( $_POST['fixall'] ) && ($_POST['fixall'] == '1' || $_POST['fixall'] == '2') ) {
                    $fixall_active = false;
                    if ( $fixall_active === false ) {
                        $SecuritySafe->messages[] = [__( 'The batch file permissions feature requires an active Pro license.', SECSAFE_TRANSLATE ) . ' <a href="' . esc_url( SECSAFE_URL_MORE_INFO_PRO ) . '">' . esc_html__( 'Upgrade to Pro', SECSAFE_TRANSLATE ) . '</a>', 3, 0];
                    }
                } else {
                    // Add Notice To Look At Process Log
                    $SecuritySafe->messages[] = [__( 'Please review the Process Log below for details.', SECSAFE_TRANSLATE ), 1, 0];
                    // Sanitize $_POST Before We Do Anything
                    $post = REQUEST::POST();
                    foreach ( $post as $name => $value ) {
                        $v = explode( '|', $value );
                        if ( strpos( $name, 'file-' ) === false || $v[0] == '0' ) {
                            // Pass On This One
                        } else {
                            if ( isset( $v[1] ) && isset( $v[0] ) ) {
                                $this->set_permissions( $v[1], $v[0] );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Grabs all the files and folders for a provided directory. It scans in-depth by default.
     *
     * @param string $folder
     * @param bool $deep
     *
     * @return array
     *
     * @since  1.0.3
     */
    private function get_dir_files( $folder, $deep = true ) {
        // Scan All Files In Directory
        $files = scandir( $folder );
        $results = [];
        foreach ( $files as $file ) {
            if ( in_array( $file, ['.', '..'] ) ) {
                if ( $file == '.' ) {
                    $abspath = $folder . '/';
                    if ( $abspath == ABSPATH ) {
                        $results[] = ABSPATH;
                    } else {
                        $results[] = $folder;
                    }
                }
            } elseif ( is_dir( $folder . '/' . $file ) ) {
                if ( $deep ) {
                    //It is a dir; let's scan it
                    $array_results = $this->get_dir_files( $folder . '/' . $file );
                    foreach ( $array_results as $r ) {
                        $results[] = $r;
                    }
                    // foreach()
                } else {
                    // Add folder to list and do not scan it.
                    $results[] = $folder . '/' . $file;
                }
            } else {
                //It is a file
                $results[] = $folder . '/' . $file;
            }
        }
        return $results;
    }

    /**
     * Retrieves secure permissions value for a particular type of file
     *
     * @param  string $p Path of file
     * @param  string $type file or dir
     *
     * @return  string returns the recommended secure permissions value or false if bad input
     *
     * @since  1.2.0
     */
    function get_secure_perms( $p, $type ) {
        $sec = false;
        // Force lowercase for faster search
        $p = strtolower( $p );
        if ( $type == 'file' ) {
            $sec = '644';
            // Secure
            // Secure Permissions for certain files
            // https://codex.wordpress.org/Changing_File_Permissions#Finding_Secure_File_Permissions
            if ( strpos( $p, '.txt' ) ) {
                $sec = ( strpos( $p, 'readme.txt' ) ? '640' : $sec );
                $sec = ( $sec == '644' && strpos( $p, 'changelog.txt' ) ? '640' : $sec );
                $sec = ( $sec == '644' && strpos( $p, 'license.txt' ) ? '640' : $sec );
            } elseif ( strpos( $p, '.md' ) ) {
                $sec = ( strpos( $p, 'readme.md' ) ? '640' : $sec );
                $sec = ( $sec == '644' && strpos( $p, 'changelog.md' ) ? '640' : $sec );
            } else {
                $sec = ( strpos( $p, 'readme.html' ) ? '640' : $sec );
                $sec = ( $sec == '644' && strpos( $p, 'wp-config.php' ) ? '600' : $sec );
                $sec = ( $sec == '644' && strpos( $p, 'php.ini' ) ? '600' : $sec );
            }
        } elseif ( $type == 'dir' ) {
            // Default permissions
            $sec = '755';
        }
        // Pantheon.io Compatibility
        return $this->get_pantheon_permissions( $p, $sec );
    }

    /**
     * Get Pantheon's Secure permissions for all files and directories in uploads directory
     * @param $perms
     */
    function get_pantheon_permissions( $p, $perms ) {
        if ( isset( $_ENV['PANTHEON_ENVIRONMENT'] ) ) {
            // Pantheon servers have 770 perms for uploads directories
            // Get Uploads Directory info
            $uploads_dir = wp_upload_dir();
            $uploads_dir = ( isset( $uploads_dir["basedir"] ) ? strtolower( $uploads_dir["basedir"] ) : false );
            // Check to see if we are in the uploads directory
            if ( $uploads_dir && ($p == $uploads_dir || strpos( $p, $uploads_dir ) !== false) ) {
                $perms = '770';
            }
        }
        return $perms;
    }

    /**
     * Set Permissions For File or Directory
     *
     * @param string $path Absolute path to file or directory
     * @param string $perm Desired permissions value 3 chars
     * @param bool $errors_only When set to true, only errors will be recorded in the Process Log
     * @param bool $sanitize Set to false to skip sanitization (for fix_all)
     */
    private function set_permissions(
        $path,
        $perm,
        $errors_only = false,
        $sanitize = true
    ) {
        if ( $sanitize ) {
            // Get File Path With A Baseline Sanitization
            $path = esc_url( $path );
            // Cleanup Path ( bc WP doesn't have a file path sanitization filter )
            $path = str_replace( [
                ABSPATH,
                'http://',
                'https://',
                '..',
                '"',
                "'",
                ')',
                '('
            ], '', $path );
            // Add ABSPATH
            $path = ABSPATH . $path;
            // Cleanup Path Again..
            $path = str_replace( [
                '/./',
                '////',
                '///',
                '//'
            ], '/', $path );
            // Get Permissions
            $perm = sanitize_text_field( $perm );
        }
        // Relative Path (clean)
        $rel_path = str_replace( ABSPATH, '/', $path );
        $result = false;
        if ( file_exists( $path ) ) {
            // Permissions Be 3 Chars In Length
            if ( strlen( $perm ) == 3 ) {
                // Perm Value Must Be Octal; Not A String
                if ( $perm == '775' ) {
                    $result = chmod( $path, 0775 );
                } elseif ( $perm == '755' ) {
                    $result = chmod( $path, 0755 );
                } elseif ( $perm == '711' ) {
                    $result = chmod( $path, 0711 );
                } elseif ( $perm == '770' ) {
                    $result = chmod( $path, 0770 );
                } elseif ( $perm == '644' ) {
                    $result = chmod( $path, 0644 );
                } elseif ( $perm == '640' ) {
                    $result = chmod( $path, 0640 );
                } elseif ( $perm == '604' ) {
                    $result = chmod( $path, 0604 );
                } elseif ( $perm == '600' ) {
                    $result = chmod( $path, 0600 );
                }
                // @todo need to add a configential check to see if the chmod worked and display an error if it did not
                $result = true;
            }
            /* @todo need to add else just in case so we can be sure that something is going to happen
             * and it doesn't fail silently. Need to return error if it doesn't work.
             */
        } else {
            $this->messages[] = [sprintf( __( 'Error: File does not exist - %s', SECSAFE_TRANSLATE ), $path ), 3, 0];
        }
        if ( $result ) {
            if ( !$errors_only ) {
                $this->messages[] = [sprintf( __( 'File permissions were successfully updated to %1$s for file: %2$s', SECSAFE_TRANSLATE ), $perm, $rel_path ), 0, 0];
            }
        } else {
            $this->messages[] = [sprintf( __( 'Error: File permissions could not be updated to %1$s for file: %2$s. Please contact your hosting provider or a developer for assistance.', SECSAFE_TRANSLATE ), $perm, $rel_path ), 3, 0];
        }
    }

}
