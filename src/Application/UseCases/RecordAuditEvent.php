<?php

declare(strict_types=1);

namespace WPLogWatch\Application\UseCases;

use WPLogWatch\Application\Contracts\ClockInterface;
use WPLogWatch\Application\DTOs\RecordAuditEventInput;
use WPLogWatch\Application\Services\SensitiveDataFilter;
use WPLogWatch\Domain\Contracts\AuditLogRepositoryInterface;
use WPLogWatch\Domain\Entities\AuditLog;

/**
 * Records a single audit event.
 */
final class RecordAuditEvent {

	public function __construct(
		private readonly AuditLogRepositoryInterface $repository,
		private readonly ClockInterface $clock,
		private readonly SensitiveDataFilter $sensitiveDataFilter,
	) {
	}

	public function execute( RecordAuditEventInput $input ): AuditLog {
		$metadata               = $input->metadata;
		$metadata['actor_type'] = $input->actor->type()->value;
		$metadata               = $this->sensitiveDataFilter->filter( $metadata );

		$log = AuditLog::create(
			eventType: $input->eventType,
			actor: $input->actor,
			resourceType: $input->resourceType,
			resourceId: $input->resourceId,
			description: $input->description,
			metadata: $metadata,
			ipAddress: $input->ipAddress,
			userAgent: $input->userAgent,
			createdAt: $this->clock->now(),
		);

		$this->repository->save( $log );

		return $log;
	}
}
