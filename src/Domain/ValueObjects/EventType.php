<?php

declare(strict_types=1);

namespace WPLogWatch\Domain\ValueObjects;

use WPLogWatch\Domain\Exceptions\InvalidEventTypeException;

/**
 * Identifies the semantic kind of an audit event (e.g. "post.updated").
 *
 * Event types follow the pattern `resource.action` and are constrained to a
 * lowercase, dotted format. The well-known values are exposed as constants so
 * callers get autocompletion and avoid typos, while the value object still
 * allows the set to grow without changing the domain.
 */
final class EventType {

	public const POST_CREATED      = 'post.created';
	public const POST_UPDATED      = 'post.updated';
	public const POST_PUBLISHED    = 'post.published';
	public const POST_DRAFTED      = 'post.drafted';
	public const POST_TRASHED      = 'post.trashed';
	public const POST_RESTORED     = 'post.restored';
	public const POST_DELETED      = 'post.deleted';
	public const POST_META_ADDED   = 'post.meta_added';
	public const POST_META_UPDATED = 'post.meta_updated';
	public const POST_META_DELETED = 'post.meta_deleted';

	public const TERM_CREATED = 'term.created';
	public const TERM_UPDATED = 'term.updated';
	public const TERM_DELETED = 'term.deleted';

	public const USER_CREATED      = 'user.created';
	public const USER_UPDATED      = 'user.updated';
	public const USER_DELETED      = 'user.deleted';
	public const USER_LOGIN        = 'user.login';
	public const USER_LOGOUT       = 'user.logout';
	public const USER_LOGIN_FAILED = 'user.login_failed';
	public const USER_ROLE_CHANGED = 'user.role_changed';

	public const PLUGIN_ACTIVATED   = 'plugin.activated';
	public const PLUGIN_DEACTIVATED = 'plugin.deactivated';
	public const PLUGIN_INSTALLED   = 'plugin.installed';
	public const PLUGIN_UPDATED     = 'plugin.updated';
	public const PLUGIN_DELETED     = 'plugin.deleted';

	public const THEME_ACTIVATED = 'theme.activated';
	public const THEME_INSTALLED = 'theme.installed';
	public const THEME_UPDATED   = 'theme.updated';

	public const SYSTEM_CORE_UPDATED = 'system.core_updated';

	private function __construct( private readonly string $value ) {
	}

	public static function fromString( string $value ): self {
		if ( preg_match( '/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/', $value ) !== 1 ) {
			throw new InvalidEventTypeException(
				sprintf( 'Invalid event type "%s". Expected format "resource.action".', $value )
			);
		}

		return new self( $value );
	}

	public function value(): string {
		return $this->value;
	}

	public function equals( self $other ): bool {
		return $this->value === $other->value;
	}

	public function __toString(): string {
		return $this->value;
	}
}
