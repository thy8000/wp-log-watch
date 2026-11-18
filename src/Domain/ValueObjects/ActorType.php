<?php

declare(strict_types=1);

namespace WPLogWatch\Domain\ValueObjects;

/**
 * Represents the kind of actor that originated an audit event.
 *
 * WordPress does not expose a single "actor" concept, so the domain models it
 * explicitly: a human user or an automated/system context (cron, CLI, REST…).
 */
enum ActorType: string {

	case User    = 'user';
	case System  = 'system';
	case Cron    = 'cron';
	case WpCli   = 'wp-cli';
	case RestApi = 'rest-api';

	public function isUser(): bool {
		return $this === self::User;
	}
}
