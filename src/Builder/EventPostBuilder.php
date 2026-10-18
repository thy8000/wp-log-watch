<?php

namespace WPTrace\Builder;

if (!defined("ABSPATH")) {
    exit;
}

use WPTrace\Builder\EventBuilder;

class EventPostBuilder extends EventBuilder
{
    private $Post;

    private $event_data = [
        'create' => [
            'event'  => 'post.created',
            'action' => 'create',
        ],
    ];

    public function __construct($post_ID)
    {
        parent::__construct();
        
        if (!empty($post_ID) && !is_wp_error($post_ID)) {
            $Post = get_post($post_ID);
        }

        if (!empty($Post) && !is_wp_error($Post)) {
            $this->Post = $Post;
        }
    }

    public function set_post_data($event_type = 'create')
    {
        $event_data = [
            'event_type'      => $this->get_event_type($event_type),
            'action'          => $this->get_action($event_type),
            'actor_user_id'   => $this->get_user_ID(),
            'actor_login'     => $this->get_user_login(),
            'resource_type'   => $this->get_resource_type(),
            'resource_id'     => $this->get_resource_ID(),
            'resource_name'   => $this->get_resource_name(),
            'context'         => $this->get_context(),
            'ip_address'      => $this->get_ip_address(),
            'user_agent'      => $this->get_user_agent(),
            'request_id'      => $this->get_request_UUID4(),
            'occurred_at'     => $this->get_occurred_at(),
        ];

        return $event_data;
    }

    public function set_post_data_format($event_type = 'create')
    {
        return [
            '%s',
            '%s',
            '%d',
            '%s',
            '%s',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
            '%s',
        ];
    }

    private function get_event_type($type = 'create')
    {
        return $this->event_data[$type]['event'];
    }

    private function get_action($type = 'create')
    {
        return $this->event_data[$type]['action'];
    }

    private function get_resource_type()
    {
        return 'post';
    }

    private function get_resource_ID()
    {
        return $this->Post->ID;
    }

    private function get_resource_name()
    {
        return $this->Post->post_title;
    }

    private function get_context()
    {
        return 'wp-admin';
    }
}
