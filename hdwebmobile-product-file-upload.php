<?php

/**
 * Plugin Name: HDWebmobile Product File Upload
 * Plugin URI: https://hdwebmobile.com/plugins/hdwebmobile-product-file-upload/
 * Description: Let customers upload a file (photo, artwork, PDF) with a personalized product. There is no "accept all file types" setting -- the allowed types are always a hardcoded safe list, closing the unauthenticated-arbitrary-file-upload class found in a competing plugin.
 * Version: 1.0.0
 * Author: htrxuan - Han Tran
 * Author URI: https://hdwebmobile.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hdwebmobile-product-file-upload
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * Requires PHP: 7.4
 * Requires at least: 6.9
 */

namespace htrxuan\hdpfu;

if (!defined('ABSPATH')) {
    exit;
}

define('HDPFU_VERSION', '1.0.0');
define('HDPFU_PLUGIN_FILE', __FILE__);
define('HDPFU_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HDPFU_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once HDPFU_PLUGIN_DIR . 'includes/class-hdpfu-activator.php';

register_activation_hook(__FILE__, array(HDPFU_Activator::class, 'activate'));

add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', HDPFU_PLUGIN_FILE, true);
    }
});

add_action('plugins_loaded', function () {
    require_once HDPFU_PLUGIN_DIR . 'includes/class-hdpfu-core.php';
    HDPFU_Core::get_instance();
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $donate_link = '<a href="https://paypal.me/htrxuan/20" target="_blank" rel="noopener noreferrer">' . esc_html__('Donate', 'hdwebmobile-product-file-upload') . '</a>';
    array_unshift($links, $donate_link);
    return $links;
});
