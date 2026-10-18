<?php

namespace WPTrace\Logger;

if (!defined('ABSPATH')) {
    exit;
}

use WPTrace\Builder\EventPostBuilder;
use WPTrace\Database\AuditLogTableHelper;

final class PostLogger
{
    public function save($post_ID, $event_type = 'create')
    {
        global $wpdb;

        $EventPostBuilder = new EventPostBuilder($post_ID);
        $event_post_data = $EventPostBuilder->set_post_data($event_type);
        $event_post_format = $EventPostBuilder->set_post_data_format($event_type);

        $table_name = AuditLogTableHelper::get_table_name();

        $result = $wpdb->insert($table_name, $event_post_data, $event_post_format);

        if (empty($result) || is_wp_error($result)) {
            return;
        }

        $insert_ID = $wpdb->insert_id;

        return;
    }
}
