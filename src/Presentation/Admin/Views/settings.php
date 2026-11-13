<?php
/**
 * Settings screen.
 *
 * @var string|null   $notice
 * @var string|null   $error
 * @var int           $days
 * @var array<int>    $allowedDays
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$retentionLabels = array(
	0   => __( 'Keep forever', 'wp-log-watch' ),
	7   => __( '7 days', 'wp-log-watch' ),
	30  => __( '30 days', 'wp-log-watch' ),
	90  => __( '90 days', 'wp-log-watch' ),
	180 => __( '180 days', 'wp-log-watch' ),
	365 => __( '1 year', 'wp-log-watch' ),
);
?>
<div class="wrap wp-log-watch">
	<h1><?php esc_html_e( 'WP Log Watch Settings', 'wp-log-watch' ); ?></h1>

	<?php if ( $notice !== null ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo esc_html( $notice ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $error !== null ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo esc_html( $error ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post">
		<?php wp_nonce_field( 'wp_log_watch_settings', '_wpnonce' ); ?>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="wp-log-watch-retention"><?php esc_html_e( 'Retention period', 'wp-log-watch' ); ?></label>
					</th>
					<td>
						<select id="wp-log-watch-retention" name="retention_days">
							<?php foreach ( $allowedDays as $value ) : ?>
								<option value="<?php echo esc_attr( (string) $value ); ?>" <?php selected( $days, $value ); ?>>
									<?php echo esc_html( $retentionLabels[ $value ] ?? (string) $value ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php esc_html_e( 'Audit entries older than this period are removed automatically by a daily scheduled job.', 'wp-log-watch' ); ?>
						</p>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit">
			<button type="submit" name="wp_log_watch_save" value="1" class="button button-primary">
				<?php esc_html_e( 'Save Changes', 'wp-log-watch' ); ?>
			</button>
		</p>
	</form>
</div>
