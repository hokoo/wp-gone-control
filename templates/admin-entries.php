<?php
/**
 * @var array $args
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$entries = $args['entries'] ?? [];
$notice  = $args['notice'] ?? [];
?>
<div class="gone-control-admin">
	<?php if ( ! empty( $notice ) ) : ?>
		<div class="notice <?php echo esc_attr( $notice['class'] ); ?>"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
	<?php endif; ?>
	<h2><?php esc_html_e( 'Add entry', 'gone-control' ); ?></h2>
	<div class="gone-control-entry-actions">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="gone_control_add_entry">
			<?php wp_nonce_field( 'gone_control_add_entry' ); ?>
			<input type="text" name="gone_control_url" class="regular-text" placeholder="/removed-path" required>
			<button type="submit" class="button button-primary">
				<?php esc_html_e( 'Add', 'gone-control' ); ?>
			</button>
		</form>
		<?php if ( ! empty( $entries ) ) : ?>
			<div class="gone-control-pagination">
				<label>
					<?php esc_html_e( 'Entries per page', 'gone-control' ); ?>
						<input
							type="number"
							min="1"
							step="1"
							class="small-text gone-control-per-page"
							data-default="200"
							value="200"
							aria-label="<?php esc_attr_e( 'Entries per page', 'gone-control' ); ?>"
						>
				</label>
				<button type="button" class="button gone-control-prev">
					<?php esc_html_e( 'Previous', 'gone-control' ); ?>
				</button>
				<label>
					<?php esc_html_e( 'Page', 'gone-control' ); ?>
						<input
							type="number"
							min="1"
							step="1"
							class="small-text gone-control-page-input"
							value="1"
							aria-label="<?php esc_attr_e( 'Current page', 'gone-control' ); ?>"
						>
				</label>
				<span class="gone-control-page-total" aria-live="polite"></span>
				<button type="button" class="button gone-control-next">
					<?php esc_html_e( 'Next', 'gone-control' ); ?>
				</button>
			</div>
		<?php endif; ?>
	</div>

	<h2><?php esc_html_e( 'Entry list', 'gone-control' ); ?></h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="gone_control_delete_entries">
		<?php wp_nonce_field( 'gone_control_delete_entries' ); ?>
		<table class="widefat striped">
				<thead>
					<tr>
					<th class="manage-column column-cb check-column">
						<input type="checkbox" onclick="jQuery('.gone-control-entry-checkbox').prop('checked', this.checked);">
					</th>
					<th><?php esc_html_e( 'URL', 'gone-control' ); ?></th>
					<th><?php esc_html_e( 'Object type', 'gone-control' ); ?></th>
					<th><?php esc_html_e( 'Object ID', 'gone-control' ); ?></th>
					<th><?php esc_html_e( 'Deleted at', 'gone-control' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $entries ) ) : ?>
					<tr>
						<td colspan="5"><?php esc_html_e( 'No entries yet.', 'gone-control' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $entries as $entry ) : ?>
						<tr class="gone-control-entry-row">
							<th class="check-column">
								<input type="checkbox" class="gone-control-entry-checkbox" name="gone_control_ids[]" value="<?php echo esc_attr( (string) $entry['id'] ); ?>">
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
					<?php esc_html_e( 'Delete selected', 'gone-control' ); ?>
			</button>
		</p>
	</form>
</div>
