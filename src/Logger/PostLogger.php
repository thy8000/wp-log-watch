<?php

namespace WPTrace\Logger;

if (!defined('ABSPATH')) {
    exit;
}

final class PostLogger
{
    public function save($post_ID)
    {
        $event_data = [
            'event_type'      => 'post.updated',
            'action'          => 'update',
            'actor_user_id'   => 123,
            'actor_login'     => 'joao',
            'resource_type'   => 'post',
            'resource_id'     => 456,
            'resource_name'   => 'Meu artigo',
            'context'         => 'wp-admin',
            'ip_address'      => '192.0.2.10',
            'user_agent'      => 'Mozilla/5.0...',
            'request_id'      => '...',
            'occurred_at'     => '2026-10-06 23:10:00',
            'changes'         => [
                'post_status' => [
                    'old' => 'draft',
                    'new' => 'publish',
                ],
            ],
        ];
    }
}
