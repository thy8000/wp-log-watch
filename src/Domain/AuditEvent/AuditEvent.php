<?php

namespace WPTrace\Domain\AuditEvent;

use DateTimeImmutable;
use InvalidArgumentException;
use WPTrace\Domain\Actor\Actor;
use WPTrace\Domain\AuditObject\AuditObject;
use WPTrace\Domain\AuditContext\AuditContext;

final class AuditEvent {
    public function __construct(
        private readonly AuditEventID $ID,
        private readonly AuditAction $action,
        private readonly Actor $actor,
        private readonly AuditObject $object,
        private readonly AuditContext $context,
        private readonly DateTimeImmutable $createdAt,
        private readonly array $metadata = [],
    ) {}

    public function id(): ?AuditEventID {
        return $this->ID;
    }

    public function action(): AuditAction {
        return $this->action;
    }

    public function actor(): Actor {
        return $this->actor;
    }

    public function object(): AuditObject {
        return $this->object;
    }

    public function context(): AuditContext {
        return $this->context;
    }

    public function createdAt(): DateTimeImmutable {
        return $this->createdAt;
    }

    public function metadata(): array {
        return $this->metadata;
    }
}