<?php

namespace htrxuan\hdpfu;

if (!defined('ABSPATH')) {
    exit;
}

final class HDPFU_Core
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->includes();
        $this->init_hooks();
    }

    private function __clone()
    {
    }

    private function includes()
    {
        require_once HDPFU_PLUGIN_DIR . 'includes/class-hdpfu-upload-handler.php';
        require_once HDPFU_PLUGIN_DIR . 'includes/class-hdpfu-product.php';
        require_once HDPFU_PLUGIN_DIR . 'includes/class-hdpfu-admin-fields.php';
        require_once HDPFU_PLUGIN_DIR . 'includes/class-hdpfu-frontend.php';
        require_once HDPFU_PLUGIN_DIR . 'includes/class-hdpfu-cart.php';
        require_once HDPFU_PLUGIN_DIR . 'includes/class-hdpfu-admin.php';
    }

    private function init_hooks()
    {
        add_action('admin_notices', array($this, 'render_missing_woocommerce_notice'));

        if (!class_exists('WooCommerce')) {
            return;
        }

        HDPFU_Frontend::get_instance();
        HDPFU_Cart::get_instance();

        if (is_admin()) {
            HDPFU_Admin_Fields::get_instance();
            HDPFU_Admin::get_instance();
        }
    }

    public function render_missing_woocommerce_notice()
    {
        $screen = get_current_screen();
        if (!$screen || 'plugins' !== $screen->id) {
            return;
        }

        if (!get_transient('hdpfu_wc_missing_notice')) {
            return;
        }
        delete_transient('hdpfu_wc_missing_notice');
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php esc_html_e('HDWebmobile Product File Upload requires WooCommerce to be installed and active. The plugin has been deactivated.', 'hdwebmobile-product-file-upload'); ?>
            </p>
        </div>
        <?php
    }
}
