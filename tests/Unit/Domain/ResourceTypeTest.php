<?php

declare(strict_types=1);

namespace WPLogWatch\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use WPLogWatch\Domain\Exceptions\InvalidResourceTypeException;
use WPLogWatch\Domain\ValueObjects\ResourceType;

final class ResourceTypeTest extends TestCase
{
    public function testAcceptsKnownTypes(): void
    {
        self::assertSame('post', ResourceType::fromString('post')->value());
        self::assertSame('user', ResourceType::fromString('user')->value());
    }

    public function testAcceptsCustomPostTypeSlug(): void
    {
        self::assertSame('product', ResourceType::fromString('product')->value());
        self::assertSame('my_custom_type', ResourceType::fromString('my_custom_type')->value());
    }

    public function testRejectsEmptyString(): void
    {
        $this->expectException(InvalidResourceTypeException::class);

        ResourceType::fromString('');
    }

    public function testRejectsUppercaseAndSpaces(): void
    {
        $this->expectException(InvalidResourceTypeException::class);

        ResourceType::fromString('My Type');
    }
}
