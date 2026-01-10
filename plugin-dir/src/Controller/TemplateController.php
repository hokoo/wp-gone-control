<?php

namespace iTRON\WPGoneControl\Controller;

use iTRON\WPGoneControl\Database;

if ( ! defined( 'ABSPATH' ) ) exit;

class TemplateController {
	private $database;

	public function __construct( Database $database ) {
		$this->database = $database;
	}

	public function maybe_send_410() {
		if ( is_admin() || ! is_404() ) {
			return;
		}

		$path = '';
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$path = wp_parse_url( sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
		}

		$path = $this->database->normalize_path( $path );
		if ( ! $path ) {
			return;
		}

		if ( ! $this->database->url_exists( $path ) ) {
			return;
		}

		$proceed = apply_filters( 'itron/gone-control/send-410', true, $path );
		if ( ! $proceed ) {
			return;
		}

		status_header( 410 );
		nocache_headers();

		do_action( 'itron/gone-control/410-sent', $path );
	}
}
