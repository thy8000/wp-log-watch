<?php

namespace WPTrace\Database;

if(!defined("ABSPATH")) {
    exit;
}

final class AuditLogTable {
    private string $db_version = '1.0.0';

    public function __construct(string $plugin_file) {
        register_activation_hook($plugin_file, [$this, 'create_table']);
    }

    public function create_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'audit_log';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            ID mediumint(9) NOT NULL AUTO_INCREMENT,
            event_type varchar(20) NOT NULL,
            action varchar(20) NOT NULL,
            actor_user_ID mediumint(9) unsigned NOT NULL,
            actor_login varchar(100) NOT NULL,
            resource_type varchar(20) NOT NULL,
            resource_ID mediumint(9),
            resource_name varchar(300) NOT NULL,
            context varchar(20),
            ip_address VARBINARY(16), 
            user_agent varchar(100),
            request_id mediumint(9) NOT NULL,
            occurred_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            changes LONGTEXT,
            PRIMARY KEY (ID)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql);

        update_option('wp-trace_db_version', $this->db_version);
    }
}