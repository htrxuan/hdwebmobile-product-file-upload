<?php

namespace htrxuan\hdpfu;

if (!defined('ABSPATH')) {
    exit;
}

final class HDPFU_Frontend
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
        add_action('woocommerce_before_add_to_cart_button', array($this, 'render_field'));
        add_action('wp_enqueue_scripts', array($this, 'maybe_enqueue_assets'));
    }

    public function maybe_enqueue_assets()
    {
        if (is_product()) {
            wp_enqueue_style('hdpfu-frontend', HDPFU_PLUGIN_URL . 'assets/css/hdpfu-frontend.css', array(), HDPFU_VERSION);
        }
    }

    public function render_field()
    {
        global $product;
        if (!$product || !HDPFU_Product::is_enabled($product->get_id())) {
            return;
        }

        $product_id    = $product->get_id();
        $label         = HDPFU_Product::get_label($product_id);
        $required      = HDPFU_Product::is_required($product_id);
        $allowed_types = HDPFU_Product::get_allowed_types($product_id);
        $max_size_mb   = HDPFU_Product::get_max_size_mb($product_id);

        $extensions = array();
        $labels     = array();
        foreach ($allowed_types as $key) {
            if (isset(HDPFU_Upload_Handler::ALLOWED_TYPES[$key])) {
                $extensions = array_merge($extensions, HDPFU_Upload_Handler::ALLOWED_TYPES[$key]['extensions']);
                $labels[]   = HDPFU_Upload_Handler::ALLOWED_TYPES[$key]['label'];
            }
        }
        $accept_attr = implode(',', array_map(fn($ext) => ".{$ext}", $extensions));

        echo '<div class="hdpfu-upload-field">';
        printf(
            '<label for="hdpfu_file">%s%s</label>',
            esc_html($label),
            $required ? ' <span class="hdpfu-required">*</span>' : ''
        );
        printf(
            '<input type="file" id="hdpfu_file" name="hdpfu_file" accept="%s"%s />',
            esc_attr($accept_attr),
            $required ? ' required' : ''
        );
        printf(
            '<p class="hdpfu-hint">%s</p>',
            esc_html(sprintf(
                /* translators: 1: comma-separated list of accepted file type labels, 2: max size in MB */
                __('Accepted: %1$s. Max size: %2$dMB.', 'hdwebmobile-product-file-upload'),
                implode(', ', $labels),
                $max_size_mb
            ))
        );
        echo '</div>';
    }
}
