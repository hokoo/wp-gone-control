<?php
/**
 * @var array $entries
 * @var array $notice
 */
?>
<div class="wp-gone-control-admin">
	<?php if ( ! empty( $notice ) ) : ?>
		<div class="notice <?php echo esc_attr( $notice['class'] ); ?>"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
	<?php endif; ?>
	<h2><?php esc_html_e( 'Add entry', 'wp-gone-control' ); ?></h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="wp_gone_control_add_entry">
		<?php wp_nonce_field( 'wp_gone_control_add_entry' ); ?>
		<input type="text" name="wp_gone_control_url" class="regular-text" placeholder="/removed-path" required>
		<button type="submit" class="button button-primary">
			<?php esc_html_e( 'Add', 'wp-gone-control' ); ?>
		</button>
	</form>

	<h2><?php esc_html_e( 'Entry list', 'wp-gone-control' ); ?></h2>
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
					<th><?php esc_html_e( 'Object type', 'wp-gone-control' ); ?></th>
					<th><?php esc_html_e( 'Object ID', 'wp-gone-control' ); ?></th>
					<th><?php esc_html_e( 'Deleted at', 'wp-gone-control' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( empty( $entries ) ) : ?>
					<tr>
						<td colspan="5"><?php esc_html_e( 'No entries yet.', 'wp-gone-control' ); ?></td>
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
				<?php esc_html_e( 'Delete selected', 'wp-gone-control' ); ?>
			</button>
		</p>
	</form>
</div>
