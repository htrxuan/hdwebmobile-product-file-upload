<?php

namespace htrxuan\hdpfu;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The HTML `accept` attribute on the frontend's file input is a browser
 * convenience only -- it is not a security control and is trivially bypassed by
 * choosing "all files" in the OS file picker or by posting a raw multipart
 * request directly. Every check that actually matters happens here, server-side,
 * via HDPFU_Upload_Handler, before a file is ever accepted into the cart.
 */
final class HDPFU_Cart
{

    private static $instance = null;

    private $pending_upload = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_add_to_cart'), 10, 3);
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 2);
        add_filter('woocommerce_get_item_data', array($this, 'get_item_data'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_order_line_item_meta'), 10, 4);
    }

    public function validate_add_to_cart($passed, $product_id, $quantity)
    {
        $this->pending_upload = null;

        if (!HDPFU_Product::is_enabled($product_id)) {
            return $passed;
        }

        $file_present = isset($_FILES['hdpfu_file']) && UPLOAD_ERR_NO_FILE !== $_FILES['hdpfu_file']['error']; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- only checking presence/error code here; the file itself is fully validated (type, content, size) in HDPFU_Upload_Handler before ever being trusted.

        if (!$file_present) {
            if (HDPFU_Product::is_required($product_id)) {
                wc_add_notice(__('Please upload a file for this product before adding it to your cart.', 'hdwebmobile-product-file-upload'), 'error');
                return false;
            }
            return $passed;
        }

        $result = HDPFU_Upload_Handler::validate_and_store(
            $_FILES['hdpfu_file'], // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- the raw $_FILES entry is passed deliberately; HDPFU_Upload_Handler::validate_and_store() is the single place that sanitizes the filename and verifies the file's actual content, so sanitizing here first would just duplicate (and risk diverging from) that logic.
            HDPFU_Product::get_allowed_types($product_id),
            HDPFU_Product::get_max_size_mb($product_id)
        );

        if (is_wp_error($result)) {
            wc_add_notice($result->get_error_message(), 'error');
            return false;
        }

        $this->pending_upload = $result;
        return $passed;
    }

    public function add_cart_item_data($cart_item_data, $product_id)
    {
        if (null !== $this->pending_upload) {
            $cart_item_data['hdpfu_upload'] = $this->pending_upload;
            $this->pending_upload = null;
        }
        return $cart_item_data;
    }

    public function get_item_data($item_data, $cart_item)
    {
        if (!empty($cart_item['hdpfu_upload']['url'])) {
            $item_data[] = array(
                'key'   => __('Uploaded File', 'hdwebmobile-product-file-upload'),
                'value' => esc_html(basename($cart_item['hdpfu_upload']['file'])),
            );
        }
        return $item_data;
    }

    public function add_order_line_item_meta($item, $cart_item_key, $values, $order)
    {
        if (empty($values['hdpfu_upload']['url'])) {
            return;
        }
        $item->add_meta_data(__('Uploaded File', 'hdwebmobile-product-file-upload'), esc_url_raw($values['hdpfu_upload']['url']), true);
    }
}
