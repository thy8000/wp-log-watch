<?php

declare(strict_types=1);

namespace WPLogWatch\Tests\Unit\Application;

use PHPUnit\Framework\TestCase;
use WPLogWatch\Application\Services\SensitiveDataFilter;

final class SensitiveDataFilterTest extends TestCase
{
    private SensitiveDataFilter $filter;

    protected function setUp(): void
    {
        $this->filter = new SensitiveDataFilter();
    }

    public function testMasksSensitiveKeys(): void
    {
        $result = $this->filter->filter([
            'password' => 'secret123',
            'title' => 'Hello',
        ]);

        self::assertSame('[redacted]', $result['password']);
        self::assertSame('Hello', $result['title']);
    }

    public function testMasksSensitiveKeysRecursively(): void
    {
        $result = $this->filter->filter([
            'before' => [
                'api_key' => 'abc',
                'name' => 'John',
            ],
        ]);

        self::assertSame('[redacted]', $result['before']['api_key']);
        self::assertSame('John', $result['before']['name']);
    }

    public function testMasksBySubstring(): void
    {
        $result = $this->filter->filter(['user_token' => 'x']);

        self::assertSame('[redacted]', $result['user_token']);
    }

    public function testLeavesOrdinaryKeysUntouched(): void
    {
        $result = $this->filter->filter(['role' => 'editor', 'slug' => 'my-post']);

        self::assertSame('editor', $result['role']);
        self::assertSame('my-post', $result['slug']);
    }
}
