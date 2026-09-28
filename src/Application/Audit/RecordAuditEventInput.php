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
            action: $input->action,
            actorID: $input->actorID,
            objectType: $input->objectType,
            objectID: $input->objectID,
            context: $input->context,
        );

        $this->Repository->save($event);
    }
}