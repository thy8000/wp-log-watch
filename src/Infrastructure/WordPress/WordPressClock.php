<?php

declare(strict_types=1);

namespace WPLogWatch\Infrastructure\WordPress;

use DateTimeImmutable;
use DateTimeZone;
use WPLogWatch\Application\Contracts\ClockInterface;

/**
 * Clock backed by WordPress UTC time.
 */
final class WordPressClock implements ClockInterface {

	public function now(): DateTimeImmutable {
		return new DateTimeImmutable( current_time( 'mysql', true ), new DateTimeZone( 'UTC' ) );
	}
}
