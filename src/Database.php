<?php

namespace iTRON\WPGoneControl;

class Database {
	public function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'gone_control_gone_urls';
	}

	public function activate() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $this->get_table_name();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			object_type varchar(20) NOT NULL,
			object_id bigint(20) unsigned NOT NULL,
			url_path text NOT NULL,
			url_hash char(32) NOT NULL,
			deleted_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY url_hash (url_hash),
			KEY object_type_id (object_type, object_id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public function normalize_path( $url_or_path ) {
		$path = wp_parse_url( $url_or_path, PHP_URL_PATH );
		if ( null === $path || false === $path ) {
			$path = $url_or_path;
		}

		$path = '/' . ltrim( (string) $path, '/' );
		$path = rtrim( $path, '/' );
		$path = strtolower( $path );

		return '' === $path ? '/' : $path;
	}

	public function store_url( $url, $object_type, $object_id ) {
		global $wpdb;

		$table_name = $this->get_table_name();
		$path       = $this->normalize_path( $url );

		if ( '/' === $path ) {
			return;
		}

		$hash = md5( $path );

		$wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO {$table_name} (object_type, object_id, url_path, url_hash, deleted_at)
				VALUES (%s, %d, %s, %s, %s)",
				$object_type,
				$object_id,
				$path,
				$hash,
				current_time( 'mysql' )
			)
		);
	}

	public function url_exists( $path ): bool {
		global $wpdb;

		$table_name = $this->get_table_name();
		$hash       = md5( $path );

		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table_name} WHERE url_hash = %s LIMIT 1",
				$hash
			)
		);
	}

	public function get_entries( int $limit = 100 ): array {
		global $wpdb;

		$table_name = $this->get_table_name();

		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, object_type, object_id, url_path, deleted_at
				FROM {$table_name}
				ORDER BY deleted_at DESC
				LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}

	public function delete_entries( array $ids ): int {
		global $wpdb;

		$table_name = $this->get_table_name();
		$ids        = array_values( array_filter( array_map( 'absint', $ids ) ) );

		if ( empty( $ids ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$query        = $wpdb->prepare(
			"DELETE FROM {$table_name} WHERE id IN ({$placeholders})",
			$ids
		);

		return (int) $wpdb->query( $query );
	}
}
