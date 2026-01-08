# WP Gone Control: Gone Status for Deleted Content

A WordPress plugin that ensures proper HTTP 410 (Gone) status codes are returned for deleted posts, pages, and custom post types, improving SEO and user experience.

## Description

When content is deleted in WordPress, the default behavior is to return a 404 (Not Found) status code. However, according to HTTP specifications, a **410 (Gone)** status code is more appropriate for resources that have been permanently deleted and will not return.

This plugin automatically returns a 410 status code for:
- Deleted posts
- Deleted pages
- Deleted custom post types
- Deleted users
- Deleted media attachments

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher

## Installation

### Via WordPress Admin

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Navigate to **Plugins > Add New**
4. Click **Upload Plugin**
5. Choose the ZIP file and click **Install Now**
6. Click **Activate Plugin**

### Via FTP

1. Extract the plugin ZIP file
2. Upload the `wp-gone-control` folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu in WordPress

### Via Composer

```bash
composer require hokoo/wp-410
```

### Via WP-CLI

```bash
wp plugin install wp-gone-control --activate
```

## Usage

Once activated, the plugin works automatically. When a visitor tries to access a URL that previously contained content but has been deleted or moved to trash, they will receive:

- A 410 HTTP status code in the response headers
- The standard WordPress 404 page template (customizable via your theme)

## Changelog

### 1.0.0
- Initial release
- Basic 410 status code functionality for deleted posts
- Support for all post types
- Developer hooks and filters

## Support

- **Issues**: [GitHub Issues](https://github.com/hokoo/wp-410/issues)
- **Documentation**: [GitHub Wiki](https://github.com/hokoo/wp-410/wiki)
- **WordPress Support**: [WordPress.org Plugin Page](https://wordpress.org/plugins/wp-410/)

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- **Author**: [Igor Tron](https://github.com/hokoo)
- **Contributors**: [See all contributors](https://github.com/hokoo/wp-410/graphs/contributors)

## Related Resources

- [HTTP 410 Gone - MDN Web Docs](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/410)
- [RFC 7231 - HTTP Status Codes](https://tools.ietf.org/html/rfc7231#section-6.5.9)
- [Google's guidelines on 410 vs 404](https://developers.google.com/search/docs/crawling-indexing/http-status-codes)

---

Made with ❤️ for the WordPress community
