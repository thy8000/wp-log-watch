<?php

declare(strict_types=1);

namespace WPLogWatch\Application\UseCases;

use WPLogWatch\Application\DTOs\ExportAuditLogsInput;
use WPLogWatch\Application\DTOs\ExportResult;
use WPLogWatch\Domain\Contracts\AuditLogRepositoryInterface;
use WPLogWatch\Domain\Entities\AuditLog;
use WPLogWatch\Domain\Queries\AuditLogCriteria;

/**
 * Exports the audit log as CSV or JSON.
 *
 * Uses the repository's paginated search so the export never loads the whole
 * table into memory at once, up to a defensive maximum.
 */
final class ExportAuditLogs {

	// phpcs:disable WordPress.WP.AlternativeFunctions -- This use case is pure PHP: CSV/JSON are generated in memory (php://temp) and must stay free of WordPress wrappers.

	private const PAGE_SIZE = 100;

	private const MAX_ROWS = 100000;

	public function __construct(
		private readonly AuditLogRepositoryInterface $repository,
	) {
	}

	public function execute( ExportAuditLogsInput $input ): ExportResult {
		$items = $this->collect( $input->criteria );

		return match ( $input->format ) {
			ExportAuditLogsInput::FORMAT_JSON => $this->toJson( $items ),
			default => $this->toCsv( $items ),
		};
	}

	/**
	 * @return AuditLog[]
	 */
	private function collect( AuditLogCriteria $criteria ): array {
		$items = array();
		$page  = 1;

		while ( true ) {
			$pageCriteria = new AuditLogCriteria(
				dateFrom: $criteria->dateFrom(),
				dateTo: $criteria->dateTo(),
				actorId: $criteria->actorId(),
				eventType: $criteria->eventType(),
				resourceType: $criteria->resourceType(),
				resourceId: $criteria->resourceId(),
				search: $criteria->search(),
				page: $page,
				perPage: self::PAGE_SIZE,
				orderBy: $criteria->orderBy(),
				order: $criteria->orderDirection(),
			);

			$result = $this->repository->search( $pageCriteria );

			foreach ( $result->items() as $item ) {
				$items[] = $item;
			}

			if ( ! $result->hasNext() || count( $items ) >= self::MAX_ROWS ) {
				break;
			}

			++$page;
		}

		return $items;
	}

	/**
	 * @param AuditLog[] $items
	 */
	private function toJson( array $items ): ExportResult {
		$records = array_map( static fn ( AuditLog $log ): array => self::toArray( $log ), $items );

		$content = (string) json_encode(
			array(
				'generated_at' => gmdate( 'c' ),
				'count'        => count( $records ),
				'items'        => $records,
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);

		return new ExportResult(
			$content,
			$this->filename( 'json' ),
			'application/json; charset=utf-8',
		);
	}

	/**
	 * @param AuditLog[] $items
	 */
	private function toCsv( array $items ): ExportResult {
		$stream = fopen( 'php://temp', 'r+' );
		if ( $stream === false ) {
			return new ExportResult( '', $this->filename( 'csv' ), 'text/csv; charset=utf-8' );
		}

		fputcsv( $stream, self::header() );

		foreach ( $items as $log ) {
			$row             = self::toArray( $log );
			$row['metadata'] = (string) json_encode( $row['metadata'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			$row['actor']    = (string) json_encode( $row['actor'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			$row['resource'] = (string) json_encode( $row['resource'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			fputcsv( $stream, $row );
		}

		rewind( $stream );
		$content = stream_get_contents( $stream );
		fclose( $stream );

		return new ExportResult(
			$content === false ? '' : $content,
			$this->filename( 'csv' ),
			'text/csv; charset=utf-8',
		);
	}

	// phpcs:enable WordPress.WP.AlternativeFunctions

	/**
	 * @return string[]
	 */
	private static function header(): array {
		return array(
			'id',
			'event_uuid',
			'event_type',
			'actor',
			'resource',
			'description',
			'metadata',
			'ip_address',
			'user_agent',
			'created_at',
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function toArray( AuditLog $log ): array {
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

	private function filename( string $extension ): string {
		return sprintf( 'wp-log-watch-%s.%s', gmdate( 'Y-m-d-His' ), $extension );
	}
}
