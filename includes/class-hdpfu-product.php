<?php

namespace htrxuan\hdpfu;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Reads/writes a product's upload configuration. save() always intersects the
 * submitted type checkboxes against HDPFU_Upload_Handler::ALLOWED_TYPES -- a
 * merchant can only ever narrow the accepted types, never widen them beyond that
 * hardcoded list, and there is deliberately no "allow all types" checkbox at all.
 */
class HDPFU_Product
{

    const META_ENABLED       = '_hdpfu_enabled';
    const META_REQUIRED      = '_hdpfu_required';
    const META_LABEL         = '_hdpfu_label';
    const META_ALLOWED_TYPES = '_hdpfu_allowed_types';
    const META_MAX_SIZE_MB   = '_hdpfu_max_size_mb';

    public static function is_enabled($product_id)
    {
        return 'yes' === get_post_meta($product_id, self::META_ENABLED, true);
    }

    public static function is_required($product_id)
    {
        return 'yes' === get_post_meta($product_id, self::META_REQUIRED, true);
    }

    public static function get_label($product_id)
    {
        $label = get_post_meta($product_id, self::META_LABEL, true);
        return '' !== $label ? $label : __('Upload a file', 'hdwebmobile-product-file-upload');
    }

    public static function get_allowed_types($product_id)
    {
        $types = get_post_meta($product_id, self::META_ALLOWED_TYPES, true);
        $types = is_array($types) ? $types : array();
        $safe  = array_values(array_intersect($types, array_keys(HDPFU_Upload_Handler::ALLOWED_TYPES)));
        return empty($safe) ? array('jpg', 'png', 'pdf') : $safe; // sensible default if never configured.
    }

    public static function get_max_size_mb($product_id)
    {
        $value = (int) get_post_meta($product_id, self::META_MAX_SIZE_MB, true);
        if ($value <= 0) {
            return HDPFU_Upload_Handler::DEFAULT_MAX_SIZE_MB;
        }
        return min($value, HDPFU_Upload_Handler::MAX_ALLOWED_SIZE_MB);
    }

    public static function save($product_id, $enabled, $required, $label, array $raw_types, $raw_max_size_mb)
    {
        update_post_meta($product_id, self::META_ENABLED, $enabled ? 'yes' : 'no');
        update_post_meta($product_id, self::META_REQUIRED, $required ? 'yes' : 'no');
        update_post_meta($product_id, self::META_LABEL, sanitize_text_field($label));

        $safe_types = array_values(array_intersect($raw_types, array_keys(HDPFU_Upload_Handler::ALLOWED_TYPES)));
        update_post_meta($product_id, self::META_ALLOWED_TYPES, $safe_types);

        $max_size = min(max(1, absint($raw_max_size_mb)), HDPFU_Upload_Handler::MAX_ALLOWED_SIZE_MB);
        update_post_meta($product_id, self::META_MAX_SIZE_MB, $max_size);
    }
}
