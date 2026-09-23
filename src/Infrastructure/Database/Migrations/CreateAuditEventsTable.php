<?php

namespace WPTrace\Infrastructure\Database\Migrations;

class CreateAuditEventsTable {
    public function up(): void {
        global $wpdb;
        $table_name = $wpdb->prefix . 'log_watch_user_activity_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_type varchar(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            action varchar(255) NOT NULL,
            object_id bigint(20) NOT NULL,
            object_type varchar(50) NOT NULL,
            context varchar(50) NOT NULL,
            ip_address varchar(45) NULL,
            user_agent text NULL,
            metadata longtext NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY created_at (created_at),
            KEY actor_id (actor_id),
            KEY action (action),
            KEY object_type_object_id (object_type, object_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        \dbDelta($sql);
    }
}