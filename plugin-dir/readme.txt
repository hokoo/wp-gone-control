=== Gone Control ===
Contributors: hokku
Donate link: https://www.paypal.me/igortron
Tags: contact form telegram,contact form 7,telegram
Requires at least: 5.6
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin that ensures HTTP 410 (Gone) status code are returned for deleted posts, pages, and CPT, improving SEO and user experience.

== Description ==

When content is deleted in WordPress, the default behavior is to return a 404 (Not Found) status code. However, according to HTTP specifications, a 410 (Gone) status code is more appropriate for resources that have been permanently deleted and will not return.

This plugin automatically returns a 410 status code for:
 - Deleted posts
 - Deleted pages
 - Deleted custom post types
 - Deleted users
 - Deleted media attachments

Once activated, the plugin works automatically. When a visitor tries to access a URL that previously contained content but has been deleted or moved to trash, they will receive:

- A 410 HTTP status code in the response headers
- The standard WordPress 404 page template (customizable via your theme)

== Related Resources ==

- [HTTP 410 Gone - MDN Web Docs](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/410)
- [RFC 7231 - HTTP Status Codes](https://tools.ietf.org/html/rfc7231#section-6.5.9)
- [Google's guidelines on 410 vs 404](https://developers.google.com/search/docs/crawling-indexing/http-status-codes)

== Changelog ==

= 0.2.0 =
* Local development setup.