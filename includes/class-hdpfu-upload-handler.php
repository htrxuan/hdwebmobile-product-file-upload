<?php

namespace htrxuan\hdpfu;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The one place a file from a customer's browser is ever accepted. This is the
 * exact surface CVE-2026-19089 broke: a competing plugin treated an empty/unset
 * "accepted types" setting as "accept everything," including .php -- letting an
 * unauthenticated visitor upload a script and (on a permissive host) execute it.
 * This class makes that failure mode structurally impossible:
 *
 * - self::ALLOWED_TYPES is the entire universe of file types this plugin will
 *   EVER accept. It is a hardcoded constant, not a setting -- there is no
 *   database value, option, or product-meta field anywhere that can widen it,
 *   and nothing ever iterates "all registered mime types" or similar. A product
 *   can only restrict this list further (see HDPFU_Product::get_allowed_types()),
 *   never expand it, and a product with zero valid types selected is treated as
 *   accepting nothing, never as accepting everything.
 * - Every extension is independently cross-checked against WordPress core's own
 *   wp_check_filetype_and_ext(), which inspects actual file content (not just
 *   the filename/extension the browser sent) -- a PHP script renamed to
 *   photo.jpg is rejected here even though its filename passes the extension
 *   check, because its real content does not match an image signature.
 * - self::HARD_DENYLIST is checked before anything else as pure defense in
 *   depth: even if ALLOWED_TYPES were ever edited by a future contributor to
 *   include something unsafe, these extensions can never be accepted.
 */
class HDPFU_Upload_Handler
{

    const ALLOWED_TYPES = array(
        'jpg'  => array('label' => 'JPG', 'extensions' => array('jpg', 'jpeg'), 'mime' => 'image/jpeg'),
        'png'  => array('label' => 'PNG', 'extensions' => array('png'), 'mime' => 'image/png'),
        'gif'  => array('label' => 'GIF', 'extensions' => array('gif'), 'mime' => 'image/gif'),
        'webp' => array('label' => 'WEBP', 'extensions' => array('webp'), 'mime' => 'image/webp'),
        'pdf'  => array('label' => 'PDF', 'extensions' => array('pdf'), 'mime' => 'application/pdf'),
    );

    const HARD_DENYLIST = array(
        'php', 'php2', 'php3', 'php4', 'php5', 'php7', 'phps', 'phtml', 'pht', 'phar',
        'cgi', 'pl', 'py', 'sh', 'bash', 'exe', 'bat', 'cmd', 'com', 'jar',
        'asp', 'aspx', 'jsp', 'jspx', 'htaccess', 'htpasswd', 'svg', 'swf', 'js', 'html', 'htm',
    );

    const DEFAULT_MAX_SIZE_MB = 5;
    const MAX_ALLOWED_SIZE_MB = 20; // Hard ceiling -- a product can never be configured above this.

    /**
     * @param array $file       A single entry from $_FILES.
     * @param array $type_keys  Keys from self::ALLOWED_TYPES this product accepts (already
     *                          intersected against ALLOWED_TYPES by HDPFU_Product -- this
     *                          method re-intersects anyway, trusting nothing).
     * @param int   $max_size_mb
     * @return array|\WP_Error  array('url' => ..., 'file' => ..., 'type' => ...) or WP_Error.
     */
    public static function validate_and_store(array $file, array $type_keys, $max_size_mb)
    {
        if (!isset($file['error']) || UPLOAD_ERR_OK !== $file['error']) {
            return new \WP_Error('hdpfu_upload_error', __('The file could not be uploaded.', 'hdwebmobile-product-file-upload'));
        }

        $max_size_mb = min((int) $max_size_mb ?: self::DEFAULT_MAX_SIZE_MB, self::MAX_ALLOWED_SIZE_MB);
        if ($file['size'] > $max_size_mb * MB_IN_BYTES) {
            return new \WP_Error('hdpfu_too_large', sprintf(
                /* translators: %d: maximum file size in megabytes */
                __('That file is larger than the %dMB limit.', 'hdwebmobile-product-file-upload'),
                $max_size_mb
            ));
        }

        $safe_keys = array_values(array_intersect($type_keys, array_keys(self::ALLOWED_TYPES)));
        if (empty($safe_keys)) {
            // No valid type configured for this product -- refuse the upload entirely
            // rather than ever falling back to "accept anything" behavior.
            return new \WP_Error('hdpfu_no_types_configured', __('File uploads are not configured correctly for this product.', 'hdwebmobile-product-file-upload'));
        }

        $filename = sanitize_file_name($file['name']);
        $ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, self::HARD_DENYLIST, true)) {
            return new \WP_Error('hdpfu_type_denied', __('That file type is not allowed.', 'hdwebmobile-product-file-upload'));
        }

        $allowed_extensions = array();
        $mime_map           = array();
        foreach ($safe_keys as $key) {
            $allowed_extensions = array_merge($allowed_extensions, self::ALLOWED_TYPES[$key]['extensions']);
            $mime_map[implode('|', self::ALLOWED_TYPES[$key]['extensions'])] = self::ALLOWED_TYPES[$key]['mime'];
        }

        if (!in_array($ext, $allowed_extensions, true)) {
            return new \WP_Error('hdpfu_type_not_accepted', __('That file type is not accepted for this product.', 'hdwebmobile-product-file-upload'));
        }

        // The decisive check: does the file's actual content match a safe type,
        // regardless of what extension or Content-Type the browser claimed?
        $filetype = wp_check_filetype_and_ext($file['tmp_name'], $filename, $mime_map);
        if (empty($filetype['ext']) || empty($filetype['type']) || !in_array($filetype['ext'], $allowed_extensions, true)) {
            return new \WP_Error('hdpfu_content_mismatch', __('That file does not appear to be a valid file of an accepted type.', 'hdwebmobile-product-file-upload'));
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';

        $overrides = array(
            'test_form' => false,
            'mimes'     => $mime_map,
        );

        $uploaded = wp_handle_upload($file, $overrides);
        if (isset($uploaded['error'])) {
            return new \WP_Error('hdpfu_handle_upload_failed', $uploaded['error']);
        }

        return array(
            'url'  => $uploaded['url'],
            'file' => $uploaded['file'],
            'type' => $uploaded['type'],
        );
    }
}
