<?php

declare(strict_types=1);

namespace WPLogWatch\Infrastructure\Cron;

use WPLogWatch\Application\UseCases\DeleteExpiredAuditLogs;
use WPLogWatch\Infrastructure\Logging\ErrorLogger;
use WPLogWatch\Infrastructure\WordPress\RetentionSettings;

/**
 * Schedules and executes the retention cleanup job via WP-Cron.
 */
final class RetentionScheduler {

	public const HOOK = 'wp_log_watch_retention';

	public function __construct(
		private readonly DeleteExpiredAuditLogs $deleteExpiredAuditLogs,
		private readonly RetentionSettings $retentionSettings,
		private readonly ErrorLogger $errorLogger,
	) {
	}

	public function register(): void {
		add_action( self::HOOK, array( $this, 'run' ) );
	}

	public function schedule(): void {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::HOOK );
		}
	}

	public function unschedule(): void {
		wp_clear_scheduled_hook( self::HOOK );
	}

	public function run(): void {
		$days = $this->retentionSettings->days();

		if ( $days <= 0 ) {
			return;
		}

		try {
			$this->deleteExpiredAuditLogs->execute( $days );
		} catch ( \Throwable $throwable ) {
			$this->errorLogger->error( 'Retention job failed: ' . $throwable->getMessage() );
		}
	}
}
