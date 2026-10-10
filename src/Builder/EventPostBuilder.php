<?php

namespace WPTrace\Builder;

if (!defined("ABSPATH")) {
    exit;
}

use WPTrace\Builder\EventBuilder;

class EventPostBuilder extends EventBuilder
{
    private $event_data = [
        'save' => [
            'event'  => 'post.saved',
            'action' => 'save',
        ],
    ];

    public function set_saved_data()
    {
        $event_data = [
            'event_type'      => $this->get_event_type(),
            'action'          => $this->get_action(),
            'actor_user_id'   => $this->get_user_ID(),
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

    private function get_event_type($type = 'save') {
        return $this->event_data[$type]['event'];
    }

    private function get_action($type = 'save') {
        return $this->event_data[$type]['action'];
    }
}
