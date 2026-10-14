<?php

namespace WPTrace\Builder;

if(!defined('ABSPATH')) {
    exit;
}

class EventBuilder {
    private $User = null;

    private function __construct()
    {
        $this->User = \wp_get_current_user();
    }

    public function get_user_ID() {
        return $this->User->ID;
    }

    public function get_user_login() {
        return $this->User->user_login;
    }

    public function get_ip_address() {
        return $_SERVER['REMOTE_ADDR'];
    }

    public function get_user_agent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? esc_html__('Desconhecido', 'wp-trace');
    }
}