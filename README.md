# WP Gone Control: development environment

A WordPress plugin that ensures proper HTTP 410 (Gone) status codes are returned for deleted posts, pages, and custom post types, improving SEO and user experience.

## Plugin description

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

## Local dev installation

```bash
bash ./dev/init.sh && make docker.up
make connect.php 
composer install
```

Don't forget update your hosts file
`127.0.0.1 wp410.local`.

## Development
WP plugin directory `plugin-dir`.


## Usage



## Support

- **Issues**: [GitHub Issues](https://github.com/hokoo/wp-410/issues)
- **Documentation**: [GitHub Wiki](https://github.com/hokoo/wp-410/wiki)
- **WordPress Support**: [WordPress.org Plugin Page](https://wordpress.org/plugins/wp-410/)

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- **Author**: [Igor Tron](https://github.com/hokoo)
- **Contributors**: [See all contributors](https://github.com/hokoo/wp-410/graphs/contributors)

---

Made with ❤️ for the WordPress community
