<?php

declare(strict_types=1);

namespace WPLogWatch\Application\DTOs;

use WPLogWatch\Domain\Queries\AuditLogCriteria;

/**
 * Input for the "export audit logs" use case.
 */
final class ExportAuditLogsInput {

	public const FORMAT_CSV = 'csv';

	public const FORMAT_JSON = 'json';

	public function __construct(
		public readonly AuditLogCriteria $criteria,
		public readonly string $format = self::FORMAT_CSV,
	) {
	}
}
