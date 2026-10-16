<?php

namespace WPTrace\Database;

if (!defined("ABSPATH")) {
    exit;
}

final class AuditLogTableHelper
{
    public static function get_table_name()
    {
        global $wpdb;
        
        return $wpdb->prefix . 'audit_log';
    }
}
