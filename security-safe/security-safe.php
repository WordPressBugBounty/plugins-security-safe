<?php

namespace SovereignStack\SecuritySafe;

define( 'SECSAFE_NAME', 'WP Security Safe' );
define( 'SECSAFE_NAME_PRO', 'WP Security Safe Pro' );
define( 'SECSAFE_SLUG', basename( plugin_dir_url( __FILE__ ) ) );
define( 'SECSAFE_VERSION', '2.6.2' );
define( 'SECSAFE_MIN_PHP', '7.4.0' );
define( 'SECSAFE_MIN_WP', '5.3.0' );
/**
 * WP Security Safe Plugin.
 *
 * @package   SovereignStack\SecuritySafe
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 or higher
 *
 * @wordpress-plugin
 * Plugin Name: WP Security Safe
 * Version: 2.6.2
 * Plugin URI: https://wpsecuritysafe.com
 * Description: Firewall, Security Hardening, Auditing & Privacy
 * Author: Sovereign Stack
 * Author URI: https://sovstack.com
 * Text Domain: security-safe-translate
 * Domain Path: /languages/
 * License: GPL v3
 *
 * This software is provided "as is" and any express or implied warranties, including, but not limited to, the
 * implied warranties of merchantibility and fitness for a particular purpose are disclaimed. In no event shall
 * the copyright owner or contributors be liable for any direct, indirect, incidental, special, exemplary, or
 * consequential damages(including, but not limited to, procurement of substitute goods or services; loss of
 * use, data, or profits; or business interruption) however caused and on any theory of liability, whether in
 * contract, strict liability, or tort(including negligence or otherwise) arising in any way out of the use of
 * this software, even if advised of the possibility of such damage.
 *
 * For full license details see license.txt
 */
// Prevent Direct Access
defined( 'ABSPATH' ) || die;
define( 'SECSAFE_TRANSLATE', 'security-safe-translate' );
if ( check_compatibility() ) {
    // Do not move the following constants to Yoda
    define( 'SECSAFE_TIME_START', microtime( true ) );
    define( 'SECSAFE_DEBUG', false );
    define( 'SECSAFE_FILE', __FILE__ );
    define( 'SECSAFE_DIR', WP_PLUGIN_DIR . '/' . SECSAFE_SLUG );
    define( 'SECSAFE_DIR_CORE', SECSAFE_DIR . '/core' );
    define( 'SECSAFE_DIR_INCLUDES', SECSAFE_DIR_CORE . '/includes' );
    define( 'SECSAFE_DESC', __( 'Firewall, Security Hardening, Auditing & Privacy', SECSAFE_TRANSLATE ) );
    require_once SECSAFE_DIR_INCLUDES . '/REQUEST.php';
    require_once SECSAFE_DIR_INCLUDES . '/Yoda.php';
    Yoda::set_constants();
    // Make freemius compatible in local environment
    if ( !defined( 'WP_FS__DIR' ) ) {
        define( 'WP_FS__DIR', SECSAFE_DIR . '/freemius' );
    }
    if ( !function_exists( 'SovereignStack\\SecuritySafe\\security_safe' ) ) {
        // Create a helper function for easy SDK access.
        function security_safe() {
            global $security_safe;
            if ( !isset( $security_safe ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $security_safe = fs_dynamic_init( [
                    'id'             => '2439',
                    'slug'           => 'security-safe',
                    'type'           => 'plugin',
                    'public_key'     => 'pk_d47b8181312a2a8b3191a732c0996',
                    'is_premium'     => false,
                    'premium_suffix' => '',
                    'has_addons'     => false,
                    'has_paid_plans' => true,
                    'menu'           => [
                        'slug'    => SECSAFE_SLUG,
                        'contact' => false,
                    ],
                    'is_live'        => true,
                ] );
            }
            return $security_safe;
        }

        // Init Freemius.
        security_safe();
        // Signal that SDK was initiated.
        do_action( 'security_safe_loaded' );
    }
    // Load Janitor
    require_once SECSAFE_DIR_INCLUDES . '/Janitor.php';
    $Janitor = new Janitor();
    // Load Plugin Core
    require_once SECSAFE_DIR_CORE . '/Plugin.php';
    // Load Security
    require_once SECSAFE_DIR_INCLUDES . '/Threats.php';
    require_once SECSAFE_DIR_FIREWALL . '/Firewall.php';
    require_once SECSAFE_DIR_SECURITY . '/Security.php';
    // Init Plugin
    add_action( 'plugins_loaded', __NAMESPACE__ . '\\Plugin::init' );
    // Clear PHP Cache on Upgrades
    add_filter(
        'upgrader_pre_install',
        __NAMESPACE__ . '\\Plugin::clear_php_cache',
        10,
        2
    );
} else {
    if ( defined( 'SECSAFE_COMPATIBLE_ERROR' ) ) {
        if ( (is_admin() || is_network_admin()) && !wp_doing_ajax() && !wp_doing_cron() ) {
            // Display plugin compatibility error notice
            add_action( 'admin_notices', __NAMESPACE__ . '\\display_compatibility_notice' );
            add_action( 'network_admin_notices', __NAMESPACE__ . '\\display_compatibility_notice' );
        }
    }
}
/**
 * Check to see if the current WordPress install is compatible with our plugin
 *
 * @package SovereignStack\SecuritySafe
 * @since   2.5.0
 *
 * @global string $wp_version The WordPress version string.
 * @return bool
 *
 * @note    DO NOT ADD TYPE HINTING: The function must be backwards compatible with old versions of PHP < 7.4
 */
function check_compatibility() {
    global $wp_version;
    if ( !defined( 'SECSAFE_COMPATIBLE_ERROR' ) ) {
        if ( version_compare( PHP_VERSION, SECSAFE_MIN_PHP, '<' ) ) {
            // PHP version is less than minimum required
            $message = sprintf(
                __( 'Error: %s - Plugin requires PHP version %s or higher. You are currently running %s.', SECSAFE_TRANSLATE ),
                SECSAFE_NAME,
                SECSAFE_MIN_PHP,
                PHP_VERSION
            );
            define( 'SECSAFE_COMPATIBLE_ERROR', $message );
        } elseif ( version_compare( $wp_version, SECSAFE_MIN_WP, '<' ) ) {
            // WP version is less than minimum required
            $message = sprintf(
                __( 'Error: %s - Plugin requires WordPress version %s or higher. You are currently running version %s.', SECSAFE_TRANSLATE ),
                SECSAFE_NAME,
                SECSAFE_MIN_WP,
                $wp_version
            );
            define( 'SECSAFE_COMPATIBLE_ERROR', $message );
        } else {
            // System is compatible with this plugin up to this point
        }
    }
    return !defined( 'SECSAFE_COMPATIBLE_ERROR' );
}

/**
 * Displays the plugin compatibility error in the admin and network admin areas
 *
 * @package SovereignStack\SecuritySafe
 * @since   2.5.0
 *
 * @return void
 *
 * @note    DO NOT ADD TYPE HINTING: The function must be backwards compatible with old versions of PHP < 7.4
 */
function display_compatibility_notice() {
    echo '<div class="active notice notice-error plugin-' . esc_attr( SECSAFE_SLUG ) . '"><p>' . esc_html( SECSAFE_COMPATIBLE_ERROR ) . '</p></div>';
}
