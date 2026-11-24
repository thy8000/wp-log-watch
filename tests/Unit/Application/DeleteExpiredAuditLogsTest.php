<?php

declare(strict_types=1);

namespace WPLogWatch\Tests\Unit\Application;

use PHPUnit\Framework\TestCase;
use WPLogWatch\Application\UseCases\DeleteExpiredAuditLogs;
use WPLogWatch\Tests\Fixtures\FixedClock;
use WPLogWatch\Tests\Fixtures\InMemoryAuditLogRepository;

final class DeleteExpiredAuditLogsTest extends TestCase
{
    public function testDoesNothingWhenRetentionIsForever(): void
    {
        $repository = new InMemoryAuditLogRepository();
        $useCase = new DeleteExpiredAuditLogs($repository, new FixedClock());

        $deleted = $useCase->execute(0);

        self::assertSame(0, $deleted);
        self::assertSame([], $repository->deleteCutoffs);
    }

    public function testDeletesBeforeComputedCutoff(): void
    {
        $repository = new InMemoryAuditLogRepository();
        $useCase = new DeleteExpiredAuditLogs($repository, new FixedClock('2026-01-31 12:00:00'));

        $useCase->execute(30);

        self::assertCount(1, $repository->deleteCutoffs);
        self::assertSame('2026-01-01 12:00:00', $repository->deleteCutoffs[0]->format('Y-m-d H:i:s'));
    }
}
