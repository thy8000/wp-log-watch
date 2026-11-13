<?php

declare(strict_types=1);

namespace WPLogWatch\Tests\Fixtures;

use DateTimeImmutable;
use DateTimeZone;
use WPLogWatch\Application\Contracts\ClockInterface;

final class FixedClock implements ClockInterface
{
    public function __construct(private readonly string $time = '2026-01-01 12:00:00')
    {
    }

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->time, new DateTimeZone('UTC'));
    }
}
