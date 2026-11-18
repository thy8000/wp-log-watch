<?php

declare(strict_types=1);

namespace WPLogWatch\Application\UseCases;

use WPLogWatch\Domain\Contracts\AuditLogRepositoryInterface;
use WPLogWatch\Domain\Entities\AuditLog;

/**
 * Retrieves a single audit log entry.
 */
final class GetAuditLog {

	public function __construct(
		private readonly AuditLogRepositoryInterface $repository,
	) {
	}

	public function execute( int $id ): ?AuditLog {
		if ( $id <= 0 ) {
			return null;
		}

		return $this->repository->findById( $id );
	}
}
