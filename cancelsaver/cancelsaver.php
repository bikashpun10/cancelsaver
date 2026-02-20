<?php
/**
 * Plugin Name:       CancelSaver - Subscription Retention
 * Plugin URI:        https://cancelsaver.com
 * Description:       Intercepts WooCommerce subscription cancellations with smart retention offers. Show pause, skip, or discount offers before a subscriber cancels. Built-in win-back email. Works with WooCommerce Subscriptions, WebToffee, YITH, and SUMO.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            CancelSaver
 * Author URI:        https://cancelsaver.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cancelsaver
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CANCELSAVER_VERSION', '1.0.0' );
define( 'CANCELSAVER_PATH', plugin_dir_path( __FILE__ ) );
define( 'CANCELSAVER_URL', plugin_dir_url( __FILE__ ) );

require_once CANCELSAVER_PATH . 'includes/class-cancelsaver-compat.php';
require_once CANCELSAVER_PATH . 'includes/class-cancelsaver-settings.php';
require_once CANCELSAVER_PATH . 'includes/class-cancelsaver-interceptor.php';
require_once CANCELSAVER_PATH . 'includes/class-cancelsaver-offers.php';
require_once CANCELSAVER_PATH . 'includes/class-cancelsaver-tracker.php';
require_once CANCELSAVER_PATH . 'includes/class-cancelsaver-winback.php';
require_once CANCELSAVER_PATH . 'admin/class-cancelsaver-admin.php';

function cancelsaver_init() {
    CancelSaver_Compat::detect();
    if ( ! CancelSaver_Compat::is_supported() ) {
        add_action( 'admin_notices', 'cancelsaver_missing_plugin_notice' );
        return;
    }
    CancelSaver_Settings::init();
    CancelSaver_Interceptor::init();
    CancelSaver_Tracker::init();
    CancelSaver_WinBack::init();
    CancelSaver_Admin::init();
}
add_action( 'plugins_loaded', 'cancelsaver_init', 20 );

function cancelsaver_missing_plugin_notice() {
    echo '<div class="notice notice-warning is-dismissible"><p>';
    echo '<strong>CancelSaver</strong> requires a supported subscription plugin: ';
    echo 'WooCommerce Subscriptions, WebToffee Subscriptions, YITH WooCommerce Subscriptions, or SUMO Subscriptions.';
    echo '</p></div>';
}

function cancelsaver_plugin_action_links( $links ) {
    $settings = '<a href="' . admin_url( 'admin.php?page=cancelsaver-settings' ) . '">Settings</a>';
    array_unshift( $links, $settings );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'cancelsaver_plugin_action_links' );

register_activation_hook( __FILE__, 'cancelsaver_activate' );
function cancelsaver_activate() {
    global $wpdb;
    $table   = $wpdb->prefix . 'cancelsaver_events';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        sub_id      VARCHAR(100)    NOT NULL,
        customer_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
        event       VARCHAR(50)     NOT NULL,
        offer       VARCHAR(50)     DEFAULT NULL,
        sub_value   DECIMAL(10,2)   DEFAULT 0,
        plugin      VARCHAR(50)     DEFAULT NULL,
        created_at  DATETIME        DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY sub_id (sub_id),
        KEY event (event),
        KEY created_at (created_at)
    ) {$charset};";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
    $defaults = array(
        'cancelsaver_enabled'         => '1',
        'cancelsaver_offer_pause'     => '1',
        'cancelsaver_offer_skip'      => '1',
        'cancelsaver_offer_discount'  => '1',
        'cancelsaver_discount_amount' => '20',
        'cancelsaver_discount_type'   => 'percent',
        'cancelsaver_headline'        => 'Wait - before you go!',
        'cancelsaver_subheadline'     => "We'd hate to lose you. Pick an option and we'll make it work.",
        'cancelsaver_winback_enabled' => '1',
        'cancelsaver_winback_subject' => "We miss you - here's something special",
        'cancelsaver_winback_delay'   => '1',
    );
    foreach ( $defaults as $key => $val ) {
        if ( get_option( $key ) === false ) update_option( $key, $val );
    }
}

register_deactivation_hook( __FILE__, 'cancelsaver_deactivate' );
function cancelsaver_deactivate() {
    wp_clear_scheduled_hook( 'cancelsaver_resume_sub' );
    wp_clear_scheduled_hook( 'cancelsaver_send_winback' );
}

register_uninstall_hook( __FILE__, 'cancelsaver_uninstall' );
function cancelsaver_uninstall() {
    global $wpdb;
    $opts = array(
        'cancelsaver_enabled','cancelsaver_offer_pause','cancelsaver_offer_skip',
        'cancelsaver_offer_discount','cancelsaver_discount_amount','cancelsaver_discount_type',
        'cancelsaver_headline','cancelsaver_subheadline','cancelsaver_winback_enabled',
        'cancelsaver_winback_subject','cancelsaver_winback_delay',
    );
    foreach ( $opts as $o ) delete_option( $o );
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cancelsaver_events" ); // phpcs:ignore
}
