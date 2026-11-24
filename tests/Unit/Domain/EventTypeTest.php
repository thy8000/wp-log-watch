<?php

declare(strict_types=1);

namespace WPLogWatch\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use WPLogWatch\Domain\Exceptions\InvalidEventTypeException;
use WPLogWatch\Domain\ValueObjects\EventType;

final class EventTypeTest extends TestCase
{
    public function testAcceptsValidDottedFormat(): void
    {
        $type = EventType::fromString('post.created');

        self::assertSame('post.created', $type->value());
        self::assertSame('post.created', (string) $type);
    }

    public function testAcceptsUnderscoresAndDigits(): void
    {
        self::assertSame('system.core_updated', EventType::fromString('system.core_updated')->value());
    }

    public function testRejectsMissingDot(): void
    {
        $this->expectException(InvalidEventTypeException::class);

        EventType::fromString('login');
    }

    public function testRejectsUppercase(): void
    {
        $this->expectException(InvalidEventTypeException::class);

        EventType::fromString('Post.Created');
    }

    public function testRejectsEmptyString(): void
    {
        $this->expectException(InvalidEventTypeException::class);

        EventType::fromString('');
    }

    public function testEquals(): void
    {
        self::assertTrue(EventType::fromString('post.created')->equals(EventType::fromString('post.created')));
        self::assertFalse(EventType::fromString('post.created')->equals(EventType::fromString('post.updated')));
    }
}
