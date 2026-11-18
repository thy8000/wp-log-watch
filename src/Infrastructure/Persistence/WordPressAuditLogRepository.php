<?php

declare(strict_types=1);

namespace WPLogWatch\Infrastructure\Persistence;

use DateTimeImmutable;
use DateTimeZone;
use WPLogWatch\Domain\Contracts\AuditLogRepositoryInterface;
use WPLogWatch\Domain\Entities\Actor;
use WPLogWatch\Domain\Entities\AuditLog;
use WPLogWatch\Domain\Queries\AuditLogCriteria;
use WPLogWatch\Domain\Queries\AuditLogSearchResult;
use WPLogWatch\Domain\ValueObjects\ActorType;
use WPLogWatch\Domain\ValueObjects\EventType;
use WPLogWatch\Domain\ValueObjects\ResourceType;
use WPLogWatch\Infrastructure\Persistence\Database\AuditTableSchema;

/**
 * MySQL adapter for the audit log repository, backed by $wpdb.
 *
 * Every dynamic value flows through $wpdb->prepare(); identifiers are limited
 * to the hardcoded table name and an allowlisted order-by column.
 */
final class WordPressAuditLogRepository implements AuditLogRepositoryInterface {

	// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The table name is built from $wpdb->prefix plus a hardcoded suffix, and every dynamic value is bound via $wpdb->prepare().

	public function save( AuditLog $log ): void {
		global $wpdb;

		$wpdb->insert(
			AuditTableSchema::tableName(),
			$this->toDatabaseRow( $log ),
			$this->formats()
		);
	}

	public function findById( int $id ): ?AuditLog {
		global $wpdb;

		$sql = $wpdb->prepare(
			'SELECT * FROM ' . AuditTableSchema::tableName() . ' WHERE id = %d LIMIT 1',
			$id
		);

		$row = $wpdb->get_row( $sql );

		return $row instanceof \stdClass ? $this->mapRow( $row ) : null;
	}

	public function findByUuid( string $uuid ): ?AuditLog {
		global $wpdb;

		$sql = $wpdb->prepare(
			'SELECT * FROM ' . AuditTableSchema::tableName() . ' WHERE event_uuid = %s LIMIT 1',
			$uuid
		);

		$row = $wpdb->get_row( $sql );

		return $row instanceof \stdClass ? $this->mapRow( $row ) : null;
	}

	public function search( AuditLogCriteria $criteria ): AuditLogSearchResult {
		global $wpdb;

		$table = AuditTableSchema::tableName();

		[$where, $args] = $this->buildWhere( $criteria );
		$whereSql       = $where === '' ? '' : 'WHERE ' . $where;

		$countSql = "SELECT COUNT(*) FROM {$table} {$whereSql}";
		$total    = (int) $wpdb->get_var( $wpdb->prepare( $countSql, ...$args ) );

		$orderBy = $criteria->orderBy();
		$order   = $criteria->orderDirection();
		$limit   = $criteria->perPage();
		$offset  = $criteria->offset();

		$dataSql  = "SELECT * FROM {$table} {$whereSql} ORDER BY {$orderBy} {$order} LIMIT %d OFFSET %d";
		$dataArgs = array_merge( $args, array( $limit, $offset ) );

		$rows = $wpdb->get_results( $wpdb->prepare( $dataSql, ...$dataArgs ) );

		$items = array();
		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$items[] = $this->mapRow( $row );
			}
		}

		return new AuditLogSearchResult( $items, $total, $criteria->page(), $criteria->perPage() );
	}

	public function deleteOlderThan( DateTimeImmutable $cutoff, int $batchSize = 1000 ): int {
		global $wpdb;

		$table     = AuditTableSchema::tableName();
		$batchSize = max( 1, $batchSize );
		$cutoffStr = $cutoff->format( 'Y-m-d H:i:s' );
		$deleted   = 0;

		while ( true ) {
			$sql = $wpdb->prepare(
				"DELETE FROM {$table} WHERE created_at < %s LIMIT %d",
				$cutoffStr,
				$batchSize
			);

			$result = $wpdb->query( $sql );

			if ( $result === false || $result === 0 ) {
				break;
			}

			$deleted += $result;

			if ( $result < $batchSize ) {
				break;
			}
		}

		return $deleted;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function toDatabaseRow( AuditLog $log ): array {
		return array(
			'event_uuid'    => $log->eventUuid(),
			'actor_id'      => $log->actor()->id(),
			'event_type'    => $log->eventType()->value(),
			'resource_type' => $log->resourceType()->value(),
			'resource_id'   => $log->resourceId(),
			'description'   => $log->description(),
			'metadata'      => wp_json_encode( $log->metadata() ),
			'ip_address'    => $log->ipAddress(),
			'user_agent'    => $log->userAgent(),
			'created_at'    => $log->createdAt()->format( 'Y-m-d H:i:s' ),
		);
	}

	/**
	 * @return string[]
	 */
	private function formats(): array {
		return array( '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' );
	}

	/**
	 * @return array{0: string, 1: array<int, mixed>}
	 */
	private function buildWhere( AuditLogCriteria $criteria ): array {
		global $wpdb;

		$conditions = array();
		$args       = array();

		if ( $criteria->dateFrom() !== null ) {
			$conditions[] = 'created_at >= %s';
			$args[]       = $criteria->dateFrom()->format( 'Y-m-d H:i:s' );
		}

		if ( $criteria->dateTo() !== null ) {
			$conditions[] = 'created_at <= %s';
			$args[]       = $criteria->dateTo()->format( 'Y-m-d H:i:s' );
		}

		if ( $criteria->actorId() !== null ) {
			$conditions[] = 'actor_id = %d';
			$args[]       = $criteria->actorId();
		}

		if ( $criteria->eventType() !== null ) {
			$conditions[] = 'event_type = %s';
			$args[]       = $criteria->eventType()->value();
		}

		if ( $criteria->resourceType() !== null ) {
			$conditions[] = 'resource_type = %s';
			$args[]       = $criteria->resourceType()->value();
		}

		if ( $criteria->resourceId() !== null ) {
			$conditions[] = 'resource_id = %d';
			$args[]       = $criteria->resourceId();
		}

		$search = $criteria->search();
		if ( $search !== null && $search !== '' ) {
			$like         = '%' . $wpdb->esc_like( $search ) . '%';
			$conditions[] = '(description LIKE %s OR event_type LIKE %s OR resource_type LIKE %s)';
			$args[]       = $like;
			$args[]       = $like;
			$args[]       = $like;
		}

		return array( implode( ' AND ', $conditions ), $args );
	}

	private function mapRow( \stdClass $row ): AuditLog {
		$metadata  = $this->decodeMetadata( $row->metadata );
		$actorType = $this->parseActorType( $metadata['actor_type'] ?? null );
		$actorId   = $row->actor_id !== null ? (int) $row->actor_id : null;

		return AuditLog::reconstitute(
			id: (int) $row->id,
			eventUuid: (string) $row->event_uuid,
			eventType: EventType::fromString( (string) $row->event_type ),
			actor: Actor::fromTypeAndId( $actorType, $actorId ),
			resourceType: ResourceType::fromString( (string) ( $row->resource_type ?? ResourceType::SYSTEM ) ),
			resourceId: $row->resource_id !== null ? (int) $row->resource_id : null,
			description: (string) $row->description,
			metadata: $metadata,
			ipAddress: $row->ip_address !== null ? (string) $row->ip_address : null,
			userAgent: $row->user_agent !== null ? (string) $row->user_agent : null,
			createdAt: new DateTimeImmutable( (string) $row->created_at, new DateTimeZone( 'UTC' ) ),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function decodeMetadata( ?string $json ): array {
		if ( $json === null || $json === '' ) {
			return array();
		}

		$decoded = json_decode( $json, true );

		return is_array( $decoded ) ? $decoded : array();
	}

	private function parseActorType( ?string $value ): ActorType {
		$cases = ActorType::cases();

		foreach ( $cases as $case ) {
			if ( $case->value === $value ) {
				return $case;
			}
		}

		return ActorType::User;
	}

	// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}
