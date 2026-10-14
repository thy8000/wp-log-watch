<?php

namespace WPTrace\Builder;

if (!defined("ABSPATH")) {
    exit;
}

use Override;
use WPTrace\Builder\EventBuilder;

class EventPostBuilder extends EventBuilder
{
    private $Post;

    public function __construct($post_ID)
    {
        if(!empty($post_ID) && !is_wp_error($post_ID)) {
            $Post = get_post($post_ID);
        } 

        if(!empty($Post) && !is_wp_error($Post)) {
            $this->Post = $Post;
        }
    }

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
            'actor_login'     => $this->get_user_login(),
            'resource_type'   => $this->get_resource_type(),
            'resource_id'     => $this->get_resource_ID(),
            'resource_name'   => $this->get_resource_name(),
            'context'         => $this->get_context(),
            'ip_address'      => $this->get_ip_address(),
            'user_agent'      => $this->get_user_agent(),
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

    private function get_post() {

    }

    private function get_event_type($type = 'save') {
        return $this->event_data[$type]['event'];
    }

    private function get_action($type = 'save') {
        return $this->event_data[$type]['action'];
    }

    private function get_resource_type() {
        return 'post';
    }

    private function get_resource_ID() {
        return $this->Post->ID;
    }

    private function get_resource_name() {
        return $this->Post->post_title;
    }

    private function get_context() {
        return 'wp-admin';
    }
}
