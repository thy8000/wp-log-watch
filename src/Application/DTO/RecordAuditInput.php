<?php

namespace WPTrace\Application\DTO;

use WPTrace\Domain\Actor\Actor;
use WPTrace\Domain\AuditContext\AuditContext;
use WPTrace\Domain\AuditEvent\AuditAction;
use WPTrace\Domain\AuditObject\AuditObject;

final readonly class RecordAuditEventInput{
    public function __construct(
        public AuditAction $action,
        public Actor $actor,
        public AuditObject $object,
        public AuditContext $context,
        public array $metadata = [],
    ) {

    }
}