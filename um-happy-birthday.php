<?php
/**
 * Plugin Name:         Ultimate Member - Happy Birthday
 * Description:         Extension to Ultimate Member for Birthday greeting emails and optional mobile SMS texts.
 * Version:             2.5.0
 * Requires PHP:        7.4
 * Author:              Miss Veronica
 * License:             GPL v3 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:          https://github.com/MissVeronica
 * Plugin URI:          https://github.com/MissVeronica/um-happy-birthday
 * Update URI:          https://github.com/MissVeronica/um-happy-birthday
 * Text Domain:         happy-birthday
 * Domain Path:         /languages
 * UM version:          2.8.6
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

define( 'Plugin_File', __FILE__ );
define( 'Plugin_Path', plugin_dir_path( __FILE__ ) );
define( 'Plugin_Textdomain', 'happy-birthday' );
define( 'Plugin_Basename', plugin_basename(__FILE__));

add_action( 'plugins_loaded', 'um_happy_birthday_plugin_loaded', 0 );
add_filter( 'plugin_action_links_' . Plugin_Basename, 'happy_birthday_settings_link' );

function happy_birthday_settings_link( $links ) {

    $url = get_admin_url() . 'admin.php?page=um_options&tab=extensions&section=happy-birthday';
    $links[] = '<a href="' . esc_url( $url ) . '">' . __( 'Settings' ) . '</a>';

    return $links;
}

function um_happy_birthday_plugin_loaded() {

    $locale = ( get_locale() != '' ) ? get_locale() : 'en_US';

    load_textdomain( Plugin_Textdomain, WP_LANG_DIR . '/plugins/' . Plugin_Textdomain . '-' . $locale . '.mo' );
    load_plugin_textdomain( Plugin_Textdomain, false, dirname( Plugin_Basename ) . '/languages/' );

    require_once( Plugin_Path . 'includes/admin/happy-birthday-admin.php' );
    UM()->classes['um_happy_birthday'] = new UM_Happy_Birthday();

    require_once( Plugin_Path . 'includes/admin/happy-birthday-transients.php' );
    UM()->classes['um_happy_birthday_transients'] = new UM_Happy_Birthday_Transients();

    require_once( Plugin_Path . 'includes/admin/happy-birthday-predefined.php' );

    require_once( Plugin_Path . 'includes/core/happy-birthday-shortcode.php' );

    if ( is_admin()) {
        require_once( Plugin_Path . 'includes/admin/happy-birthday-admin-settings.php' );
    }
}
