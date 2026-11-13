<?php

declare(strict_types=1);

namespace WPLogWatch\Tests\Unit\Application;

use PHPUnit\Framework\TestCase;
use WPLogWatch\Application\DTOs\RecordAuditEventInput;
use WPLogWatch\Application\Services\SensitiveDataFilter;
use WPLogWatch\Application\UseCases\RecordAuditEvent;
use WPLogWatch\Domain\Entities\Actor;
use WPLogWatch\Domain\ValueObjects\EventType;
use WPLogWatch\Domain\ValueObjects\ResourceType;
use WPLogWatch\Tests\Fixtures\FixedClock;
use WPLogWatch\Tests\Fixtures\InMemoryAuditLogRepository;

final class RecordAuditEventTest extends TestCase
{
    public function testPersistsEventWithActorTypeAndContext(): void
    {
        $repository = new InMemoryAuditLogRepository();
        $useCase = new RecordAuditEvent($repository, new FixedClock(), new SensitiveDataFilter());

        $log = $useCase->execute(new RecordAuditEventInput(
            eventType: EventType::fromString(EventType::POST_CREATED),
            actor: Actor::user(15),
            resourceType: ResourceType::fromString(ResourceType::POST),
            resourceId: 320,
            description: 'Post "Hello" created.',
            metadata: ['context' => 'wp-admin'],
            ipAddress: '192.0.2.10',
            userAgent: 'Mozilla/5.0',
        ));

        self::assertCount(1, $repository->saved);
        self::assertSame('post.created', $log->eventType()->value());
        self::assertSame('user', $log->metadata()['actor_type']);
        self::assertSame('wp-admin', $log->metadata()['context']);
        self::assertSame('2026-01-01 12:00:00', $log->createdAt()->format('Y-m-d H:i:s'));
    }

    public function testRedactsSensitiveMetadataBeforeSave(): void
    {
        $repository = new InMemoryAuditLogRepository();
        $useCase = new RecordAuditEvent($repository, new FixedClock(), new SensitiveDataFilter());

        $useCase->execute(new RecordAuditEventInput(
            eventType: EventType::fromString(EventType::USER_CREATED),
            actor: Actor::user(1),
            resourceType: ResourceType::fromString(ResourceType::USER),
            resourceId: 2,
            description: 'User created.',
            metadata: ['password' => 'hunter2'],
        ));

        self::assertSame('[redacted]', $repository->saved[0]->metadata()['password']);
    }
}
