<?php

namespace WPTrace\Builder;

if(!defined('ABSPATH')) {
    exit;
}

class EventBuilder {
    public function get_user_ID() {
        return \get_current_user_id();
    }
}