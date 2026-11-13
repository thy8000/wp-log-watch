<?php

declare(strict_types=1);

namespace WPLogWatch\Tests\Unit\Application;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use WPLogWatch\Application\DTOs\RecordAuditEventInput;
use WPLogWatch\Application\DTOs\SearchAuditLogsInput;
use WPLogWatch\Application\UseCases\RecordAuditEvent;
use WPLogWatch\Application\UseCases\SearchAuditLogs;
use WPLogWatch\Application\Services\SensitiveDataFilter;
use WPLogWatch\Domain\Entities\Actor;
use WPLogWatch\Domain\ValueObjects\EventType;
use WPLogWatch\Domain\ValueObjects\ResourceType;
use WPLogWatch\Tests\Fixtures\FixedClock;
use WPLogWatch\Tests\Fixtures\InMemoryAuditLogRepository;

final class SearchAuditLogsTest extends TestCase
{
    public function testFiltersByActorAndEventType(): void
    {
        $repository = new InMemoryAuditLogRepository();
        $record = new RecordAuditEvent($repository, new FixedClock(), new SensitiveDataFilter());

        $record->execute(new RecordAuditEventInput(
            EventType::fromString(EventType::POST_CREATED),
            Actor::user(10),
            ResourceType::fromString(ResourceType::POST),
            1,
            'Post created.',
        ));

        $record->execute(new RecordAuditEventInput(
            EventType::fromString(EventType::USER_LOGIN),
            Actor::user(20),
            ResourceType::fromString(ResourceType::USER),
            20,
            'User logged in.',
        ));

        $search = new SearchAuditLogs($repository);

        $result = $search->execute(new SearchAuditLogsInput(
            actorId: 10,
            eventType: EventType::fromString(EventType::POST_CREATED),
        ));

        self::assertSame(1, $result->total());
        self::assertSame('post.created', $result->items()[0]->eventType()->value());
    }

    public function testPaginates(): void
    {
        $repository = new InMemoryAuditLogRepository();
        $search = new SearchAuditLogs($repository);

        $result = $search->execute(new SearchAuditLogsInput(page: 2, perPage: 10));

        self::assertSame(2, $result->page());
        self::assertSame(10, $result->perPage());
    }

    public function testSearchByDescriptionSubstring(): void
    {
        $repository = new InMemoryAuditLogRepository();
        $record = new RecordAuditEvent($repository, new FixedClock(), new SensitiveDataFilter());

        $record->execute(new RecordAuditEventInput(
            EventType::fromString(EventType::POST_CREATED),
            Actor::user(1),
            ResourceType::fromString(ResourceType::POST),
            1,
            'Post "Privacy Policy" created.',
        ));

        $search = new SearchAuditLogs($repository);
        $result = $search->execute(new SearchAuditLogsInput(search: 'Privacy'));

        self::assertSame(1, $result->total());
    }

    public function testBuildsCriteriaFromDateRange(): void
    {
        $repository = new InMemoryAuditLogRepository();
        $search = new SearchAuditLogs($repository);

        $from = new DateTimeImmutable('2026-01-01 00:00:00', new DateTimeZone('UTC'));
        $to = new DateTimeImmutable('2026-01-31 23:59:59', new DateTimeZone('UTC'));

        $result = $search->execute(new SearchAuditLogsInput(dateFrom: $from, dateTo: $to));

        self::assertSame(0, $result->total());
    }
}
