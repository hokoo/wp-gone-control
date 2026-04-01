<?php

namespace iTRON\GoneControl\Controller;

use iTRON\GoneControl\Database;
use iTRON\GoneControl\Settings;

if ( ! defined( 'ABSPATH' ) ) exit;

class Activation {
	private $database;

	public function __construct( Database $database ) {
		$this->database = $database;
	}

	public static function init(): void {
		add_action( 'gonecontrol_activate', [ self::class, 'processSecondPhaseActivation' ] );
	}

	public function processActivationHook(): void {
		self::grantCaps();
		$this->database->activate();

		// Carbon fields cannot be loaded during the activation hook,
		// and the plugin cannot be properly activated during the activation hook
		// because the activation hook runs too late. See wp-admin/plugins.php:do_action( 'activate_' . $plugin );
		// So, we just need to schedule the second phase of activation for the next normal request.
		// wp_schedule_single_event( time(), 'gonecontrol_activate' );
	}

	public static function processSecondPhaseActivation(): void {}

	public static function processDeactivationHook(): void {}

	private static function grantCaps(): void {
		$role = get_role( 'administrator' );
		$role->add_cap( Settings::GONECONTROL_MANAGE_CAPABILITY, true );

		do_action( 'gonecontrol_capabilities_set' );
	}
}
