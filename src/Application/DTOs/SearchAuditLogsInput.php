<?php

declare(strict_types=1);

namespace WPLogWatch\Application\DTOs;

use DateTimeImmutable;
use WPLogWatch\Domain\Queries\AuditLogCriteria;
use WPLogWatch\Domain\ValueObjects\EventType;
use WPLogWatch\Domain\ValueObjects\ResourceType;

/**
 * Input for the "search audit logs" use case.
 */
final class SearchAuditLogsInput {

	public function __construct(
		public readonly ?DateTimeImmutable $dateFrom = null,
		public readonly ?DateTimeImmutable $dateTo = null,
		public readonly ?int $actorId = null,
		public readonly ?EventType $eventType = null,
		public readonly ?ResourceType $resourceType = null,
		public readonly ?int $resourceId = null,
		public readonly ?string $search = null,
		public readonly int $page = 1,
		public readonly int $perPage = 50,
		public readonly string $orderBy = 'created_at',
		public readonly string $order = 'DESC',
	) {
	}

	public function toCriteria(): AuditLogCriteria {
		return new AuditLogCriteria(
			dateFrom: $this->dateFrom,
			dateTo: $this->dateTo,
			actorId: $this->actorId,
			eventType: $this->eventType,
			resourceType: $this->resourceType,
			resourceId: $this->resourceId,
			search: $this->search,
			page: $this->page,
			perPage: $this->perPage,
			orderBy: $this->orderBy,
			order: $this->order,
		);
	}
}
