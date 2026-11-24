<?php

declare(strict_types=1);

namespace WPLogWatch\Tests\Unit\Domain;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use WPLogWatch\Domain\Entities\Actor;
use WPLogWatch\Domain\Entities\AuditLog;
use WPLogWatch\Domain\Exceptions\InvalidAuditLogException;
use WPLogWatch\Domain\Exceptions\InvalidEventUuidException;
use WPLogWatch\Domain\ValueObjects\EventType;
use WPLogWatch\Domain\ValueObjects\ResourceType;

final class AuditLogTest extends TestCase
{
    private function createdAt(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-01-01 12:00:00', new DateTimeZone('UTC'));
    }

    public function testCreateGeneratesValidUuid(): void
    {
        $log = AuditLog::create(
            EventType::fromString(EventType::POST_CREATED),
            Actor::user(1),
            ResourceType::fromString(ResourceType::POST),
            10,
            'Post created.',
            [],
            null,
            null,
            $this->createdAt(),
        );

        self::assertNull($log->id());
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $log->eventUuid()
        );
    }

    public function testReconstituteRejectsInvalidUuid(): void
    {
        $this->expectException(InvalidEventUuidException::class);

        AuditLog::reconstitute(
            1,
            'not-a-uuid',
            EventType::fromString(EventType::POST_CREATED),
            Actor::user(1),
            ResourceType::fromString(ResourceType::POST),
            10,
            'Post created.',
            [],
            null,
            null,
            $this->createdAt(),
        );
    }

    public function testRejectsNonPositiveResourceId(): void
    {
        $this->expectException(InvalidAuditLogException::class);

        AuditLog::create(
            EventType::fromString(EventType::POST_CREATED),
            Actor::user(1),
            ResourceType::fromString(ResourceType::POST),
            0,
            'Post created.',
            [],
            null,
            null,
            $this->createdAt(),
        );
    }
}
