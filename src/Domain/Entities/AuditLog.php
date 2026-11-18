<?php

declare(strict_types=1);

namespace WPLogWatch\Domain\Entities;

use DateTimeImmutable;
use WPLogWatch\Domain\Exceptions\InvalidAuditLogException;
use WPLogWatch\Domain\ValueObjects\EventType;
use WPLogWatch\Domain\ValueObjects\EventUuid;
use WPLogWatch\Domain\ValueObjects\ResourceType;

/**
 * The core audit entity: an immutable record of "who did what, to which
 * resource, when and with which metadata".
 */
final class AuditLog {

	/**
	 * @param array<string, mixed> $metadata
	 */
	private function __construct(
		private readonly ?int $id,
		private readonly string $eventUuid,
		private readonly EventType $eventType,
		private readonly Actor $actor,
		private readonly ResourceType $resourceType,
		private readonly ?int $resourceId,
		private readonly string $description,
		private readonly array $metadata,
		private readonly ?string $ipAddress,
		private readonly ?string $userAgent,
		private readonly DateTimeImmutable $createdAt,
	) {
		if ( $resourceId !== null && $resourceId <= 0 ) {
			throw new InvalidAuditLogException( 'resourceId must be positive or null.' );
		}
	}

	/**
	 * @param array<string, mixed> $metadata
	 */
	public static function create(
		EventType $eventType,
		Actor $actor,
		ResourceType $resourceType,
		?int $resourceId,
		string $description,
		array $metadata,
		?string $ipAddress,
		?string $userAgent,
		DateTimeImmutable $createdAt,
	): self {
		return new self(
			null,
			EventUuid::v4()->value(),
			$eventType,
			$actor,
			$resourceType,
			$resourceId,
			$description,
			$metadata,
			$ipAddress,
			$userAgent,
			$createdAt,
		);
	}

	/**
	 * @param array<string, mixed> $metadata
	 */
	public static function reconstitute(
		int $id,
		string $eventUuid,
		EventType $eventType,
		Actor $actor,
		ResourceType $resourceType,
		?int $resourceId,
		string $description,
		array $metadata,
		?string $ipAddress,
		?string $userAgent,
		DateTimeImmutable $createdAt,
	): self {
		return new self(
			$id,
			EventUuid::fromString( $eventUuid )->value(),
			$eventType,
			$actor,
			$resourceType,
			$resourceId,
			$description,
			$metadata,
			$ipAddress,
			$userAgent,
			$createdAt,
		);
	}

	public function id(): ?int {
		return $this->id;
	}

	public function eventUuid(): string {
		return $this->eventUuid;
	}

	public function eventType(): EventType {
		return $this->eventType;
	}

	public function actor(): Actor {
		return $this->actor;
	}

	public function resourceType(): ResourceType {
		return $this->resourceType;
	}

	public function resourceId(): ?int {
		return $this->resourceId;
	}

	public function description(): string {
		return $this->description;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function metadata(): array {
		return $this->metadata;
	}

	public function ipAddress(): ?string {
		return $this->ipAddress;
	}

	public function userAgent(): ?string {
		return $this->userAgent;
	}

	public function createdAt(): DateTimeImmutable {
		return $this->createdAt;
	}
}
