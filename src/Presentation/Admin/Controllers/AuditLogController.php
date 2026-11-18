<?php

declare(strict_types=1);

namespace WPLogWatch\Presentation\Admin\Controllers;

use DateTimeImmutable;
use DateTimeZone;
use WPLogWatch\Application\DTOs\ExportAuditLogsInput;
use WPLogWatch\Application\DTOs\SearchAuditLogsInput;
use WPLogWatch\Application\UseCases\ExportAuditLogs;
use WPLogWatch\Application\UseCases\GetAuditLog;
use WPLogWatch\Application\UseCases\SearchAuditLogs;
use WPLogWatch\Domain\Entities\AuditLog;
use WPLogWatch\Domain\ValueObjects\EventType;
use WPLogWatch\Domain\ValueObjects\ResourceType;
use WPLogWatch\Infrastructure\WordPress\Capabilities;

/**
 * Handles the audit log admin screen: listing, filtering, detail and export.
 */
final class AuditLogController {

	private const PER_PAGE = 50;

	public function __construct(
		private readonly SearchAuditLogs $searchAuditLogs,
		private readonly GetAuditLog $getAuditLog,
		private readonly ExportAuditLogs $exportAuditLogs,
	) {
	}

	public function render(): void {
		if ( ! current_user_can( Capabilities::VIEW_LOGS ) ) {
			wp_die( esc_html__( 'You are not allowed to view the audit log.', 'wp-log-watch' ) );
		}

		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'list';

		if ( $action === 'export' ) {
			$this->handleExport();

			return;
		}

		if ( $action === 'view' ) {
			$this->renderDetail( isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0 );

			return;
		}

		$this->renderList();
	}

	private function renderList(): void {
		$input  = $this->buildInputFromRequest();
		$result = $this->searchAuditLogs->execute( $input );

		$actorNames = $this->actorNames( $result->items() );
		$users      = $this->userOptions();

		$filters             = $this->currentFilters();
		$eventTypeOptions    = $this->eventTypeOptions();
		$resourceTypeOptions = $this->resourceTypeOptions();

		include __DIR__ . '/../Views/audit-log.php';
	}

	private function renderDetail( int $id ): void {
		$log = $this->getAuditLog->execute( $id );

		if ( $log === null ) {
			wp_die( esc_html__( 'Audit log entry not found.', 'wp-log-watch' ) );
		}

		include __DIR__ . '/../Views/audit-log-detail.php';
	}

	private function handleExport(): void {
		if ( ! current_user_can( Capabilities::VIEW_LOGS ) ) {
			wp_die( esc_html__( 'You are not allowed to export the audit log.', 'wp-log-watch' ) );
		}

		check_admin_referer( 'wp_log_watch_export', '_wpnonce' );

		$format = isset( $_GET['format'] ) && $_GET['format'] === 'json'
			? ExportAuditLogsInput::FORMAT_JSON
			: ExportAuditLogsInput::FORMAT_CSV;

		$input  = $this->buildInputFromRequest();
		$result = $this->exportAuditLogs->execute( new ExportAuditLogsInput( $input->toCriteria(), $format ) );

		nocache_headers();

		header( 'Content-Type: ' . $result->contentType );
		header( 'Content-Disposition: attachment; filename="' . $result->filename . '"' );
		header( 'Content-Length: ' . strlen( $result->content ) );

		echo $result->content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV/JSON download.

		exit;
	}

	private function buildInputFromRequest(): SearchAuditLogsInput {
		$dateFrom     = $this->parseDate( isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '', false );
		$dateTo       = $this->parseDate( isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '', true );
		$actorId      = isset( $_GET['actor_id'] ) ? absint( $_GET['actor_id'] ) : 0;
		$eventType    = $this->parseEventType( isset( $_GET['event_type'] ) ? sanitize_text_field( wp_unslash( $_GET['event_type'] ) ) : '' );
		$resourceType = $this->parseResourceType( isset( $_GET['resource_type'] ) ? sanitize_text_field( wp_unslash( $_GET['resource_type'] ) ) : '' );
		$resourceId   = isset( $_GET['resource_id'] ) ? absint( $_GET['resource_id'] ) : 0;
		$search       = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$page         = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;

		return new SearchAuditLogsInput(
			dateFrom: $dateFrom,
			dateTo: $dateTo,
			actorId: $actorId > 0 ? $actorId : null,
			eventType: $eventType,
			resourceType: $resourceType,
			resourceId: $resourceId > 0 ? $resourceId : null,
			search: $search !== '' ? $search : null,
			page: $page,
			perPage: self::PER_PAGE,
		);
	}

	private function parseDate( string $raw, bool $endOfDay ): ?DateTimeImmutable {
		if ( $raw === '' ) {
			return null;
		}

		$date = sanitize_text_field( $raw );

		$parsed = \DateTime::createFromFormat( 'Y-m-d', $date );
		if ( $parsed === false || $parsed->format( 'Y-m-d' ) !== $date ) {
			return null;
		}

		$time = $endOfDay ? '23:59:59' : '00:00:00';
		$gmt  = get_gmt_from_date( $date . ' ' . $time );

		return new DateTimeImmutable( $gmt, new DateTimeZone( 'UTC' ) );
	}

	private function parseEventType( string $value ): ?EventType {
		if ( $value === '' ) {
			return null;
		}

		try {
			return EventType::fromString( $value );
		} catch ( \Throwable ) {
			return null;
		}
	}

	private function parseResourceType( string $value ): ?ResourceType {
		if ( $value === '' ) {
			return null;
		}

		try {
			return ResourceType::fromString( $value );
		} catch ( \Throwable ) {
			return null;
		}
	}

	/**
	 * @param AuditLog[] $items
	 *
	 * @return array<int, string>
	 */
	private function actorNames( array $items ): array {
		$ids = array();

		foreach ( $items as $log ) {
			$actorId = $log->actor()->id();
			if ( $actorId !== null ) {
				$ids[ $actorId ] = true;
			}
		}

		if ( $ids === array() ) {
			return array();
		}

		$names = array();
		$users = get_users(
			array(
				'include' => array_keys( $ids ),
				'fields'  => array( 'ID', 'user_login' ),
			)
		);

		foreach ( $users as $user ) {
			$names[ (int) $user->ID ] = (string) $user->user_login;
		}

		return $names;
	}

	/**
	 * @return array<int, string>
	 */
	private function userOptions(): array {
		$options = array();
		$users   = get_users(
			array(
				'fields'  => array( 'ID', 'user_login' ),
				'orderby' => 'user_login',
			)
		);

		foreach ( $users as $user ) {
			$options[ (int) $user->ID ] = (string) $user->user_login;
		}

		return $options;
	}

	/**
	 * @return array<string, string>
	 */
	private function currentFilters(): array {
		return array(
			'date_from'     => isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '',
			'date_to'       => isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '',
			'actor_id'      => isset( $_GET['actor_id'] ) ? (string) absint( $_GET['actor_id'] ) : '',
			'event_type'    => isset( $_GET['event_type'] ) ? sanitize_text_field( wp_unslash( $_GET['event_type'] ) ) : '',
			'resource_type' => isset( $_GET['resource_type'] ) ? sanitize_text_field( wp_unslash( $_GET['resource_type'] ) ) : '',
			'resource_id'   => isset( $_GET['resource_id'] ) ? (string) absint( $_GET['resource_id'] ) : '',
			's'             => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
		);
	}

	/**
	 * @return array<string, string>
	 */
	private function eventTypeOptions(): array {
		return array(
			EventType::POST_CREATED        => __( 'Post created', 'wp-log-watch' ),
			EventType::POST_UPDATED        => __( 'Post updated', 'wp-log-watch' ),
			EventType::POST_PUBLISHED      => __( 'Post published', 'wp-log-watch' ),
			EventType::POST_DRAFTED        => __( 'Post unpublished', 'wp-log-watch' ),
			EventType::POST_TRASHED        => __( 'Post trashed', 'wp-log-watch' ),
			EventType::POST_RESTORED       => __( 'Post restored', 'wp-log-watch' ),
			EventType::POST_DELETED        => __( 'Post deleted', 'wp-log-watch' ),
			EventType::POST_META_ADDED     => __( 'Post meta added', 'wp-log-watch' ),
			EventType::POST_META_UPDATED   => __( 'Post meta updated', 'wp-log-watch' ),
			EventType::POST_META_DELETED   => __( 'Post meta deleted', 'wp-log-watch' ),
			EventType::TERM_CREATED        => __( 'Term created', 'wp-log-watch' ),
			EventType::TERM_UPDATED        => __( 'Term updated', 'wp-log-watch' ),
			EventType::TERM_DELETED        => __( 'Term deleted', 'wp-log-watch' ),
			EventType::USER_CREATED        => __( 'User created', 'wp-log-watch' ),
			EventType::USER_UPDATED        => __( 'User updated', 'wp-log-watch' ),
			EventType::USER_DELETED        => __( 'User deleted', 'wp-log-watch' ),
			EventType::USER_LOGIN          => __( 'User login', 'wp-log-watch' ),
			EventType::USER_LOGOUT         => __( 'User logout', 'wp-log-watch' ),
			EventType::USER_LOGIN_FAILED   => __( 'Login failed', 'wp-log-watch' ),
			EventType::USER_ROLE_CHANGED   => __( 'Role changed', 'wp-log-watch' ),
			EventType::PLUGIN_ACTIVATED    => __( 'Plugin activated', 'wp-log-watch' ),
			EventType::PLUGIN_DEACTIVATED  => __( 'Plugin deactivated', 'wp-log-watch' ),
			EventType::PLUGIN_INSTALLED    => __( 'Plugin installed', 'wp-log-watch' ),
			EventType::PLUGIN_UPDATED      => __( 'Plugin updated', 'wp-log-watch' ),
			EventType::PLUGIN_DELETED      => __( 'Plugin deleted', 'wp-log-watch' ),
			EventType::THEME_ACTIVATED     => __( 'Theme activated', 'wp-log-watch' ),
			EventType::THEME_INSTALLED     => __( 'Theme installed', 'wp-log-watch' ),
			EventType::THEME_UPDATED       => __( 'Theme updated', 'wp-log-watch' ),
			EventType::SYSTEM_CORE_UPDATED => __( 'WordPress updated', 'wp-log-watch' ),
		);
	}

	/**
	 * @return array<string, string>
	 */
	private function resourceTypeOptions(): array {
		return array(
			ResourceType::POST   => __( 'Post', 'wp-log-watch' ),
			ResourceType::PAGE   => __( 'Page', 'wp-log-watch' ),
			ResourceType::USER   => __( 'User', 'wp-log-watch' ),
			ResourceType::TERM   => __( 'Term', 'wp-log-watch' ),
			ResourceType::PLUGIN => __( 'Plugin', 'wp-log-watch' ),
			ResourceType::THEME  => __( 'Theme', 'wp-log-watch' ),
			ResourceType::SITE   => __( 'Site', 'wp-log-watch' ),
			ResourceType::SYSTEM => __( 'System', 'wp-log-watch' ),
		);
	}
}
