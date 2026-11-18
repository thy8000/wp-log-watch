<?php

declare(strict_types=1);

namespace WPLogWatch\Infrastructure\WordPress\Hooks;

use Throwable;
use WPLogWatch\Application\DTOs\RecordAuditEventInput;
use WPLogWatch\Application\UseCases\RecordAuditEvent;
use WPLogWatch\Domain\Entities\Actor;
use WPLogWatch\Domain\ValueObjects\EventType;
use WPLogWatch\Domain\ValueObjects\ResourceType;
use WPLogWatch\Infrastructure\Logging\ErrorLogger;
use WPLogWatch\Infrastructure\WordPress\RequestContext;
use WPLogWatch\Infrastructure\WordPress\WordPressUserProvider;

/**
 * Base class for WordPress hook adapters: turns raw WordPress hooks into domain
 * events through the RecordAuditEvent use case.
 */
abstract class AbstractHook {

	public function __construct(
		protected readonly RecordAuditEvent $recordAuditEvent,
		protected readonly WordPressUserProvider $userProvider,
		protected readonly RequestContext $requestContext,
		protected readonly ErrorLogger $errorLogger,
	) {
	}

	abstract public function register(): void;

	/**
	 * @param array<string, mixed> $metadata
	 */
	protected function record(
		EventType $eventType,
		ResourceType $resourceType,
		?int $resourceId,
		string $description,
		array $metadata = array(),
		?Actor $actor = null,
	): void {
		try {
			$metadata['context'] = $this->requestContext->context();

			$this->recordAuditEvent->execute(
				new RecordAuditEventInput(
					eventType: $eventType,
					actor: $actor ?? $this->userProvider->currentActor(),
					resourceType: $resourceType,
					resourceId: $resourceId,
					description: $description,
					metadata: $metadata,
					ipAddress: $this->requestContext->ipAddress(),
					userAgent: $this->requestContext->userAgent(),
				)
			);
		} catch ( Throwable $throwable ) {
			// Observing must never break the request being observed.
			$this->errorLogger->error( $throwable->getMessage() );
		}
	}
}
