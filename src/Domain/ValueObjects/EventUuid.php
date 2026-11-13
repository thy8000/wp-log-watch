<?php

declare(strict_types=1);

namespace WPLogWatch\Domain\ValueObjects;

use WPLogWatch\Domain\Exceptions\InvalidEventUuidException;

/**
 * A RFC 4122 version 4 UUID that uniquely identifies a single audit entry.
 */
final class EventUuid {

	private function __construct( private readonly string $value ) {
	}

	public static function v4(): self {
		$data = random_bytes( 16 );

		$data[6] = chr( ( ord( $data[6] ) & 0x0f ) | 0x40 );
		$data[8] = chr( ( ord( $data[8] ) & 0x3f ) | 0x80 );

		return new self( vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) ) );
	}

	public static function fromString( string $value ): self {
		if ( preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value ) !== 1 ) {
			throw new InvalidEventUuidException( sprintf( 'Invalid event UUID "%s".', $value ) );
		}

		return new self( strtolower( $value ) );
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
