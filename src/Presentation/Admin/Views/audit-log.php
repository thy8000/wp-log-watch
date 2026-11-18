<?php
/**
 * Audit log list screen.
 *
 * @var \WPLogWatch\Domain\Queries\AuditLogSearchResult $result
 * @var array<int, string>                              $actorNames
 * @var array<int, string>                              $users
 * @var array<string, string>                           $filters
 * @var array<string, string>                           $eventTypeOptions
 * @var array<string, string>                           $resourceTypeOptions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap wp-log-watch">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Audit Log', 'wp-log-watch' ); ?></h1>

	<?php
	$csvExportUrl  = wp_nonce_url(
		add_query_arg(
			array(
				'action' => 'export',
				'format' => 'csv',
			)
		),
		'wp_log_watch_export'
	);
	$jsonExportUrl = wp_nonce_url(
		add_query_arg(
			array(
				'action' => 'export',
				'format' => 'json',
			)
		),
		'wp_log_watch_export'
	);
	?>
	<a href="<?php echo esc_url( $csvExportUrl ); ?>" class="page-title-action"><?php esc_html_e( 'Export CSV', 'wp-log-watch' ); ?></a>
	<a href="<?php echo esc_url( $jsonExportUrl ); ?>" class="page-title-action"><?php esc_html_e( 'Export JSON', 'wp-log-watch' ); ?></a>

	<form method="get" class="wp-log-watch__filters">
		<input type="hidden" name="page" value="wp-log-watch" />

		<div class="wp-log-watch__filters-row">
			<label>
				<span><?php esc_html_e( 'From', 'wp-log-watch' ); ?></span>
				<input type="date" name="date_from" value="<?php echo esc_attr( $filters['date_from'] ); ?>" />
			</label>

			<label>
				<span><?php esc_html_e( 'To', 'wp-log-watch' ); ?></span>
				<input type="date" name="date_to" value="<?php echo esc_attr( $filters['date_to'] ); ?>" />
			</label>

			<label>
				<span><?php esc_html_e( 'User', 'wp-log-watch' ); ?></span>
				<select name="actor_id">
					<option value=""><?php esc_html_e( 'All users', 'wp-log-watch' ); ?></option>
					<?php foreach ( $users as $userId => $userLogin ) : ?>
						<option value="<?php echo esc_attr( (string) $userId ); ?>" <?php selected( $filters['actor_id'], (string) $userId ); ?>>
							<?php echo esc_html( $userLogin ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>

			<label>
				<span><?php esc_html_e( 'Event', 'wp-log-watch' ); ?></span>
				<select name="event_type">
					<option value=""><?php esc_html_e( 'All events', 'wp-log-watch' ); ?></option>
					<?php foreach ( $eventTypeOptions as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filters['event_type'], $value ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>

			<label>
				<span><?php esc_html_e( 'Resource', 'wp-log-watch' ); ?></span>
				<select name="resource_type">
					<option value=""><?php esc_html_e( 'All resources', 'wp-log-watch' ); ?></option>
					<?php foreach ( $resourceTypeOptions as $value => $label ) : ?>
						<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filters['resource_type'], $value ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>

			<label>
				<span><?php esc_html_e( 'Resource ID', 'wp-log-watch' ); ?></span>
				<input type="number" name="resource_id" value="<?php echo esc_attr( $filters['resource_id'] ); ?>" min="0" />
			</label>

			<label>
				<span><?php esc_html_e( 'Search', 'wp-log-watch' ); ?></span>
				<input type="search" name="s" value="<?php echo esc_attr( $filters['s'] ); ?>" />
			</label>

			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'wp-log-watch' ); ?></button>
			<a class="button" href="<?php echo esc_url( remove_query_arg( array( 's', 'date_from', 'date_to', 'actor_id', 'event_type', 'resource_type', 'resource_id', 'paged' ) ) ); ?>">
				<?php esc_html_e( 'Reset', 'wp-log-watch' ); ?>
			</a>
		</div>
	</form>

	<table class="wp-list-table widefat fixed striped wp-log-watch__table">
		<thead>
			<tr>
				<th scope="col" class="column-id"><?php esc_html_e( 'ID', 'wp-log-watch' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Date', 'wp-log-watch' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Actor', 'wp-log-watch' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Event', 'wp-log-watch' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Resource', 'wp-log-watch' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Description', 'wp-log-watch' ); ?></th>
				<th scope="col" class="column-ip"><?php esc_html_e( 'IP', 'wp-log-watch' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( $result->items() === array() ) : ?>
			<tr>
				<td colspan="7"><?php esc_html_e( 'No audit entries found.', 'wp-log-watch' ); ?></td>
			</tr>
		<?php endif; ?>

		<?php foreach ( $result->items() as $log ) : ?>
			<?php
			$actorId = $log->actor()->id();

			if ( $log->actor()->isUser() && $actorId !== null ) {
				$actorLabel = $actorNames[ $actorId ] ?? ( '#' . $actorId );
			} else {
				$actorLabel = $log->actor()->type()->value;
			}

			$resourceLabel = $log->resourceType()->value();
			if ( $log->resourceId() !== null ) {
				$resourceLabel .= ' #' . $log->resourceId();
			}

			$detailUrl = admin_url( 'admin.php?page=wp-log-watch&action=view&id=' . $log->id() );
			?>
			<tr>
				<td class="column-id">
					<a href="<?php echo esc_url( $detailUrl ); ?>"><?php echo esc_html( (string) $log->id() ); ?></a>
				</td>
				<td><?php echo esc_html( get_date_from_gmt( $log->createdAt()->format( 'Y-m-d H:i:s' ) ) ); ?></td>
				<td><?php echo esc_html( $actorLabel ); ?></td>
				<td><code><?php echo esc_html( $log->eventType()->value() ); ?></code></td>
				<td><?php echo esc_html( $resourceLabel ); ?></td>
				<td class="column-description"><?php echo esc_html( $log->description() ); ?></td>
				<td class="column-ip"><?php echo esc_html( $log->ipAddress() ?? '—' ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<?php if ( $result->totalPages() > 1 ) : ?>
		<div class="tablenav">
			<div class="tablenav-pages">
				<span class="displaying-num">
					<?php
					echo esc_html(
						sprintf(
						/* translators: %s: total number of entries. */
							_n( '%s entry', '%s entries', $result->total(), 'wp-log-watch' ),
							number_format_i18n( $result->total() )
						)
					);
					?>
				</span>

				<span class="pagination-links">
					<?php if ( $result->hasPrevious() ) : ?>
						<a class="prev-page button" href="<?php echo esc_url( add_query_arg( 'paged', $result->page() - 1 ) ); ?>">&lsaquo;</a>
					<?php endif; ?>

					<span class="paging-input">
						<span class="tablenav-paging-text">
							<?php
							echo esc_html(
								sprintf(
									'%d / %d',
									$result->page(),
									max( 1, $result->totalPages() )
								)
							);
							?>
						</span>
					</span>

					<?php if ( $result->hasNext() ) : ?>
						<a class="next-page button" href="<?php echo esc_url( add_query_arg( 'paged', $result->page() + 1 ) ); ?>">&rsaquo;</a>
					<?php endif; ?>
				</span>
			</div>
		</div>
	<?php endif; ?>
</div>
