<?php

namespace iTRON\WPGoneControl;

if ( ! defined( 'ABSPATH' ) ) exit;

class Settings {
	public static string $optionPrefix;
	const MANAGE_CAPS = 'gone_control_manage_options';

	public static function init(): void {
		add_action( 'admin_menu', [ self::class, 'registerAdminPages' ] );
		add_action( 'admin_post_gone_control_add_entry', [ self::class, 'handleAddEntry' ] );
		add_action( 'admin_post_gone_control_delete_entries', [ self::class, 'handleDeleteEntries' ] );
		add_action( 'admin_post_gone_control_save_settings', [ self::class, 'handleSaveSettings' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueueAdminAssets' ] );

		self::$optionPrefix = PLUGIN_SLUG . '_';
	}

	public static function registerAdminPages(): void {
		$capability = self::getManageCapability();

		add_menu_page(
			__( 'WP Gone Control', 'gone-control' ),
			__( 'WP Gone Control', 'gone-control' ),
			$capability,
			'gone-control',
			[ self::class, 'renderEntriesPage' ],
			'dashicons-drumstick'
		);

		add_submenu_page(
			'gone-control',
			__( 'WP Gone Control', 'gone-control' ),
			__( '410 list', 'gone-control' ),
			$capability,
			'gone-control',
			[ self::class, 'renderEntriesPage' ]
		);

		add_submenu_page(
			'gone-control',
			__( 'Gone Control Settings', 'gone-control' ),
			__( 'Settings', 'gone-control' ),
			$capability,
			'gone-control-settings',
			[ self::class, 'renderSettingsPage' ]
		);
	}

	private static function getManageCapability(): string {
		if ( current_user_can( self::MANAGE_CAPS ) ) {
			return self::MANAGE_CAPS;
		}

		return 'manage_options';
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

		if ( $roles ) {
			foreach ( $roles->roles as $role_slug => $role_data ) {
				$options[ $role_slug ] = $role_data['name'] ?? $role_slug;
			}

			ksort( $options );
		}

		$options['none'] = __( 'No role', 'gone-control' );

		return $options;
	}

	private static function normalizeSelection( $selected, array $available ): array {
		if ( null === $selected || '' === $selected || false === $selected ) {
			return $available;
		}

		$selected = array_map( 'strval', (array) $selected );
		$filtered = array_values( array_intersect( $available, $selected ) );

		return $filtered;
	}

	public static function getEnabledPostTypes(): array {
		$options   = array_keys( self::getPostTypeOptions() );
		$selected  = self::isOverloaded( 'post_types' ) ? self::getOverloaded( 'post_types' ) : self::getOption( 'post_types' );
		$processed = self::normalizeSelection( $selected, $options );

		return $processed;
	}

	public static function getEnabledTaxonomies(): array {
		$options   = array_keys( self::getTaxonomyOptions() );
		$selected  = self::isOverloaded( 'taxonomies' ) ? self::getOverloaded( 'taxonomies' ) : self::getOption( 'taxonomies' );
		$processed = self::normalizeSelection( $selected, $options );

		return $processed;
	}

	public static function getEnabledRoles(): array {
		$options   = array_keys( self::getRoleOptions() );
		$selected  = self::isOverloaded( 'user_roles' ) ? self::getOverloaded( 'user_roles' ) : self::getOption( 'user_roles' );
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
			return in_array( 'none', $enabled_roles, true );
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

	public static function renderEntriesPage(): void {
		if ( ! current_user_can( self::MANAGE_CAPS ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'gone-control' ) );
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'WP Gone Control', 'gone-control' ) . '</h1>';
		echo self::renderEntriesHtml();
		echo '</div>';
	}

	public static function renderSettingsPage(): void {
		if ( ! current_user_can( self::MANAGE_CAPS ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'gone-control' ) );
		}

		$status = isset( $_GET['gone_control_settings_status'] ) ? sanitize_key( wp_unslash( $_GET['gone_control_settings_status'] ) ) : '';
		$notice = self::getSettingsNoticeData( $status );

		$post_type_options = self::getPostTypeOptions();
		$taxonomy_options  = self::getTaxonomyOptions();
		$role_options      = self::getRoleOptions();
		$post_types_locked = self::isOverloaded( 'post_types' );
		$taxonomies_locked = self::isOverloaded( 'taxonomies' );
		$roles_locked      = self::isOverloaded( 'user_roles' );
		$post_type_defaults = $post_types_locked
			? self::normalizeSelection( self::getOverloaded( 'post_types' ), array_keys( $post_type_options ) )
			: self::getEnabledPostTypes();
		$taxonomy_defaults = $taxonomies_locked
			? self::normalizeSelection( self::getOverloaded( 'taxonomies' ), array_keys( $taxonomy_options ) )
			: self::getEnabledTaxonomies();
		$role_defaults = $roles_locked
			? self::normalizeSelection( self::getOverloaded( 'user_roles' ), array_keys( $role_options ) )
			: self::getEnabledRoles();

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Gone Control Settings', 'gone-control' ) . '</h1>';

		if ( $notice ) {
			printf(
				'<div class="notice %s"><p>%s</p></div>',
				esc_attr( $notice['class'] ),
				esc_html( $notice['message'] )
			);
		}

		echo '<p>' . esc_html__( 'Select the post types and taxonomies that should be processed by Gone Control.', 'gone-control' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'gone_control_save_settings' );
		echo '<input type="hidden" name="action" value="gone_control_save_settings" />';

		$post_types_description = $post_types_locked
			? __( 'Post types are locked because the WP_GONE_CONTROL_POST_TYPES constant is defined.', 'gone-control' )
			: '';
		self::renderCheckboxGroup( 'post_types', __( 'Post types', 'gone-control' ), $post_type_options, $post_type_defaults, $post_types_locked, $post_types_description );

		$taxonomies_description = $taxonomies_locked
			? __( 'Taxonomies are locked because the WP_GONE_CONTROL_TAXONOMIES constant is defined.', 'gone-control' )
			: '';
		self::renderCheckboxGroup( 'taxonomies', __( 'Taxonomies', 'gone-control' ), $taxonomy_options, $taxonomy_defaults, $taxonomies_locked, $taxonomies_description );

		$roles_description = $roles_locked
			? __( 'User roles are locked because the WP_GONE_CONTROL_USER_ROLES constant is defined.', 'gone-control' )
			: '';
		self::renderCheckboxGroup( 'user_roles', __( 'User roles', 'gone-control' ), $role_options, $role_defaults, $roles_locked, $roles_description );

		if ( ! $post_types_locked || ! $taxonomies_locked || ! $roles_locked ) {
			submit_button( __( 'Save Settings', 'gone-control' ) );
		}

		echo '</form>';
		echo '</div>';
	}

	private static function renderCheckboxGroup( string $slug, string $label, array $options, array $selected, bool $disabled, string $description = '' ): void {
		echo '<fieldset>';
		echo '<legend class="screen-reader-text">' . esc_html( $label ) . '</legend>';
		echo '<h2>' . esc_html( $label ) . '</h2>';
		if ( '' !== $description ) {
			echo '<p class="description">' . esc_html( $description ) . '</p>';
		}
		echo '<div class="gone-control-settings-group">';

		foreach ( $options as $value => $option_label ) {
			$checked = in_array( $value, $selected, true ) ? 'checked' : '';
			$disabled_attr = $disabled ? 'disabled' : '';
			printf(
				'<label style="display:block;margin:4px 0;"><input type="checkbox" name="%s[]" value="%s" %s %s /> %s</label>',
				esc_attr( $slug ),
				esc_attr( $value ),
				$checked,
				$disabled_attr,
				esc_html( $option_label )
			);
		}

		echo '</div>';
		echo '</fieldset>';
	}

	public static function handleSaveSettings(): void {
		if ( ! current_user_can( self::MANAGE_CAPS ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'gone-control' ) );
		}

		check_admin_referer( 'gone_control_save_settings' );

		$post_type_options = array_keys( self::getPostTypeOptions() );
		$taxonomy_options  = array_keys( self::getTaxonomyOptions() );
		$role_options      = array_keys( self::getRoleOptions() );

		if ( ! self::isOverloaded( 'post_types' ) ) {
			$post_types = isset( $_POST['post_types'] ) ? (array) wp_unslash( $_POST['post_types'] ) : [];
			$post_types = self::normalizeSelection( $post_types, $post_type_options );
			update_option( self::$optionPrefix . 'post_types', $post_types );
		}

		if ( ! self::isOverloaded( 'taxonomies' ) ) {
			$taxonomies = isset( $_POST['taxonomies'] ) ? (array) wp_unslash( $_POST['taxonomies'] ) : [];
			$taxonomies = self::normalizeSelection( $taxonomies, $taxonomy_options );
			update_option( self::$optionPrefix . 'taxonomies', $taxonomies );
		}

		if ( ! self::isOverloaded( 'user_roles' ) ) {
			$roles = isset( $_POST['user_roles'] ) ? (array) wp_unslash( $_POST['user_roles'] ) : [];
			$roles = self::normalizeSelection( $roles, $role_options );
			update_option( self::$optionPrefix . 'user_roles', $roles );
		}

		$redirect = add_query_arg( 'gone_control_settings_status', 'saved', admin_url( 'admin.php?page=gone-control-settings' ) );
		wp_safe_redirect( $redirect );
		exit;
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

	private static function getSettingsNoticeData( string $status ): array {
		if ( 'saved' !== $status ) {
			return [];
		}

		return [
			'message' => __( 'Settings updated.', 'gone-control' ),
			'class'   => 'notice-success',
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

		$option_name = self::$optionPrefix . $optionSlug;

		return get_option( $option_name );
	}

	public static function getInterval(): int {
		return (int) self::getOption( 'reset_interval' );
	}
}
