<?php

if ( ! defined( 'ABSPATH' ) ) exit;
?>

<p><?php echo esc_html__( 'Import URLs from CSV (first column only) or from a plain text file (one URL per line).', 'gone-control' ); ?></p>

<h2><?php echo esc_html__( 'CSV import', 'gone-control' ); ?></h2>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
	<?php wp_nonce_field( 'gone_control_import_entries' ); ?>
	<input type="hidden" name="action" value="<?php echo esc_attr( $args['action'] ?? '' ); ?>">
	<input type="hidden" name="gone_control_import_type" value="csv">
	<input type="file" name="gone_control_import_file" accept=".csv,text/csv">
	<?php submit_button( __( 'Import CSV', 'gone-control' ) ); ?>
</form>

<h2><?php echo esc_html__( 'Text file import', 'gone-control' ); ?></h2>
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
	<?php wp_nonce_field( 'gone_control_import_entries' ); ?>
	<input type="hidden" name="action" value="<?php echo esc_attr( $args['action'] ?? '' ); ?>">
	<input type="hidden" name="gone_control_import_type" value="text">
	<input type="file" name="gone_control_import_file" accept=".txt,text/plain">
	<?php submit_button( __( 'Import text file', 'gone-control' ) ); ?>
</form>
