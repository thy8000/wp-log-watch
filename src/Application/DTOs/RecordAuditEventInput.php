<?php

declare(strict_types=1);

namespace WPLogWatch\Application\DTOs;

use WPLogWatch\Domain\Entities\Actor;
use WPLogWatch\Domain\ValueObjects\EventType;
use WPLogWatch\Domain\ValueObjects\ResourceType;

/**
 * Input for the "record an audit event" use case.
 */
final class RecordAuditEventInput {

	/**
	 * @param array<string, mixed> $metadata
	 */
	public function __construct(
		public readonly EventType $eventType,
		public readonly Actor $actor,
		public readonly ResourceType $resourceType,
		public readonly ?int $resourceId,
		public readonly string $description,
		public readonly array $metadata = array(),
		public readonly ?string $ipAddress = null,
		public readonly ?string $userAgent = null,
	) {
	}
}
