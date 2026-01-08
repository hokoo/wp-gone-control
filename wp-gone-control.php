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

function wp_gone_control_get_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'wp_gone_control_gone_urls';
}

function wp_gone_control_activate() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();
	$table_name      = wp_gone_control_get_table_name();

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
register_activation_hook( __FILE__, 'wp_gone_control_activate' );

function wp_gone_control_normalize_path( $url_or_path ) {
	$path = wp_parse_url( $url_or_path, PHP_URL_PATH );
	if ( null === $path || false === $path ) {
		$path = $url_or_path;
	}

	$path = '/' . ltrim( (string) $path, '/' );
	$path = rtrim( $path, '/' );
	$path = strtolower( $path );

	return '' === $path ? '/' : $path;
}

function wp_gone_control_store_url( $url, $object_type, $object_id ) {
	global $wpdb;

	$table_name = wp_gone_control_get_table_name();
	$path       = wp_gone_control_normalize_path( $url );

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

function wp_gone_control_post_is_public( WP_Post $post ) {
	$post_type = get_post_type_object( $post->post_type );
	$statuses  = apply_filters( 'wp_gone_control_post_statuses', array( 'publish' ) );

	if ( ! $post_type || ! $post_type->public ) {
		return false;
	}

	return in_array( $post->post_status, (array) $statuses, true );
}

function wp_gone_control_handle_post_remove( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) {
		return;
	}

	if ( ! wp_gone_control_post_is_public( $post ) ) {
		return;
	}

	$permalink = get_permalink( $post );
	if ( $permalink ) {
		wp_gone_control_store_url( $permalink, 'post', (int) $post_id );
	}
}
add_action( 'wp_trash_post', 'wp_gone_control_handle_post_remove', 10 );
add_action( 'before_delete_post', 'wp_gone_control_handle_post_remove', 10 );

function wp_gone_control_handle_term_remove( $term, $taxonomy ) {
	$term_obj = get_term( $term, $taxonomy );
	if ( ! $term_obj || is_wp_error( $term_obj ) ) {
		return;
	}

	$taxonomy_obj = get_taxonomy( $taxonomy );
	if ( ! $taxonomy_obj || ! $taxonomy_obj->public ) {
		return;
	}

	$term_link = get_term_link( $term_obj, $taxonomy );
	if ( ! is_wp_error( $term_link ) ) {
		wp_gone_control_store_url( $term_link, 'term', (int) $term_obj->term_id );
	}
}
add_action( 'pre_delete_term', 'wp_gone_control_handle_term_remove', 10, 2 );

function wp_gone_control_user_is_public( WP_User $user ) {
	return true;
}

function wp_gone_control_handle_user_remove( $user_id ) {
	$user = get_user_by( 'id', $user_id );
	if ( ! $user ) {
		return;
	}

	$is_public = apply_filters( 'wp_gone_control_is_user_public', wp_gone_control_user_is_public( $user ), $user );
	if ( ! $is_public ) {
		return;
	}

	$author_url = get_author_posts_url( $user->ID );
	if ( $author_url ) {
		wp_gone_control_store_url( $author_url, 'user', (int) $user_id );
	}
}
add_action( 'delete_user', 'wp_gone_control_handle_user_remove', 10, 1 );

function wp_gone_control_url_was_removed( $path ) {
	global $wpdb;

	$table_name = wp_gone_control_get_table_name();
	$hash       = md5( $path );

	return (bool) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$table_name} WHERE url_hash = %s LIMIT 1",
			$hash
		)
	);
}

function wp_gone_control_maybe_send_410() {
	if ( is_admin() || ! is_404() ) {
		return;
	}

	$path = '';
	if ( isset( $_SERVER['REQUEST_URI'] ) ) {
		$path = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
	}

	$path = wp_gone_control_normalize_path( $path );
	if ( ! $path ) {
		return;
	}

	if ( ! wp_gone_control_url_was_removed( $path ) ) {
		return;
	}

	status_header( 410 );
	nocache_headers();

	do_action( 'wp_gone_control_before_template', $path );
}
add_action( 'template_redirect', 'wp_gone_control_maybe_send_410', 0 );
