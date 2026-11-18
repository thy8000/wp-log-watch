<?php

declare(strict_types=1);

namespace WPLogWatch\Infrastructure\Persistence\Database;

/**
 * Owns the audit log table schema and its lifecycle (creation/upgrades).
 *
 * Uses a dedicated table (never post meta or options) as required by the spec.
 */
final class AuditTableSchema {

	public const OPTION_DB_VERSION = 'wp_log_watch_db_version';

	public const DB_VERSION = '1.0.0';

	private const TABLE_SUFFIX = 'wp_log_watch_audit_logs';

	public static function tableName(): string {
		global $wpdb;

		return $wpdb->prefix . self::TABLE_SUFFIX;
	}

	public static function createTable(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table          = self::tableName();
		$charsetCollate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_uuid CHAR(36) NOT NULL,
            actor_id BIGINT UNSIGNED NULL,
            event_type VARCHAR(100) NOT NULL,
            resource_type VARCHAR(100) NULL,
            resource_id BIGINT UNSIGNED NULL,
            description TEXT NULL,
            metadata LONGTEXT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY event_uuid (event_uuid),
            KEY actor_id (actor_id),
            KEY event_type (event_type),
            KEY resource (resource_type, resource_id),
            KEY created_at (created_at)
        ) {$charsetCollate};";

		dbDelta( $sql );

		update_option( self::OPTION_DB_VERSION, self::DB_VERSION );
	}

	public static function maybeUpgrade(): void {
		if ( get_option( self::OPTION_DB_VERSION ) !== self::DB_VERSION ) {
			self::createTable();
		}
	}
}
