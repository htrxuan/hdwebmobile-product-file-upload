# HDWebmobile Product File Upload

Let customers upload a file with a personalized product. Accepted file types are always a hardcoded safe list -- never "allow all".

- **WordPress.org:** https://wordpress.org/plugins/hdwebmobile-product-file-upload/
- **Requires:** WordPress 6.9+, WooCommerce, PHP 7.4+
- **License:** GPLv2 or later

## Description

HDWebmobile Product File Upload adds a file-upload field to any product -- perfect for personalized items like custom mugs, printed t-shirts, or engraved gifts where the customer supplies their own photo, artwork, or PDF. The file is attached to the cart item and order, and shows up as a downloadable link on the order confirmation, order emails, and the backend order screen.

## Why this plugin exists

A competing "Product Input Fields for WooCommerce" plugin had a critical unauthenticated arbitrary file upload vulnerability (CVE-2026-19089, CVSS 9.8): when its "accepted file types" setting was left blank, the plugin treated that as permission to accept *any* file type, including PHP scripts -- letting an unauthenticated attacker upload and potentially execute a script on the server. This plugin closes that exact failure mode by construction:

* There is no "accept all file types" setting anywhere in this plugin, at any level -- the full universe of acceptable types (JPG, PNG, GIF, WEBP, PDF) is a fixed list in the plugin's own code, not a database value. A product's configuration can only narrow this list, never widen it, and a misconfigured product with no valid type selected rejects every upload rather than accepting everything.
* Every upload is independently verified against its *actual file content* using WordPress core's own `wp_check_filetype_and_ext()`, not just the filename extension the browser reports -- a script renamed to `photo.jpg` is rejected because its real content doesn't match an image.
* A hardcoded denylist of dangerous extensions (`.php` and its variants, `.exe`, `.sh`, `.svg`, and others) is checked before anything else, as defense in depth on top of the allow-list.
* Every file is processed through WordPress core's own `wp_handle_upload()`, never a hand-written upload routine.

## Features

* Add a file-upload field to any product, optional or required
* Custom field label per product (e.g. "Upload your photo")
* Choose which of the safe file types (JPG, PNG, GIF, WEBP, PDF) this product accepts
* Configurable max file size per product, capped at a hard 20MB ceiling
* Uploaded file appears as a link on the cart, checkout, order confirmation, order emails, and the backend order screen

## Development

Standard WordPress plugin structure:

```
hdwebmobile-product-file-upload.php    Bootstrap
includes/class-hdpfu-activator.php
includes/class-hdpfu-admin-fields.php
includes/class-hdpfu-admin.php
includes/class-hdpfu-cart.php
includes/class-hdpfu-core.php
includes/class-hdpfu-frontend.php
includes/class-hdpfu-hub.php
includes/class-hdpfu-product.php
includes/class-hdpfu-upload-handler.php
```

Part of the [HDWebmobile](https://hdwebmobile.com/plugins/) suite of focused, single-purpose WooCommerce plugins.

## License

GPLv2 or later. See [LICENSE](LICENSE).

