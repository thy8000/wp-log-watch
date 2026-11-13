<?php

declare(strict_types=1);

namespace WPLogWatch\Infrastructure\Logging;

/**
 * Minimal internal error logger. Audit entries must never write to the PHP
 * error log as their primary channel; this is only for the plugin's own
 * failures while observing WordPress.
 */
final class ErrorLogger {

	public function error( string $message ): void {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional: this is the plugin's own failure channel, not the audit log.
		error_log( '[WP Log Watch] ' . $message );
	}
}
