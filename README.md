# WP-410: Gone Status for Deleted Content

A WordPress plugin that ensures proper HTTP 410 (Gone) status codes are returned for deleted posts, pages, and custom post types, improving SEO and user experience.

## Description

When content is deleted in WordPress, the default behavior is to return a 404 (Not Found) status code. However, according to HTTP specifications, a **410 (Gone)** status code is more appropriate for resources that have been permanently deleted and will not return.

This plugin automatically returns a 410 status code for:
- Deleted posts
- Deleted pages
- Deleted custom post types
- Any content that was once published but has been removed

### Why 410 Instead of 404?

- **SEO Benefits**: Search engines understand that 410 means the content is permanently gone and should be removed from their index faster than a 404
- **Better Communication**: It clearly indicates to clients and crawlers that the resource existed but has been intentionally removed
- **HTTP Compliance**: Follows RFC 7231 specifications for proper status code usage
- **Reduced Crawl Waste**: Search engines won't keep trying to re-crawl 410 pages

## Features

- ✅ Automatically detects deleted/trashed content
- ✅ Returns proper 410 HTTP status code
- ✅ Works with all post types (posts, pages, custom post types)
- ✅ Lightweight and performant
- ✅ No configuration needed - works out of the box
- ✅ Compatible with caching plugins
- ✅ Developer-friendly with hooks and filters

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
2. Upload the `wp-410` folder to `/wp-content/plugins/`
3. Activate the plugin through the **Plugins** menu in WordPress

### Via Composer

```bash
composer require hokoo/wp-410
```

### Via WP-CLI

```bash
wp plugin install wp-410 --activate
```

## Usage

Once activated, the plugin works automatically. When a visitor tries to access a URL that previously contained content but has been deleted or moved to trash, they will receive:

- A 410 HTTP status code in the response headers
- The standard WordPress 404 page template (customizable via your theme)

### For Developers

#### Checking if 410 is Active

```php
if ( function_exists( 'is_410' ) && is_410() ) {
    // Custom logic for 410 pages
}
```

#### Customizing the Template

Create a `410.php` template file in your theme:

```php
<?php
// 410.php - Custom template for gone content
get_header();
?>

<div class="error-410">
    <h1><?php _e( 'Content No Longer Available', 'your-theme' ); ?></h1>
    <p><?php _e( 'The content you are looking for has been permanently removed.', 'your-theme' ); ?></p>
</div>

<?php
get_footer();
```

#### Filters

**Modify which post types trigger 410:**

```php
add_filter( 'wp410_post_types', function( $post_types ) {
    // Add custom post type
    $post_types[] = 'my_custom_type';
    return $post_types;
} );
```

**Customize 410 detection logic:**

```php
add_filter( 'wp410_should_return_410', function( $should_410, $post_id ) {
    // Custom logic to determine if 410 should be returned
    return $should_410;
}, 10, 2 );
```

#### Actions

**Execute code when 410 is triggered:**

```php
add_action( 'wp410_before_template', function( $post_id ) {
    // Log 410 responses
    error_log( "410 returned for post ID: {$post_id}" );
} );
```

## Configuration

The plugin works out of the box with no configuration required. However, you can customize its behavior using WordPress filters (see Developer section above).

### Advanced Options

For advanced users who want to customize the plugin's behavior, all customization is done through WordPress hooks and filters in your theme's `functions.php` or a custom plugin.

## Technical Details

### How It Works

1. The plugin hooks into WordPress's `template_redirect` action
2. It checks if the current request is for a 404 page
3. It queries the database to see if a post with the requested slug existed but is now in trash
4. If found, it sets the HTTP status to 410 and loads the appropriate template

### Performance

- Minimal database queries (only on 404 pages)
- Efficient caching integration
- No impact on regular page loads
- Lightweight codebase

### Compatibility

- ✅ WordPress Multisite
- ✅ WooCommerce
- ✅ Popular caching plugins (WP Super Cache, W3 Total Cache, etc.)
- ✅ SEO plugins (Yoast, Rank Math, etc.)
- ✅ Custom post types and taxonomies

## Frequently Asked Questions

### Will this affect my SEO?

Yes, in a positive way! Search engines prefer 410 status codes for deleted content as it helps them understand the content is permanently gone and should be removed from their index.

### Does it work with custom post types?

Yes, the plugin works with all post types by default, including custom post types.

### Can I customize the 410 page design?

Yes, create a `410.php` template file in your theme to customize the appearance.

### What happens to old links?

Old links to deleted content will return a 410 status code, informing visitors and search engines that the content is permanently gone.

### Does it track which pages return 410?

The plugin focuses on returning the proper status code. For tracking, use analytics tools or add custom logging via the provided hooks.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

### Development Setup

```bash
git clone https://github.com/hokoo/wp-410.git
cd wp-410
composer install
npm install
```

### Running Tests

```bash
composer test
```

### Code Standards

This plugin follows [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/).

```bash
composer phpcs
```

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
