<?php

declare(strict_types=1);

namespace WPLogWatch\Tests\Fixtures;

use DateTimeImmutable;
use WPLogWatch\Domain\Contracts\AuditLogRepositoryInterface;
use WPLogWatch\Domain\Entities\AuditLog;
use WPLogWatch\Domain\Queries\AuditLogCriteria;
use WPLogWatch\Domain\Queries\AuditLogSearchResult;

/**
 * In-memory repository used by application tests. It lets assertions inspect
 * what was persisted without touching MySQL or WordPress.
 */
final class InMemoryAuditLogRepository implements AuditLogRepositoryInterface
{
    /**
     * @var AuditLog[]
     */
    public array $saved = [];

    /**
     * @var DateTimeImmutable[]
     */
    public array $deleteCutoffs = [];

    public function save(AuditLog $log): void
    {
        $this->saved[] = $log;
    }

    public function findById(int $id): ?AuditLog
    {
        foreach ($this->saved as $log) {
            if ($log->id() === $id) {
                return $log;
            }
        }

        return null;
    }

    public function findByUuid(string $uuid): ?AuditLog
    {
        foreach ($this->saved as $log) {
            if ($log->eventUuid() === $uuid) {
                return $log;
            }
        }

        return null;
    }

    public function search(AuditLogCriteria $criteria): AuditLogSearchResult
    {
        $items = [];

        foreach ($this->saved as $log) {
            if (!$this->matches($log, $criteria)) {
                continue;
            }

            $items[] = $log;
        }

        $total = count($items);
        $offset = $criteria->offset();
        $page = array_slice($items, $offset, $criteria->perPage());

        return new AuditLogSearchResult($page, $total, $criteria->page(), $criteria->perPage());
    }

    public function deleteOlderThan(DateTimeImmutable $cutoff, int $batchSize = 1000): int
    {
        $this->deleteCutoffs[] = $cutoff;

        $kept = [];
        $deleted = 0;

        foreach ($this->saved as $log) {
            if ($log->createdAt() < $cutoff) {
                $deleted++;
            } else {
                $kept[] = $log;
            }
        }

        $this->saved = $kept;

        return $deleted;
    }

    private function matches(AuditLog $log, AuditLogCriteria $criteria): bool
    {
        if ($criteria->actorId() !== null && $log->actor()->id() !== $criteria->actorId()) {
            return false;
        }

        if ($criteria->eventType() !== null && !$log->eventType()->equals($criteria->eventType())) {
            return false;
        }

        if ($criteria->resourceType() !== null && !$log->resourceType()->equals($criteria->resourceType())) {
            return false;
        }

        if ($criteria->resourceId() !== null && $log->resourceId() !== $criteria->resourceId()) {
            return false;
        }

        if ($criteria->dateFrom() !== null && $log->createdAt() < $criteria->dateFrom()) {
            return false;
        }

        if ($criteria->dateTo() !== null && $log->createdAt() > $criteria->dateTo()) {
            return false;
        }

        $search = $criteria->search();
        if ($search !== null && $search !== '' && !str_contains($log->description(), $search)) {
            return false;
        }

        return true;
    }
}
