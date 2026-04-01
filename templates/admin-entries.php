<?php
/**
 * @var array $args
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$gonecontrol_entries = $args['entries'] ?? [];
$gonecontrol_notice  = $args['notice'] ?? [];
?>
<div class="gone-control-admin">
	<?php if ( ! empty( $gonecontrol_notice ) ) : ?>
		<div class="notice <?php echo esc_attr( $gonecontrol_notice['class'] ); ?>"><p><?php echo esc_html( $gonecontrol_notice['message'] ); ?></p></div>
	<?php endif; ?>
	<h2><?php esc_html_e( 'Add entry', 'gone-control' ); ?></h2>
	<div class="gone-control-entry-actions">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="gonecontrol_add_entry">
			<?php wp_nonce_field( 'gonecontrol_add_entry' ); ?>
			<input type="text" name="gonecontrol_url" class="regular-text" placeholder="/removed-path" required>
			<button type="submit" class="button button-primary">
				<?php esc_html_e( 'Add', 'gone-control' ); ?>
			</button>
		</form>
		<?php if ( ! empty( $gonecontrol_entries ) ) : ?>
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
		<input type="hidden" name="action" value="gonecontrol_delete_entries">
		<?php wp_nonce_field( 'gonecontrol_delete_entries' ); ?>
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
				<?php if ( empty( $gonecontrol_entries ) ) : ?>
					<tr>
						<td colspan="5"><?php esc_html_e( 'No entries yet.', 'gone-control' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $gonecontrol_entries as $gonecontrol_entry ) : ?>
						<tr class="gone-control-entry-row">
							<th class="check-column">
								<input type="checkbox" class="gone-control-entry-checkbox" name="gonecontrol_ids[]" value="<?php echo esc_attr( (string) $gonecontrol_entry['id'] ); ?>">
							</th>
							<td><?php echo esc_html( $gonecontrol_entry['url_path'] ); ?></td>
							<td><?php echo esc_html( $gonecontrol_entry['object_type'] ); ?></td>
							<td><?php echo esc_html( (string) $gonecontrol_entry['object_id'] ); ?></td>
							<td><?php echo esc_html( $gonecontrol_entry['deleted_at'] ); ?></td>
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
