<?php

namespace htrxuan\hdpfu;

if (!defined('ABSPATH')) {
    exit;
}

final class HDPFU_Admin
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
        require_once HDPFU_PLUGIN_DIR . 'includes/class-hdpfu-hub.php';
        add_filter('hdwebmobile_hub_tabs', array($this, 'register_hub_tabs'));
    }

    public function register_hub_tabs($tabs)
    {
        $tabs['product-file-upload'] = array(
            'label'  => __('File Upload', 'hdwebmobile-product-file-upload'),
            'order'  => 47,
            'render' => array($this, 'render_settings_page'),
        );
        return $tabs;
    }

    public function render_settings_page()
    {
        echo '<p>' . esc_html__('There is nothing to configure globally -- enable "File Upload" on any individual product\'s edit screen (in the General tab) to let customers attach a file to it.', 'hdwebmobile-product-file-upload') . '</p>';
        echo '<p>' . esc_html__('Accepted file types are always chosen from a fixed, safe list (JPG, PNG, GIF, WEBP, PDF) -- there is no option to accept every file type, by design.', 'hdwebmobile-product-file-upload') . '</p>';
    }
}
