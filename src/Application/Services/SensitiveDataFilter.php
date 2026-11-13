<?php

declare(strict_types=1);

namespace WPLogWatch\Application\Services;

/**
 * Removes or masks sensitive values before an event is persisted.
 *
 * This is a defence-in-depth layer: hooks already use explicit allowlists for
 * what they capture, but this filter guarantees that a key containing a
 * sensitive pattern never reaches the database even if a future hook forgets.
 */
final class SensitiveDataFilter {

	private const SENSITIVE_PATTERNS = array(
		'password',
		'passwd',
		'pass1',
		'pass2',
		'pwd',
		'secret',
		'token',
		'api_key',
		'apikey',
		'authorization',
		'auth',
		'cookie',
		'nonce',
		'_wpnonce',
		'session',
		'credit_card',
		'cvv',
		'ssn',
		'pin',
	);

	private const REDACTED = '[redacted]';

	/**
	 * @param array<string, mixed> $metadata
	 *
	 * @return array<string, mixed>
	 */
	public function filter( array $metadata ): array {
		return $this->filterRecursive( $metadata );
	}

	/**
	 * @param array<string, mixed> $data
	 *
	 * @return array<string, mixed>
	 */
	private function filterRecursive( array $data ): array {
		$filtered = array();

		foreach ( $data as $key => $value ) {
			if ( $this->isSensitiveKey( (string) $key ) ) {
				$filtered[ $key ] = self::REDACTED;
				continue;
			}

			$filtered[ $key ] = is_array( $value ) ? $this->filterRecursive( $value ) : $value;
		}

		return $filtered;
	}

	private function isSensitiveKey( string $key ): bool {
		$normalized = strtolower( $key );

		foreach ( self::SENSITIVE_PATTERNS as $pattern ) {
			if ( str_contains( $normalized, $pattern ) ) {
				return true;
			}
		}

		return false;
	}
}
