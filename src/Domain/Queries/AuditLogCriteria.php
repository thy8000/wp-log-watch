<?php

declare(strict_types=1);

namespace WPLogWatch\Domain\Queries;

use DateTimeImmutable;
use WPLogWatch\Domain\Exceptions\InvalidAuditLogException;
use WPLogWatch\Domain\ValueObjects\EventType;
use WPLogWatch\Domain\ValueObjects\ResourceType;

/**
 * Immutable, validated filter/pagination criteria for querying audit logs.
 */
final class AuditLogCriteria {

	private const ALLOWED_ORDER_BY = array(
		'created_at',
		'id',
		'event_type',
		'actor_id',
		'resource_type',
		'resource_id',
	);

	private const ALLOWED_ORDER = array( 'ASC', 'DESC' );

	private const DEFAULT_PER_PAGE = 50;

	private const MAX_PER_PAGE = 100;

	public function __construct(
		private readonly ?DateTimeImmutable $dateFrom = null,
		private readonly ?DateTimeImmutable $dateTo = null,
		private readonly ?int $actorId = null,
		private readonly ?EventType $eventType = null,
		private readonly ?ResourceType $resourceType = null,
		private readonly ?int $resourceId = null,
		private readonly ?string $search = null,
		private readonly int $page = 1,
		private readonly int $perPage = self::DEFAULT_PER_PAGE,
		private readonly string $orderBy = 'created_at',
		private readonly string $order = 'DESC',
	) {
		if ( $this->page < 1 ) {
			throw new InvalidAuditLogException( 'page must be >= 1.' );
		}

		if ( $this->perPage < 1 || $this->perPage > self::MAX_PER_PAGE ) {
			throw new InvalidAuditLogException(
				sprintf( 'perPage must be between 1 and %d.', self::MAX_PER_PAGE )
			);
		}

		if ( ! in_array( $this->orderBy, self::ALLOWED_ORDER_BY, true ) ) {
			throw new InvalidAuditLogException(
				sprintf( 'Invalid orderBy "%s".', $this->orderBy )
			);
		}

		if ( ! in_array( strtoupper( $this->order ), self::ALLOWED_ORDER, true ) ) {
			throw new InvalidAuditLogException(
				sprintf( 'Invalid order "%s".', $this->order )
			);
		}

		if ( $this->search !== null && mb_strlen( $this->search ) > 255 ) {
			throw new InvalidAuditLogException( 'search must be at most 255 characters.' );
		}

		if ( $this->actorId !== null && $this->actorId <= 0 ) {
			throw new InvalidAuditLogException( 'actorId must be positive or null.' );
		}

		if ( $this->resourceId !== null && $this->resourceId <= 0 ) {
			throw new InvalidAuditLogException( 'resourceId must be positive or null.' );
		}
	}

	public function dateFrom(): ?DateTimeImmutable {
		return $this->dateFrom;
	}

	public function dateTo(): ?DateTimeImmutable {
		return $this->dateTo;
	}

	public function actorId(): ?int {
		return $this->actorId;
	}

	public function eventType(): ?EventType {
		return $this->eventType;
	}

	public function resourceType(): ?ResourceType {
		return $this->resourceType;
	}

	public function resourceId(): ?int {
		return $this->resourceId;
	}

	public function search(): ?string {
		return $this->search;
	}

	public function page(): int {
		return $this->page;
	}

	public function perPage(): int {
		return $this->perPage;
	}

	public function orderBy(): string {
		return $this->orderBy;
	}

	public function orderDirection(): string {
		return strtoupper( $this->order );
	}

	public function offset(): int {
		return ( $this->page - 1 ) * $this->perPage;
	}

	public function hasFilters(): bool {
		return $this->dateFrom !== null
			|| $this->dateTo !== null
			|| $this->actorId !== null
			|| $this->eventType !== null
			|| $this->resourceType !== null
			|| $this->resourceId !== null
			|| ( $this->search !== null && $this->search !== '' );
	}
}
