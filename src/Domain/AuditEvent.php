<?php

namespace WPTrace\Domain\AuditEvent;

interface AuditEventRepository
{
    public function save(AuditEvent $Event): void;
}