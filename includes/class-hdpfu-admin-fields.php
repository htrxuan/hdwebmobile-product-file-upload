<?php

namespace htrxuan\hdpfu;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the upload configuration on the product edit screen. The type
 * checkboxes are generated directly from HDPFU_Upload_Handler::ALLOWED_TYPES --
 * there is no "select all" option and no way to type in a custom extension, so
 * the admin UI itself cannot express a request for an unsafe type.
 */
final class HDPFU_Admin_Fields
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
        add_action('woocommerce_product_options_general_product_data', array($this, 'render_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_fields'));
    }

    public function render_fields()
    {
        global $post;
        $product_id = $post->ID;

        echo '<div class="options_group hdpfu-fields">';

        woocommerce_wp_checkbox(array(
            'id'          => HDPFU_Product::META_ENABLED,
            'label'       => __('File Upload', 'hdwebmobile-product-file-upload'),
            'description' => __('Let customers upload a file with this product.', 'hdwebmobile-product-file-upload'),
            'value'       => get_post_meta($product_id, HDPFU_Product::META_ENABLED, true) ?: 'no',
        ));

        woocommerce_wp_checkbox(array(
            'id'          => HDPFU_Product::META_REQUIRED,
            'label'       => __('Required', 'hdwebmobile-product-file-upload'),
            'description' => __('Customer cannot add to cart without uploading a file.', 'hdwebmobile-product-file-upload'),
            'value'       => get_post_meta($product_id, HDPFU_Product::META_REQUIRED, true) ?: 'no',
        ));

        woocommerce_wp_text_input(array(
            'id'    => HDPFU_Product::META_LABEL,
            'label' => __('Field label', 'hdwebmobile-product-file-upload'),
            'value' => get_post_meta($product_id, HDPFU_Product::META_LABEL, true) ?: __('Upload a file', 'hdwebmobile-product-file-upload'),
        ));

        $selected_types = HDPFU_Product::get_allowed_types($product_id);
        echo '<p class="form-field hdpfu_allowed_types_field"><label>' . esc_html__('Accepted file types', 'hdwebmobile-product-file-upload') . '</label>';
        foreach (HDPFU_Upload_Handler::ALLOWED_TYPES as $key => $type) {
            printf(
                '<label style="display:inline-block;margin-right:1em;font-weight:normal;"><input type="checkbox" name="%s[]" value="%s"%s /> %s</label>',
                esc_attr(HDPFU_Product::META_ALLOWED_TYPES),
                esc_attr($key),
                in_array($key, $selected_types, true) ? ' checked' : '',
                esc_html($type['label'])
            );
        }
        echo '</p>';

        woocommerce_wp_text_input(array(
            'id'                => HDPFU_Product::META_MAX_SIZE_MB,
            'label'             => __('Max file size (MB)', 'hdwebmobile-product-file-upload'),
            'type'              => 'number',
            'custom_attributes' => array('step' => '1', 'min' => '1', 'max' => (string) HDPFU_Upload_Handler::MAX_ALLOWED_SIZE_MB),
            'value'             => HDPFU_Product::get_max_size_mb($product_id),
            /* translators: %d: the hard ceiling on file size, in megabytes */
            'description'       => sprintf(__('Maximum %d MB, regardless of what is entered here.', 'hdwebmobile-product-file-upload'), HDPFU_Upload_Handler::MAX_ALLOWED_SIZE_MB),
        ));

        echo '</div>';
    }

    public function save_fields($post_id)
    {
        if (!isset($_POST['woocommerce_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['woocommerce_meta_nonce'])), 'woocommerce_save_data')) {
            return;
        }

        $enabled  = isset($_POST[HDPFU_Product::META_ENABLED]);
        $required = isset($_POST[HDPFU_Product::META_REQUIRED]);
        $label    = isset($_POST[HDPFU_Product::META_LABEL]) ? sanitize_text_field(wp_unslash($_POST[HDPFU_Product::META_LABEL])) : '';
        $raw_types = isset($_POST[HDPFU_Product::META_ALLOWED_TYPES]) && is_array($_POST[HDPFU_Product::META_ALLOWED_TYPES])
            ? array_map('sanitize_text_field', wp_unslash($_POST[HDPFU_Product::META_ALLOWED_TYPES]))
            : array();
        $max_size = isset($_POST[HDPFU_Product::META_MAX_SIZE_MB]) ? absint($_POST[HDPFU_Product::META_MAX_SIZE_MB]) : HDPFU_Upload_Handler::DEFAULT_MAX_SIZE_MB;

        HDPFU_Product::save($post_id, $enabled, $required, $label, $raw_types, $max_size);
    }
}
