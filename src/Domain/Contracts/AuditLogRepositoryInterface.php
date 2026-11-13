<?php

declare(strict_types=1);

namespace WPLogWatch\Domain\Contracts;

use DateTimeImmutable;
use WPLogWatch\Domain\Entities\AuditLog;
use WPLogWatch\Domain\Queries\AuditLogCriteria;
use WPLogWatch\Domain\Queries\AuditLogSearchResult;

/**
 * Persistence port for audit logs. The domain knows nothing about the storage
 * technology (MySQL, a remote API, an append-only file…).
 */
interface AuditLogRepositoryInterface {

	public function save( AuditLog $log ): void;

	public function findById( int $id ): ?AuditLog;

	public function findByUuid( string $uuid ): ?AuditLog;

	public function search( AuditLogCriteria $criteria ): AuditLogSearchResult;

	public function deleteOlderThan( DateTimeImmutable $cutoff, int $batchSize = 1000 ): int;
}
