<?php

declare(strict_types=1);

namespace WPLogWatch\Tests\Unit\Domain;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WPLogWatch\Domain\Entities\Actor;
use WPLogWatch\Domain\ValueObjects\ActorType;

final class ActorTest extends TestCase
{
    public function testUserActor(): void
    {
        $actor = Actor::user(42);

        self::assertSame(42, $actor->id());
        self::assertTrue($actor->isUser());
        self::assertFalse($actor->isSystem());
    }

    public function testUserActorRejectsNonPositiveId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Actor::user(0);
    }

    public function testSystemActor(): void
    {
        $actor = Actor::system();

        self::assertNull($actor->id());
        self::assertTrue($actor->isSystem());
    }

    public function testSystemActorKeepsType(): void
    {
        $actor = Actor::system(ActorType::Cron);

        self::assertSame(ActorType::Cron, $actor->type());
        self::assertNull($actor->id());
    }

    public function testFromTypeAndIdWinsWithPositiveId(): void
    {
        $actor = Actor::fromTypeAndId(ActorType::Cron, 7);

        self::assertSame(ActorType::User, $actor->type());
        self::assertSame(7, $actor->id());
    }

    public function testFromTypeAndIdWithoutId(): void
    {
        $actor = Actor::fromTypeAndId(ActorType::WpCli, null);

        self::assertSame(ActorType::WpCli, $actor->type());
        self::assertNull($actor->id());
    }
}
