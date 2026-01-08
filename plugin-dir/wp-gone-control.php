<?php
/**
 * Plugin Name: Gone Control: 410 status for removed objects
 * Plugin URI: https://github.com/hokoo/wp-410
 * Description: Stores URLs of removed public objects and serves HTTP 410 for them.
 * Version: 0.2.0
 * Author: Igor Tron (itron)
 * Author URI: https://github.com/hokoo
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-gone-control
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

$gone_control_database = new iTRON\WPGoneControl\Database();
$gone_control_template = new iTRON\WPGoneControl\Controller\TemplateController( $gone_control_database );
$gone_control_main     = new iTRON\WPGoneControl\Controller\MainController( $gone_control_database, $gone_control_template );

register_activation_hook( __FILE__, array( $gone_control_main, 'activate' ) );
$gone_control_main->register();
