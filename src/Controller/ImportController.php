<?php

namespace iTRON\GoneControl\Controller;

use iTRON\GoneControl\Database;
use iTRON\GoneControl\Settings;

use const iTRON\GoneControl\GONECONTROL_PLUGIN_DIR;

if ( ! defined( 'ABSPATH' ) ) exit;

class ImportController {
	private Database $database;

	public function __construct( Database $database ) {
		$this->database = $database;
	}

	public function renderPage(): void {
		if ( ! current_user_can( Settings::GONECONTROL_MANAGE_CAPABILITY ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'gone-control' ) );
		}

		$notice = $this->getNoticeDataFromRequest();

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Import Gone URLs', 'gone-control' ) . '</h1>';

		if ( $notice ) {
			printf(
				'<div class="notice %s"><p>%s</p></div>',
				esc_attr( $notice['class'] ),
				esc_html( $notice['message'] )
			);
		}

		$template_path = GONECONTROL_PLUGIN_DIR . 'templates/admin-import.php';
		load_template(
			$template_path,
			false,
			[
				'action' => 'gonecontrol_import_entries',
			]
		);
		echo '</div>';
	}

	public function handleImportEntries(): void {
		if ( ! current_user_can( Settings::GONECONTROL_MANAGE_CAPABILITY ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'gone-control' ) );
		}

		check_admin_referer( 'gonecontrol_import_entries' );

		if ( empty( $_FILES['gonecontrol_import_file']['tmp_name'] ) ) {
			$this->redirectStatus( 'error', 0, 0 );
		}

		$type     = isset( $_POST['gonecontrol_import_type'] ) ? sanitize_key( wp_unslash( $_POST['gonecontrol_import_type'] ) ) : '';
		$tmp_name = sanitize_text_field( wp_unslash( $_FILES['gonecontrol_import_file']['tmp_name'] ) );

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

	private function getNoticeDataFromRequest(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notices sourced from this plugin's own redirects.
		$status = isset( $_GET['gonecontrol_import_status'] ) ? sanitize_key( wp_unslash( $_GET['gonecontrol_import_status'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notices sourced from this plugin's own redirects.
		$added = isset( $_GET['gonecontrol_import_added'] ) ? absint( wp_unslash( $_GET['gonecontrol_import_added'] ) ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notices sourced from this plugin's own redirects.
		$skipped = isset( $_GET['gonecontrol_import_skipped'] ) ? absint( wp_unslash( $_GET['gonecontrol_import_skipped'] ) ) : 0;

		return $this->getNoticeData( $status, $added, $skipped );
	}

	private function getFileContents( string $file_path ) {
		$contents = file_get_contents( $file_path );

		if ( false === $contents ) {
			return false;
		}

		return $contents;
	}

	private function extractUrlsFromCsv( string $file_path ): array {
		$contents = $this->getFileContents( $file_path );
		if ( false === $contents ) {
			return [];
		}

		$urls      = [];
		$temp_file = new \SplTempFileObject();
		$temp_file->fwrite( $contents );
		$temp_file->rewind();

		while ( ! $temp_file->eof() ) {
			$row = $temp_file->fgetcsv();

			if ( false === $row || ! isset( $row[0] ) ) {
				continue;
			}

			$url = trim( (string) $row[0] );
			if ( '' === $url ) {
				continue;
			}

			$urls[] = $url;
		}

		return $urls;
	}

	private function extractUrlsFromText( string $file_path ): array {
		$contents = $this->getFileContents( $file_path );
		if ( false === $contents ) {
			return [];
		}

		$lines = preg_split( "/\r\n|\n|\r/", $contents );
		if ( false === $lines ) {
			return [];
		}

		$lines = array_filter(
			$lines,
			static function ( string $line ): bool {
				return '' !== trim( $line );
			}
		);

		return array_map( 'trim', $lines );
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
				'gonecontrol_import_status'  => $status,
				'gonecontrol_import_added'   => $added,
				'gonecontrol_import_skipped' => $skipped,
			],
			$redirect
		);

		wp_safe_redirect( $redirect );
		exit;
	}
}
