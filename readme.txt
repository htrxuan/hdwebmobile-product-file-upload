=== HDWebmobile Product File Upload ===
Contributors: htrxuan
Donate link: https://paypal.me/htrxuan/20
Tags: woocommerce, file upload, personalization, custom product, print on demand
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Let customers upload a file with a personalized product. Accepted file types are always a hardcoded safe list -- never "allow all".

== Description ==

HDWebmobile Product File Upload adds a file-upload field to any product -- perfect for personalized items like custom mugs, printed t-shirts, or engraved gifts where the customer supplies their own photo, artwork, or PDF. The file is attached to the cart item and order, and shows up as a downloadable link on the order confirmation, order emails, and the backend order screen.

= Why this plugin exists =
A competing "Product Input Fields for WooCommerce" plugin had a critical unauthenticated arbitrary file upload vulnerability (CVE-2026-19089, CVSS 9.8): when its "accepted file types" setting was left blank, the plugin treated that as permission to accept *any* file type, including PHP scripts -- letting an unauthenticated attacker upload and potentially execute a script on the server. This plugin closes that exact failure mode by construction:

* There is no "accept all file types" setting anywhere in this plugin, at any level -- the full universe of acceptable types (JPG, PNG, GIF, WEBP, PDF) is a fixed list in the plugin's own code, not a database value. A product's configuration can only narrow this list, never widen it, and a misconfigured product with no valid type selected rejects every upload rather than accepting everything.
* Every upload is independently verified against its *actual file content* using WordPress core's own `wp_check_filetype_and_ext()`, not just the filename extension the browser reports -- a script renamed to `photo.jpg` is rejected because its real content doesn't match an image.
* A hardcoded denylist of dangerous extensions (`.php` and its variants, `.exe`, `.sh`, `.svg`, and others) is checked before anything else, as defense in depth on top of the allow-list.
* Every file is processed through WordPress core's own `wp_handle_upload()`, never a hand-written upload routine.

= Key Features =
* Add a file-upload field to any product, optional or required
* Custom field label per product (e.g. "Upload your photo")
* Choose which of the safe file types (JPG, PNG, GIF, WEBP, PDF) this product accepts
* Configurable max file size per product, capped at a hard 20MB ceiling
* Uploaded file appears as a link on the cart, checkout, order confirmation, order emails, and the backend order screen

= Limitations (please read before installing) =
* Only JPG, PNG, GIF, WEBP, and PDF are ever accepted -- there is no way to add other file types, by design
* Simple products only in this version

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/hdwebmobile-product-file-upload` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress. WooCommerce must already be installed and active.
3. Edit any product, and in the General tab check "File Upload", set a label, choose accepted types, and set a max size.

== How to Use ==

= 1. Enable uploads on a product =
Edit the product, go to the General tab, check "File Upload", optionally check "Required", and choose which file types to accept.

= 2. Customers upload their file =
On the product page, the customer sees the upload field above Add to Cart, with the accepted types and size limit shown.

= 3. You see the file on the order =
The uploaded file's link appears on the cart, checkout review, order confirmation, order emails, and the backend WooCommerce order screen.

== Screenshots ==

1. The upload configuration on the product edit screen.
2. The file-upload field on the product page.
3. The uploaded file link on the backend order screen.

== Changelog ==

= 1.0.0 =
* Initial release: per-product file upload field with a hardcoded safe type allow-list, content-based type verification, hard file-size ceiling, and the uploaded file surfaced on cart, checkout, order confirmation, order emails, and the backend order screen.
