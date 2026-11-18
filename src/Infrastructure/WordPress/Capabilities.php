<?php

declare(strict_types=1);

namespace WPLogWatch\Infrastructure\WordPress;

/**
 * The plugin's custom capabilities.
 */
final class Capabilities {

	public const VIEW_LOGS = 'view_wp_log_watch_logs';

	public const MANAGE_SETTINGS = 'manage_wp_log_watch_settings';

	public static function install(): void {
		$administrator = get_role( 'administrator' );

		if ( ! $administrator instanceof \WP_Role ) {
			return;
		}

		$administrator->add_cap( self::VIEW_LOGS );
		$administrator->add_cap( self::MANAGE_SETTINGS );
	}
}
