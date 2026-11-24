<?php

declare(strict_types=1);

namespace WPLogWatch\Application\Contracts;

use DateTimeImmutable;

/**
 * Provides the current time. Abstracted so use cases remain deterministic and
 * testable without depending on WordPress time functions.
 */
interface ClockInterface {

	public function now(): DateTimeImmutable;
}
