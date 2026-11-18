<?php

declare(strict_types=1);

namespace WPLogWatch\Application\UseCases;

use WPLogWatch\Application\Contracts\ClockInterface;
use WPLogWatch\Domain\Contracts\AuditLogRepositoryInterface;

/**
 * Deletes audit log entries older than a retention window.
 */
final class DeleteExpiredAuditLogs {

	public function __construct(
		private readonly AuditLogRepositoryInterface $repository,
		private readonly ClockInterface $clock,
	) {
	}

	public function execute( int $retentionDays ): int {
		if ( $retentionDays <= 0 ) {
			return 0;
		}

		$cutoff = $this->clock->now()->modify( sprintf( '-%d days', $retentionDays ) );

		return $this->repository->deleteOlderThan( $cutoff );
	}
}
