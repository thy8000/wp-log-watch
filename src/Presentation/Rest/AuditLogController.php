<?php

declare(strict_types=1);

namespace WPLogWatch\Presentation\Rest;

use DateTimeImmutable;
use DateTimeZone;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPLogWatch\Application\DTOs\SearchAuditLogsInput;
use WPLogWatch\Application\UseCases\GetAuditLog;
use WPLogWatch\Application\UseCases\SearchAuditLogs;
use WPLogWatch\Domain\Entities\AuditLog;
use WPLogWatch\Domain\ValueObjects\EventType;
use WPLogWatch\Domain\ValueObjects\ResourceType;
use WPLogWatch\Infrastructure\WordPress\Capabilities;

/**
 * Read-only REST API for the audit log.
 */
final class AuditLogController {

	private const NAMESPACE = 'wp-log-watch/v1';

	public function __construct(
		private readonly SearchAuditLogs $searchAuditLogs,
		private readonly GetAuditLog $getAuditLog,
	) {
	}

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
	}

	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/logs',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'getItems' ),
				'permission_callback' => array( $this, 'permission' ),
				'args'                => $this->collectionArgs(),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/logs/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'getItem' ),
				'permission_callback' => array( $this, 'permission' ),
				'args'                => array(
					'id' => array(
						'type'              => 'integer',
						'minimum'           => 1,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	public function permission(): bool {
		return current_user_can( Capabilities::VIEW_LOGS );
	}

	public function getItems( WP_REST_Request $request ): WP_REST_Response {
		$input  = $this->buildInputFromRequest( $request );
		$result = $this->searchAuditLogs->execute( $input );

		$items = array_map( static fn ( AuditLog $log ): array => self::serialize( $log ), $result->items() );

		return rest_ensure_response(
			array(
				'items'       => $items,
				'total'       => $result->total(),
				'page'        => $result->page(),
				'per_page'    => $result->perPage(),
				'total_pages' => $result->totalPages(),
			)
		);
	}

	public function getItem( WP_REST_Request $request ): WP_REST_Response {
		$id  = (int) $request['id'];
		$log = $this->getAuditLog->execute( $id );

		if ( $log === null ) {
			return new WP_REST_Response(
				array(
					'code'    => 'rest_audit_log_not_found',
					'message' => __( 'Audit log entry not found.', 'wp-log-watch' ),
					'data'    => array( 'status' => 404 ),
				),
				404
			);
		}

		return rest_ensure_response( self::serialize( $log ) );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function collectionArgs(): array {
		return array(
			'page'          => array(
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page'      => array(
				'type'              => 'integer',
				'default'           => 50,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
			),
			'date_from'     => array(
				'type'              => 'string',
				'validate_callback' => array( $this, 'validateDate' ),
			),
			'date_to'       => array(
				'type'              => 'string',
				'validate_callback' => array( $this, 'validateDate' ),
			),
			'actor_id'      => array(
				'type'              => 'integer',
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'event_type'    => array(
				'type'              => 'string',
				'validate_callback' => array( $this, 'validateEventType' ),
			),
			'resource_type' => array(
				'type'              => 'string',
				'validate_callback' => array( $this, 'validateResourceType' ),
			),
			'resource_id'   => array(
				'type'              => 'integer',
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'search'        => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'orderby'       => array(
				'type'    => 'string',
				'default' => 'created_at',
				'enum'    => array( 'created_at', 'id', 'event_type', 'actor_id', 'resource_type', 'resource_id' ),
			),
			'order'         => array(
				'type'    => 'string',
				'default' => 'desc',
				'enum'    => array( 'asc', 'desc' ),
			),
		);
	}

	/**
	 * @param mixed $value
	 */
	public function validateDate( $value, WP_REST_Request $request, string $param ): bool {
		if ( ! is_string( $value ) ) {
			return false;
		}

		$parsed = \DateTime::createFromFormat( 'Y-m-d', $value );

		return $parsed !== false && $parsed->format( 'Y-m-d' ) === $value;
	}

	/**
	 * @param mixed $value
	 */
	public function validateEventType( $value, WP_REST_Request $request, string $param ): bool {
		if ( ! is_string( $value ) ) {
			return false;
		}

		try {
			EventType::fromString( $value );

			return true;
		} catch ( \Throwable ) {
			return false;
		}
	}

	/**
	 * @param mixed $value
	 */
	public function validateResourceType( $value, WP_REST_Request $request, string $param ): bool {
		if ( ! is_string( $value ) ) {
			return false;
		}

		try {
			ResourceType::fromString( $value );

			return true;
		} catch ( \Throwable ) {
			return false;
		}
	}

	private function buildInputFromRequest( WP_REST_Request $request ): SearchAuditLogsInput {
		$eventType = null;
		if ( is_string( $request->get_param( 'event_type' ) ) ) {
			$eventType = EventType::fromString( $request->get_param( 'event_type' ) );
		}

		$resourceType = null;
		if ( is_string( $request->get_param( 'resource_type' ) ) ) {
			$resourceType = ResourceType::fromString( $request->get_param( 'resource_type' ) );
		}

		return new SearchAuditLogsInput(
			dateFrom: $this->parseDate( $request->get_param( 'date_from' ), false ),
			dateTo: $this->parseDate( $request->get_param( 'date_to' ), true ),
			actorId: $request->get_param( 'actor_id' ) !== null ? (int) $request->get_param( 'actor_id' ) : null,
			eventType: $eventType,
			resourceType: $resourceType,
			resourceId: $request->get_param( 'resource_id' ) !== null ? (int) $request->get_param( 'resource_id' ) : null,
			search: $request->get_param( 'search' ) !== null ? (string) $request->get_param( 'search' ) : null,
			page: (int) $request->get_param( 'page' ),
			perPage: (int) $request->get_param( 'per_page' ),
			orderBy: (string) $request->get_param( 'orderby' ),
			order: (string) $request->get_param( 'order' ),
		);
	}

	/**
	 * @param mixed $value
	 */
	private function parseDate( $value, bool $endOfDay ): ?DateTimeImmutable {
		if ( ! is_string( $value ) || $value === '' ) {
			return null;
		}

		$time = $endOfDay ? '23:59:59' : '00:00:00';
		$gmt  = get_gmt_from_date( $value . ' ' . $time );

		return new DateTimeImmutable( $gmt, new DateTimeZone( 'UTC' ) );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function serialize( AuditLog $log ): array {
		return array(
			'id'          => $log->id(),
			'event_uuid'  => $log->eventUuid(),
			'event_type'  => $log->eventType()->value(),
			'actor'       => array(
				'type' => $log->actor()->type()->value,
				'id'   => $log->actor()->id(),
			),
			'resource'    => array(
				'type' => $log->resourceType()->value(),
				'id'   => $log->resourceId(),
			),
			'description' => $log->description(),
			'metadata'    => $log->metadata(),
			'ip_address'  => $log->ipAddress(),
			'user_agent'  => $log->userAgent(),
			'created_at'  => $log->createdAt()->format( 'Y-m-d H:i:s' ),
		);
	}
}
