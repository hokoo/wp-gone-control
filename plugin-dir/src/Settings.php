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

		ob_start();
		?>
		<div class="wp-gone-control-admin">
			<?php self::renderNotice(); ?>
			<h2><?php esc_html_e( 'Добавить запись', 'wp-gone-control' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="wp_gone_control_add_entry">
				<?php wp_nonce_field( 'wp_gone_control_add_entry' ); ?>
				<input type="text" name="wp_gone_control_url" class="regular-text" placeholder="/removed-path" required>
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Добавить', 'wp-gone-control' ); ?>
				</button>
			</form>

			<h2><?php esc_html_e( 'Список записей', 'wp-gone-control' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="wp_gone_control_delete_entries">
				<?php wp_nonce_field( 'wp_gone_control_delete_entries' ); ?>
				<table class="widefat striped">
					<thead>
						<tr>
							<th class="manage-column column-cb check-column">
								<input type="checkbox" onclick="jQuery('.wp-gone-control-entry-checkbox').prop('checked', this.checked);">
							</th>
							<th><?php esc_html_e( 'URL', 'wp-gone-control' ); ?></th>
							<th><?php esc_html_e( 'Тип объекта', 'wp-gone-control' ); ?></th>
							<th><?php esc_html_e( 'ID объекта', 'wp-gone-control' ); ?></th>
							<th><?php esc_html_e( 'Дата удаления', 'wp-gone-control' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $entries ) ) : ?>
							<tr>
								<td colspan="5"><?php esc_html_e( 'Нет записей.', 'wp-gone-control' ); ?></td>
							</tr>
						<?php else : ?>
							<?php foreach ( $entries as $entry ) : ?>
								<tr>
									<th class="check-column">
										<input type="checkbox" class="wp-gone-control-entry-checkbox" name="wp_gone_control_ids[]" value="<?php echo esc_attr( (string) $entry['id'] ); ?>">
									</th>
									<td><?php echo esc_html( $entry['url_path'] ); ?></td>
									<td><?php echo esc_html( $entry['object_type'] ); ?></td>
									<td><?php echo esc_html( (string) $entry['object_id'] ); ?></td>
									<td><?php echo esc_html( $entry['deleted_at'] ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
				<p>
					<button type="submit" class="button button-secondary">
						<?php esc_html_e( 'Удалить выбранные', 'wp-gone-control' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private static function renderNotice(): void {
		$status = isset( $_GET['wp_gone_control_status'] ) ? sanitize_key( wp_unslash( $_GET['wp_gone_control_status'] ) ) : '';
		if ( '' === $status ) {
			return;
		}

		$message = '';
		$class   = 'notice-success';

		if ( 'added' === $status ) {
			$message = __( 'Запись добавлена.', 'wp-gone-control' );
		} elseif ( 'deleted' === $status ) {
			$message = __( 'Записи удалены.', 'wp-gone-control' );
		} elseif ( 'error' === $status ) {
			$message = __( 'Не удалось выполнить действие.', 'wp-gone-control' );
			$class   = 'notice-error';
		}

		if ( '' === $message ) {
			return;
		}

		printf(
			'<div class="notice %1$s"><p>%2$s</p></div>',
			esc_attr( $class ),
			esc_html( $message )
		);
	}

	public static function handleAddEntry(): void {
		if ( ! current_user_can( self::MANAGE_CAPS ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Недостаточно прав.', 'wp-gone-control' ) );
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
			wp_die( esc_html__( 'Недостаточно прав.', 'wp-gone-control' ) );
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
