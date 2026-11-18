<?php
/**
 * Audit log detail screen.
 *
 * @var \WPLogWatch\Domain\Entities\AuditLog $log
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$metadata = $log->metadata();
$context  = isset( $metadata['context'] ) ? (string) $metadata['context'] : '';
$actorId  = $log->actor()->id();

if ( $log->actor()->isUser() && $actorId !== null ) {
	$actorUser  = get_userdata( $actorId );
	$actorLabel = $actorUser instanceof \WP_User ? $actorUser->user_login : ( '#' . $actorId );
} else {
	$actorLabel = $log->actor()->type()->value;
}

$listUrl = admin_url( 'admin.php?page=wp-log-watch' );
?>
<div class="wrap wp-log-watch">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Audit Log Entry', 'wp-log-watch' ); ?></h1>
	<a href="<?php echo esc_url( $listUrl ); ?>" class="page-title-action"><?php esc_html_e( 'Back to list', 'wp-log-watch' ); ?></a>

	<table class="widefat striped wp-log-watch__detail">
		<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'ID', 'wp-log-watch' ); ?></th>
				<td><?php echo esc_html( (string) $log->id() ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Event UUID', 'wp-log-watch' ); ?></th>
				<td><code><?php echo esc_html( $log->eventUuid() ); ?></code></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Date', 'wp-log-watch' ); ?></th>
				<td><?php echo esc_html( get_date_from_gmt( $log->createdAt()->format( 'Y-m-d H:i:s' ) ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Event', 'wp-log-watch' ); ?></th>
				<td><code><?php echo esc_html( $log->eventType()->value() ); ?></code></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Actor', 'wp-log-watch' ); ?></th>
				<td>
					<?php echo esc_html( $actorLabel ); ?>
					<span class="description">(<?php echo esc_html( $log->actor()->type()->value ); ?>)</span>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Resource', 'wp-log-watch' ); ?></th>
				<td>
					<code><?php echo esc_html( $log->resourceType()->value() ); ?></code>
					<?php if ( $log->resourceId() !== null ) : ?>
						#<?php echo esc_html( (string) $log->resourceId() ); ?>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Description', 'wp-log-watch' ); ?></th>
				<td><?php echo esc_html( $log->description() ); ?></td>
			</tr>
			<?php if ( $context !== '' ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Context', 'wp-log-watch' ); ?></th>
					<td><code><?php echo esc_html( $context ); ?></code></td>
				</tr>
			<?php endif; ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'IP address', 'wp-log-watch' ); ?></th>
				<td><?php echo esc_html( $log->ipAddress() ?? '—' ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'User agent', 'wp-log-watch' ); ?></th>
				<td><?php echo esc_html( $log->userAgent() ?? '—' ); ?></td>
			</tr>
		</tbody>
	</table>

	<?php
	$before = isset( $metadata['before'] ) && is_array( $metadata['before'] ) ? $metadata['before'] : null;
	$after  = isset( $metadata['after'] ) && is_array( $metadata['after'] ) ? $metadata['after'] : null;
	?>

	<?php if ( $before !== null || $after !== null ) : ?>
		<h2><?php esc_html_e( 'Changes', 'wp-log-watch' ); ?></h2>
		<table class="widefat striped wp-log-watch__changes">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Field', 'wp-log-watch' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Before', 'wp-log-watch' ); ?></th>
					<th scope="col"><?php esc_html_e( 'After', 'wp-log-watch' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$fields = array_unique( array_merge( array_keys( $before ?? array() ), array_keys( $after ?? array() ) ) );
				foreach ( $fields as $field ) :
					$oldValue = $before[ $field ] ?? null;
					$newValue = $after[ $field ] ?? null;
					?>
					<tr>
						<td><code><?php echo esc_html( (string) $field ); ?></code></td>
						<td><?php echo esc_html( $oldValue === null ? '—' : (string) $oldValue ); ?></td>
						<td><?php echo esc_html( $newValue === null ? '—' : (string) $newValue ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Metadata', 'wp-log-watch' ); ?></h2>
	<pre class="wp-log-watch__json"><?php echo esc_html( (string) wp_json_encode( $metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); ?></pre>
</div>
