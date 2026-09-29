<?php

namespace WPTrace\Application\Audit;

use WPTrace\Application\DTO\RecordAuditEventInput;
use WPTrace\Domain\AuditEvent\AuditEvent;
use WPTrace\Domain\AuditEvent\AuditEventRepository;

final class RecordAuditEvent
{
    private AuditEventRepository $Repository;

    public function __construct(AuditEventRepository $Repository) {
        $this->Repository = $Repository;
    }

    public function execute(RecordAuditEventInput $Input) {
        $event = AuditEvent::create(
            action: $Input->action,
            actorID: $Input->actorID,
            objectType: $Input->objectType,
            objectID: $Input->objectID,
            context: $Input->context,
        );

        $this->Repository->save($event);
    }
}