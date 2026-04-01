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

namespace iTRON\GoneControl;

use iTRON\GoneControl\Controller\Activation;
use iTRON\GoneControl\Controller\ImportController;
use iTRON\GoneControl\Controller\MainController;
use iTRON\GoneControl\Controller\TemplateController;

const GONECONTROL_PLUGIN_SLUG = 'gone-control';
const GONECONTROL_VERSION     = '0.4';

define( __NAMESPACE__ . '\GONECONTROL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( __NAMESPACE__ . '\GONECONTROL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require __DIR__ . '/vendor/autoload.php';
}

Settings::setImportController( new ImportController( new Database() ) );
Settings::init();

register_activation_hook(
	__FILE__,
	[
		new Activation( new Database() ),
		'processActivationHook',
	]
);

Activation::init();

( new MainController( new Database(), new TemplateController( new Database() ) ) )->register();
