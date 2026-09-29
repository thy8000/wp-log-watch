<?php

namespace WPTrace\Domain\AuditEvent;

use InvalidArgumentException;

final readonly class AuditEventID {
    public function __construct(
        public int $value
    ) {
        if($value <= 0) {
            throw new InvalidArgumentException(
                'Audit event ID must be greater than zero.'
            );
        }
    }
}