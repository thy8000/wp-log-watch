<?php

declare(strict_types=1);

namespace WPLogWatch\Presentation\Admin;

use WPLogWatch\Infrastructure\WordPress\Capabilities;
use WPLogWatch\Presentation\Admin\Controllers\AuditLogController;
use WPLogWatch\Presentation\Admin\Controllers\SettingsController;

/**
 * Registers the admin menu pages and their assets.
 */
final class AdminMenu {

	private const LIST_HOOK = 'toplevel_page_wp-log-watch';

	private const SETTINGS_HOOK = 'wp-log-watch_page_wp-log-watch-settings';

	public function __construct(
		private readonly AuditLogController $auditLogController,
		private readonly SettingsController $settingsController,
	) {
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'addMenus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAssets' ) );
	}

	public function addMenus(): void {
		add_menu_page(
			__( 'Audit Log', 'wp-log-watch' ),
			__( 'WP Log Watch', 'wp-log-watch' ),
			Capabilities::VIEW_LOGS,
			'wp-log-watch',
			array( $this->auditLogController, 'render' ),
			'dashicons-list-view',
			80,
		);

		add_submenu_page(
			'wp-log-watch',
			__( 'Audit Log', 'wp-log-watch' ),
			__( 'Audit Log', 'wp-log-watch' ),
			Capabilities::VIEW_LOGS,
			'wp-log-watch',
			array( $this->auditLogController, 'render' ),
		);

		add_submenu_page(
			'wp-log-watch',
			__( 'Settings', 'wp-log-watch' ),
			__( 'Settings', 'wp-log-watch' ),
			Capabilities::MANAGE_SETTINGS,
			'wp-log-watch-settings',
			array( $this->settingsController, 'render' ),
		);
	}

	public function enqueueAssets( string $hook ): void {
		if ( ! in_array( $hook, array( self::LIST_HOOK, self::SETTINGS_HOOK ), true ) ) {
			return;
		}

		wp_enqueue_style(
			'wp-log-watch-admin',
			plugin_dir_url( WP_LOG_WATCH_FILE ) . 'assets/css/admin.css',
			array(),
			WP_LOG_WATCH_VERSION
		);

		wp_enqueue_script(
			'wp-log-watch-admin',
			plugin_dir_url( WP_LOG_WATCH_FILE ) . 'assets/js/admin.js',
			array(),
			WP_LOG_WATCH_VERSION,
			true
		);
	}
}
