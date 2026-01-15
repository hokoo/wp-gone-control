<?php

namespace iTRON\WPGoneControl\Controller;

use iTRON\WPGoneControl\Database;
use iTRON\WPGoneControl\Settings;

use const iTRON\WPGoneControl\PLUGIN_DIR;

if ( ! defined( 'ABSPATH' ) ) exit;

class ImportController {
	private Database $database;

	public function __construct( Database $database ) {
		$this->database = $database;
	}

	public function renderPage(): void {
		if ( ! current_user_can( Settings::MANAGE_CAPS ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'gone-control' ) );
		}

		$status = isset( $_GET['gone_control_import_status'] ) ? sanitize_key( wp_unslash( $_GET['gone_control_import_status'] ) ) : '';
		$added  = isset( $_GET['gone_control_import_added'] ) ? absint( wp_unslash( $_GET['gone_control_import_added'] ) ) : 0;
		$skipped = isset( $_GET['gone_control_import_skipped'] ) ? absint( wp_unslash( $_GET['gone_control_import_skipped'] ) ) : 0;
		$notice = $this->getNoticeData( $status, $added, $skipped );

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Import Gone URLs', 'gone-control' ) . '</h1>';

		if ( $notice ) {
			printf(
				'<div class="notice %s"><p>%s</p></div>',
				esc_attr( $notice['class'] ),
				esc_html( $notice['message'] )
			);
		}

		$template_path = PLUGIN_DIR . 'templates/admin-import.php';
		load_template(
			$template_path,
			false,
			[
				'action' => 'gone_control_import_entries',
			]
		);
		echo '</div>';
	}

	public function handleImportEntries(): void {
		if ( ! current_user_can( Settings::MANAGE_CAPS ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'gone-control' ) );
		}

		check_admin_referer( 'gone_control_import_entries' );

		if ( empty( $_FILES['gone_control_import_file']['tmp_name'] ) ) {
			$this->redirectStatus( 'error', 0, 0 );
		}

		$type = isset( $_POST['gone_control_import_type'] ) ? sanitize_key( wp_unslash( $_POST['gone_control_import_type'] ) ) : '';
		$tmp_name = sanitize_text_field( wp_unslash( $_FILES['gone_control_import_file']['tmp_name'] ) );

		if ( ! is_uploaded_file( $tmp_name ) ) {
			$this->redirectStatus( 'error', 0, 0 );
		}

		if ( 'csv' === $type ) {
			$urls = $this->extractUrlsFromCsv( $tmp_name );
		} elseif ( 'text' === $type ) {
			$urls = $this->extractUrlsFromText( $tmp_name );
		} else {
			$this->redirectStatus( 'error', 0, 0 );
		}

		$urls = array_values( array_unique( array_filter( $urls ) ) );
		$added = 0;
		$skipped = 0;

		if ( [] === $urls ) {
			$this->redirectStatus( 'imported', 0, 0 );
		}

		foreach ( $urls as $url ) {
			$path = $this->database->normalize_path( $url );

			if ( '/' === $path || $this->database->url_exists( $path ) ) {
				$skipped++;
				continue;
			}

			$this->database->store_url( $url, 'import', 0 );
			$added++;
		}

		$this->redirectStatus( 'imported', $added, $skipped );
	}

	private function extractUrlsFromCsv( string $file_path ): array {
		$urls = [];
		$handle = fopen( $file_path, 'r' );
		if ( false === $handle ) {
			return [];
		}

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			if ( ! isset( $row[0] ) ) {
				continue;
			}

			$url = trim( (string) $row[0] );
			if ( '' === $url ) {
				continue;
			}

			$urls[] = $url;
		}

		fclose( $handle );

		return $urls;
	}

	private function extractUrlsFromText( string $file_path ): array {
		$lines = file( $file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( false === $lines ) {
			return [];
		}

		return array_map(
			static function ( string $line ): string {
				return trim( $line );
			},
			$lines
		);
	}

	private function getNoticeData( string $status, int $added, int $skipped ): array {
		if ( '' === $status ) {
			return [];
		}

		$message = '';
		$class   = 'notice-success';

		if ( 'imported' === $status ) {
			$message = sprintf(
				/* translators: 1: number of URLs imported, 2: number of URLs skipped */
				__( 'Import complete. Added %1$d URLs, skipped %2$d.', 'gone-control' ),
				$added,
				$skipped
			);
		} elseif ( 'error' === $status ) {
			$message = __( 'Unable to import URLs.', 'gone-control' );
			$class   = 'notice-error';
		}

		if ( '' === $message ) {
			return [];
		}

		return [
			'message' => $message,
			'class'   => $class,
		];
	}

	private function redirectStatus( string $status, int $added, int $skipped ): void {
		$redirect = admin_url( 'admin.php?page=gone-control-import' );
		$redirect = add_query_arg(
			[
				'gone_control_import_status'  => $status,
				'gone_control_import_added'   => $added,
				'gone_control_import_skipped' => $skipped,
			],
			$redirect
		);

		wp_safe_redirect( $redirect );
		exit;
	}
}
