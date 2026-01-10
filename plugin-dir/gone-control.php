<?php
/**
 * Plugin Name: Gone Control
 * Plugin URI: https://github.com/hokoo/wp-410
 * Description: Stores URLs of removed public objects and serves HTTP 410 for them.
 * Version: 0.3
 * Author: Igor Tron (itron)
 * Author URI: https://github.com/hokoo
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gone-control
 */

namespace iTRON\WPGoneControl;

use iTRON\WPGoneControl\Controller\Activation;
use iTRON\WPGoneControl\Controller\MainController;
use iTRON\WPGoneControl\Controller\TemplateController;

const PLUGIN_SLUG = 'gone-control';
const VERSION     = '0.3';

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

$gc_db      = new Database();
$gc_main    = new MainController( $gc_db, new TemplateController( $gc_db ) );
$gc_activation = new Activation( $gc_db );

register_activation_hook( __FILE__, [ $gc_activation, 'processActivationHook' ] );

$gc_activation::init();
$gc_main->register();

( new Settings() )::init();
