<?php

namespace iTRON\WPGoneControl;

use Carbon_Fields\Carbon_Fields;
use Carbon_Fields\Container;
use Carbon_Fields\Field;

class Settings {
	public static string $optionPrefix;
	const MANAGE_CAPS = 'wp_gone_control_manage_options';

	public static function init(): void {
		add_action( 'carbon_fields_register_fields', [ self::class, 'createOptions' ] );
		add_action( 'after_setup_theme', [ self::class, 'loadCarbon' ] );
		add_action( 'admin_post_wp_gone_control_add_entry', [ self::class, 'handleAddEntry' ] );
		add_action( 'admin_post_wp_gone_control_delete_entries', [ self::class, 'handleDeleteEntries' ] );

		self::$optionPrefix = PLUGIN_SLUG . '_';
	}

	public static function loadCarbon(): void {
		Carbon_Fields::boot();
	}

	public static function createOptions(): void {
		$option_page = Container::make( OPTIONS_MODE, 'WP Gone Control' );
		$settings    = [];

		$settings[] = Field::make( 'html', 'wp_gone_control_entries' )
		                  ->set_html( self::renderEntriesHtml() );

		$option_page->set_page_file( 'wp-gone-control' )
		            ->add_fields( $settings )
		            ->set_icon( 'dashicons-drumstick' )
		            ->where( 'current_user_capability', 'IN', [ self::MANAGE_CAPS, 'manage_options' ] );
	}

	private static function renderEntriesHtml(): string {
		$db      = new Database();
		$entries = $db->get_entries();
		$status  = isset( $_GET['wp_gone_control_status'] ) ? sanitize_key( wp_unslash( $_GET['wp_gone_control_status'] ) ) : '';
		$notice  = self::getNoticeData( $status );

		ob_start();
		$template_path = PLUGIN_DIR . 'templates/admin-entries.php';
		load_template(
			$template_path,
			false,
			[
				'entries' => $entries,
				'notice'  => $notice,
			]
		);
		return (string) ob_get_clean();
	}

	private static function getNoticeData( string $status ): array {
		if ( '' === $status ) {
			return [];
		}

		$message = '';
		$class   = 'notice-success';

		if ( 'added' === $status ) {
			$message = __( 'Entry added.', 'wp-gone-control' );
		} elseif ( 'deleted' === $status ) {
			$message = __( 'Entries deleted.', 'wp-gone-control' );
		} elseif ( 'error' === $status ) {
			$message = __( 'Unable to complete the action.', 'wp-gone-control' );
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

	public static function handleAddEntry(): void {
		if ( ! current_user_can( self::MANAGE_CAPS ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wp-gone-control' ) );
		}

		check_admin_referer( 'wp_gone_control_add_entry' );

		$url = isset( $_POST['wp_gone_control_url'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_gone_control_url'] ) ) : '';
		$url = trim( $url );

		if ( '' === $url ) {
			self::redirectWithStatus( 'error' );
		}

		$db = new Database();
		$db->store_url( $url, 'manual', 0 );

		self::redirectWithStatus( 'added' );
	}

	public static function handleDeleteEntries(): void {
		if ( ! current_user_can( self::MANAGE_CAPS ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wp-gone-control' ) );
		}

		check_admin_referer( 'wp_gone_control_delete_entries' );

		$ids = isset( $_POST['wp_gone_control_ids'] ) ? (array) wp_unslash( $_POST['wp_gone_control_ids'] ) : [];
		$ids = array_map( 'absint', $ids );

		$db = new Database();
		$db->delete_entries( $ids );

		self::redirectWithStatus( 'deleted' );
	}

	private static function redirectWithStatus( string $status ): void {
		$redirect = wp_get_referer();
		if ( ! $redirect ) {
			$redirect = admin_url( 'admin.php?page=wp-gone-control' );
		}

		wp_safe_redirect( add_query_arg( 'wp_gone_control_status', $status, $redirect ) );
		exit;
	}

	private static function isOverloaded( $optionSlug ): bool {
		return defined( 'WP_GONE_CONTROL_' . strtoupper( $optionSlug ) );
	}

	private static function getOverloaded( $optionSlug ) {
		return constant( 'WP_GONE_CONTROL_' . strtoupper( $optionSlug ) );
	}

	/**
	 * @todo Cache invalidation.
	 *
	 * @param string $optionSlug
	 *
	 * @return mixed|null
	 */
	public static function getOption( string $optionSlug ) {
		if ( self::isOverloaded( $optionSlug ) ) {
			return self::getOverloaded( $optionSlug );
		}

		// Carbon Fields does not have a built-in caching mechanism, lol.
		$cache = wp_cache_get( $optionSlug, PLUGIN_SLUG );
		if ( false !== $cache ) {
			return $cache;
		}

		$value = carbon_get_theme_option( self::$optionPrefix . $optionSlug );
		wp_cache_set( $optionSlug, $value, PLUGIN_SLUG );

		return $value;
	}

	public static function getInterval(): int {
		return (int) self::getOption( 'reset_interval' );
	}
}
