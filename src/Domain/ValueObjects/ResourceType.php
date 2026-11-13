<?php

declare(strict_types=1);

namespace WPLogWatch\Domain\ValueObjects;

use WPLogWatch\Domain\Exceptions\InvalidResourceTypeException;

/**
 * Identifies the kind of resource an audit event refers to.
 *
 * Unlike EventType, the set of resource types is intentionally open: custom
 * post types and custom taxonomies introduce arbitrary, lowercase slugs at
 * runtime, so the value object validates the format instead of an allowlist.
 */
final class ResourceType {

	public const POST   = 'post';
	public const PAGE   = 'page';
	public const USER   = 'user';
	public const TERM   = 'term';
	public const PLUGIN = 'plugin';
	public const THEME  = 'theme';
	public const SITE   = 'site';
	public const SYSTEM = 'system';

	private function __construct( private readonly string $value ) {
	}

	public static function fromString( string $value ): self {
		if ( $value === '' || preg_match( '/^[a-z0-9_\-]{1,100}$/', $value ) !== 1 ) {
			throw new InvalidResourceTypeException(
				sprintf( 'Invalid resource type "%s".', $value )
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
