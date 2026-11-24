<?php

declare(strict_types=1);

namespace WPLogWatch\Presentation\Admin\Controllers;

use WPLogWatch\Infrastructure\WordPress\Capabilities;
use WPLogWatch\Infrastructure\WordPress\RetentionSettings;

/**
 * Handles the retention settings admin screen.
 */
final class SettingsController {

	public function __construct(
		private readonly RetentionSettings $retentionSettings,
	) {
	}

	public function render(): void {
		if ( ! current_user_can( Capabilities::MANAGE_SETTINGS ) ) {
			wp_die( esc_html__( 'You are not allowed to manage WP Log Watch settings.', 'wp-log-watch' ) );
		}

		$notice = null;
		$error  = null;

		if ( isset( $_POST['wp_log_watch_save'] ) ) {
			check_admin_referer( 'wp_log_watch_settings', '_wpnonce' );

			$days = isset( $_POST['retention_days'] ) ? absint( $_POST['retention_days'] ) : 0;

			if ( in_array( $days, RetentionSettings::ALLOWED_DAYS, true ) ) {
				$this->retentionSettings->update( $days );
				$notice = __( 'Settings saved.', 'wp-log-watch' );
			} else {
				$error = __( 'Invalid retention period.', 'wp-log-watch' );
			}
		}

		$days        = $this->retentionSettings->days();
		$allowedDays = RetentionSettings::ALLOWED_DAYS;

		include __DIR__ . '/../Views/settings.php';
	}
}
