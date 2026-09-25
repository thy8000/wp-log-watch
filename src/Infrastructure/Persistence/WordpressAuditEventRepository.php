<?php

//TODO: CRIAR AuditEvent

namespace WPTrace\Infrastructure\Persistence;

use WPTrace\Domain\AuditEvent\AuditEvent;
use WPTrace\Domain\AuditEvent\AuditEventRepository;

final class WordpressAuditEventRepository implements AuditEventRepository
{
    public function save(AuditEvent $Event): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp_trace_events';

        $wpdb->insert(
            $table_name, [
                'action' => $Event->action()->value,
                'actor_type' => $Event->action()->type,
                'actor_ID' => $Event->actor->id(),
                'object_type' => $Event->object->type(),
                'object_ID' => $Event->object->id(),
                'context' => $Event->context()->value,
                'created_at' => $Event->createdAt()->value,
            ]
        );
    }
}