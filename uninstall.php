<?php
/**
 * Uninstall handler for WP Log Watch.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wp_log_watch_audit_logs" );

delete_option( 'wp_log_watch_db_version' );
delete_option( 'wp_log_watch_retention_days' );

wp_clear_scheduled_hook( 'wp_log_watch_retention' );

$administrator = get_role( 'administrator' );

if ( $administrator instanceof WP_Role ) {
	$administrator->remove_cap( 'view_wp_log_watch_logs' );
	$administrator->remove_cap( 'manage_wp_log_watch_settings' );
}
