<?php

declare(strict_types=1);

namespace WPLogWatch\Domain\Queries;

use WPLogWatch\Domain\Entities\AuditLog;

/**
 * A page of audit log results together with the total number of matches.
 */
final class AuditLogSearchResult {

	/**
	 * @param AuditLog[] $items
	 */
	public function __construct(
		private readonly array $items,
		private readonly int $total,
		private readonly int $page,
		private readonly int $perPage,
	) {
	}

	/**
	 * @return AuditLog[]
	 */
	public function items(): array {
		return $this->items;
	}

	public function total(): int {
		return $this->total;
	}

	public function page(): int {
		return $this->page;
	}

	public function perPage(): int {
		return $this->perPage;
	}

	public function totalPages(): int {
		return $this->perPage > 0 ? (int) ceil( $this->total / $this->perPage ) : 0;
	}

	public function hasPrevious(): bool {
		return $this->page > 1;
	}

	public function hasNext(): bool {
		return $this->page < $this->totalPages();
	}
}
