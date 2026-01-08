<?php
/**
 * Plugin Name: Gone Control: 410 status for removed objects
 * Plugin URI: https://github.com/hokoo/wp-410
 * Description: Stores URLs of removed public objects and serves HTTP 410 for them.
 * Version: 0.1.0
 * Author: Igor Tron (itron)
 * Author URI: https://github.com/hokoo
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-gone-control
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

spl_autoload_register(
	function ( $class ) {
		$prefix   = 'WPGoneControl\\';
		$base_dir = __DIR__ . '/src/';

		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative_class = substr( $class, strlen( $prefix ) );
		$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

$gone_control_database = new WPGoneControl\Database();
$gone_control_template = new WPGoneControl\Controller\TemplateController( $gone_control_database );
$gone_control_main     = new WPGoneControl\Controller\MainController( $gone_control_database, $gone_control_template );

register_activation_hook( __FILE__, array( $gone_control_main, 'activate' ) );
$gone_control_main->register();
