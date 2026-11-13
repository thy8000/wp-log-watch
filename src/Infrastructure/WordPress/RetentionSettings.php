<?php

declare(strict_types=1);

namespace WPLogWatch\Infrastructure\WordPress;

/**
 * Access to the retention policy option.
 *
 * The stored value is a number of days; 0 means "keep forever".
 */
final class RetentionSettings {

	public const OPTION = 'wp_log_watch_retention_days';

	public const DEFAULT_DAYS = 90;

	public const ALLOWED_DAYS = array( 0, 7, 30, 90, 180, 365 );

	public function days(): int {
		return (int) get_option( self::OPTION, self::DEFAULT_DAYS );
	}

	public function update( int $days ): void {
		update_option( self::OPTION, $days );
	}
}
