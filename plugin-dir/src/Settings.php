<?php

namespace iTRON\WPGoneControl;

use Carbon_Fields\Carbon_Fields;
use Carbon_Fields\Container;
use Carbon_Fields\Field;

if ( ! defined( 'ABSPATH' ) ) exit;

class Settings {
	public static string $optionPrefix;
	const MANAGE_CAPS = 'gone_control_manage_options';

	public static function init(): void {
		add_action( 'carbon_fields_register_fields', [ self::class, 'createOptions' ] );
		add_action( 'after_setup_theme', [ self::class, 'loadCarbon' ] );
		add_action( 'admin_post_gone_control_add_entry', [ self::class, 'handleAddEntry' ] );
		add_action( 'admin_post_gone_control_delete_entries', [ self::class, 'handleDeleteEntries' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueueAdminAssets' ] );

		self::$optionPrefix = PLUGIN_SLUG . '_';
	}

	public static function loadCarbon(): void {
		Carbon_Fields::boot();
	}

	public static function createOptions(): void {
		$entries_page = Container::make( OPTIONS_MODE, 'WP Gone Control' );
		$settings     = [];

		$settings[] = Field::make( 'html', 'gone_control_entries' )
		                  ->set_html( self::renderEntriesHtml() );

		$entries_page->set_page_file( 'gone-control' )
		             ->add_fields( $settings )
		             ->set_icon( 'dashicons-drumstick' )
		             ->where( 'current_user_capability', 'IN', [ self::MANAGE_CAPS, 'manage_options' ] );

		$settings_page_fields = [];

		$settings_page_fields[] = Field::make( 'html', 'gone_control_settings_intro' )
			->set_html( sprintf( '<p>%s</p>', esc_html__( 'Select the post types and taxonomies that should be processed by Gone Control.', 'gone-control' ) ) );

		$post_type_options = self::getPostTypeOptions();
		$taxonomy_options  = self::getTaxonomyOptions();
		$role_options      = self::getRoleOptions();

		$settings_page_fields[] = Field::make( 'set', self::$optionPrefix . 'post_types', __( 'Post types', 'gone-control' ) )
			->set_options( $post_type_options )
			->set_default_value( array_keys( $post_type_options ) );

		$settings_page_fields[] = Field::make( 'set', self::$optionPrefix . 'taxonomies', __( 'Taxonomies', 'gone-control' ) )
			->set_options( $taxonomy_options )
			->set_default_value( array_keys( $taxonomy_options ) );

		$settings_page_fields[] = Field::make( 'set', self::$optionPrefix . 'user_roles', __( 'User roles', 'gone-control' ) )
			->set_options( $role_options )
			->set_default_value( array_keys( $role_options ) );

		Container::make( OPTIONS_MODE, __( 'Gone Control Settings', 'gone-control' ) )
			->set_page_parent( 'gone-control' )
			->set_page_file( 'gone-control-settings' )
			->add_fields( $settings_page_fields )
			->where( 'current_user_capability', 'IN', [ self::MANAGE_CAPS, 'manage_options' ] );
	}

	private static function getPostTypeOptions(): array {
		$post_types = get_post_types( [ 'public' => true ], 'objects' );
		$options    = [];

		foreach ( $post_types as $post_type ) {
			$options[ $post_type->name ] = $post_type->labels->name ?? $post_type->name;
		}

		ksort( $options );

		return $options;
	}

	private static function getTaxonomyOptions(): array {
		$taxonomies = get_taxonomies( [ 'public' => true ], 'objects' );
		$options    = [];

		foreach ( $taxonomies as $taxonomy ) {
			$options[ $taxonomy->name ] = $taxonomy->labels->name ?? $taxonomy->name;
		}

		ksort( $options );

		return $options;
	}

	private static function getRoleOptions(): array {
		$roles   = wp_roles();
		$options = [];

		if ( ! $roles ) {
			return $options;
		}

		foreach ( $roles->roles as $role_slug => $role_data ) {
			$options[ $role_slug ] = $role_data['name'] ?? $role_slug;
		}

		ksort( $options );

		return $options;
	}

	private static function normalizeSelection( $selected, array $available ): array {
		if ( null === $selected || '' === $selected ) {
			return $available;
		}

		$selected = array_map( 'strval', (array) $selected );
		$filtered = array_values( array_intersect( $available, $selected ) );

		return $filtered;
	}

	public static function getEnabledPostTypes(): array {
		$options   = array_keys( self::getPostTypeOptions() );
		$selected  = self::getOption( 'post_types' );
		$processed = self::normalizeSelection( $selected, $options );

		return $processed;
	}

	public static function getEnabledTaxonomies(): array {
		$options   = array_keys( self::getTaxonomyOptions() );
		$selected  = self::getOption( 'taxonomies' );
		$processed = self::normalizeSelection( $selected, $options );

		return $processed;
	}

	public static function getEnabledRoles(): array {
		$options   = array_keys( self::getRoleOptions() );
		$selected  = self::getOption( 'user_roles' );
		$processed = self::normalizeSelection( $selected, $options );

		return $processed;
	}

	public static function isPostTypeEnabled( string $post_type ): bool {
		$enabled = self::getEnabledPostTypes();

		return in_array( $post_type, $enabled, true );
	}

	public static function isTaxonomyEnabled( string $taxonomy ): bool {
		$enabled = self::getEnabledTaxonomies();

		return in_array( $taxonomy, $enabled, true );
	}

	public static function isUserEnabled( array $roles ): bool {
		$enabled_roles = self::getEnabledRoles();

		if ( [] === $roles ) {
			return true;
		}

		foreach ( $roles as $role ) {
			if ( in_array( $role, $enabled_roles, true ) ) {
				return true;
			}
		}

		return false;
	}

	private static function renderEntriesHtml(): string {
		$db      = new Database();
		$entries = $db->get_entries();
		$status  = isset( $_GET['gone_control_status'] ) ? sanitize_key( wp_unslash( $_GET['gone_control_status'] ) ) : '';
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

	public static function enqueueAdminAssets(): void {
		$screen = get_current_screen();
		if ( ! $screen || 'toplevel_page_gone-control' !== $screen->id ) {
			return;
		}

		wp_enqueue_script(
			'gone-control-admin-entries',
			PLUGIN_URL . 'assets/admin-entries.js',
			[ 'jquery' ],
			VERSION,
			true
		);

		wp_enqueue_style(
			'gone-control-admin-entries',
			PLUGIN_URL . 'assets/admin-entries.css',
			[],
			VERSION
		);

		wp_localize_script(
			'gone-control-admin-entries',
			'wpGoneControlEntries',
			[
				'userId'           => get_current_user_id(),
				'storageKeyPrefix' => 'gone_control_entries_per_page_',
			]
		);
	}

	private static function getNoticeData( string $status ): array {
		if ( '' === $status ) {
			return [];
		}

		$message = '';
		$class   = 'notice-success';

		if ( 'added' === $status ) {
			$message = __( 'Entry added.', 'gone-control' );
		} elseif ( 'deleted' === $status ) {
			$message = __( 'Entries deleted.', 'gone-control' );
		} elseif ( 'error' === $status ) {
			$message = __( 'Unable to complete the action.', 'gone-control' );
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
			wp_die( esc_html__( 'Insufficient permissions.', 'gone-control' ) );
		}

		check_admin_referer( 'gone_control_add_entry' );

		$url = isset( $_POST['gone_control_url'] ) ? sanitize_text_field( wp_unslash( $_POST['gone_control_url'] ) ) : '';
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
			wp_die( esc_html__( 'Insufficient permissions.', 'gone-control' ) );
		}

		check_admin_referer( 'gone_control_delete_entries' );

		//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$ids = isset( $_POST['gone_control_ids'] ) ? (array) wp_unslash( $_POST['gone_control_ids'] ) : [];
		$ids = array_map( 'absint', $ids ); // Sanitize IDs

		$db = new Database();
		$db->delete_entries( $ids );

		self::redirectWithStatus( 'deleted' );
	}

	private static function redirectWithStatus( string $status ): void {
		$redirect = wp_get_referer();
		if ( ! $redirect ) {
			$redirect = admin_url( 'admin.php?page=gone-control' );
		}

		wp_safe_redirect( add_query_arg( 'gone_control_status', $status, $redirect ) );
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
