<?php

declare(strict_types=1);

namespace WPLogWatch\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use WPLogWatch\Domain\Exceptions\InvalidEventUuidException;
use WPLogWatch\Domain\ValueObjects\EventUuid;

final class EventUuidTest extends TestCase
{
    public function testV4GeneratesValidUuid(): void
    {
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            EventUuid::v4()->value()
        );
    }

    public function testV4GeneratesUniqueValues(): void
    {
        self::assertNotSame(EventUuid::v4()->value(), EventUuid::v4()->value());
    }

    public function testFromStringLowercases(): void
    {
        $uuid = 'A1B2C3D4-E5F6-4789-8ABC-DEF012345678';

        self::assertSame(strtolower($uuid), EventUuid::fromString($uuid)->value());
    }

    public function testFromStringRejectsInvalid(): void
    {
        $this->expectException(InvalidEventUuidException::class);

        EventUuid::fromString('nope');
    }
}
