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

namespace iTRON\WPGoneControl;

use iTRON\WPGoneControl\Controller\Activation;
use iTRON\WPGoneControl\Controller\MainController;
use iTRON\WPGoneControl\Controller\TemplateController;

const PLUGIN_SLUG = 'wp-gone-control';
const VERSION     = '0.2.0';

const PLUGIN_MAIN_FILE_PATH = __FILE__;
define( __NAMESPACE__ . '\PLUGIN_NAME', plugin_basename( __FILE__ ) );
define( __NAMESPACE__ . '\PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( __NAMESPACE__ . '\PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( __NAMESPACE__ . '\OPTIONS_MODE', is_multisite() ? 'network' : 'theme_options' );

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

$db       = new Database();
$template = new TemplateController( $db );
$main     = new MainController( $db, $template );
$activation = new Activation( $db );

register_activation_hook( __FILE__, [ $activation, 'processActivationHook' ] );

$activation::init();
$main->register();

( new Settings() )::init();
