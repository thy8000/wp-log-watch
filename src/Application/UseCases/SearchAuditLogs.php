<?php

declare(strict_types=1);

namespace WPLogWatch\Application\UseCases;

use WPLogWatch\Application\DTOs\SearchAuditLogsInput;
use WPLogWatch\Domain\Contracts\AuditLogRepositoryInterface;
use WPLogWatch\Domain\Queries\AuditLogSearchResult;

/**
 * Searches audit logs with filters and pagination.
 */
final class SearchAuditLogs {

	public function __construct(
		private readonly AuditLogRepositoryInterface $repository,
	) {
	}

	public function execute( SearchAuditLogsInput $input ): AuditLogSearchResult {
		return $this->repository->search( $input->toCriteria() );
	}
}
