<?php

declare(strict_types=1);

namespace WPLogWatch\Infrastructure\WordPress\Hooks;

use WPLogWatch\Domain\ValueObjects\EventType;
use WPLogWatch\Domain\ValueObjects\ResourceType;

/**
 * Detects the registration/removal of custom post types and taxonomies.
 *
 * WordPress re-registers custom types on every request, so hooking
 * registered_post_type directly would flood the log. Instead this keeps a
 * snapshot of the known custom types and only records a "system" event when the
 * set changes — which happens when a plugin or theme adds or removes a type.
 */
final class RegistrationHooks extends AbstractHook {

	private const OPTION_POST_TYPES = 'wp_log_watch_registered_post_types';

	private const OPTION_TAXONOMIES = 'wp_log_watch_registered_taxonomies';

	public function register(): void {
		add_action( 'wp_loaded', array( $this, 'detectChanges' ), 999 );
	}

	public function detectChanges(): void {
		$this->detectTypeChanges(
			'post_type',
			self::OPTION_POST_TYPES,
			get_post_types( array( '_builtin' => false ), 'names' )
		);

		$this->detectTypeChanges(
			'taxonomy',
			self::OPTION_TAXONOMIES,
			get_taxonomies( array( '_builtin' => false ), 'names' )
		);
	}

	/**
	 * @param string[] $current
	 */
	private function detectTypeChanges( string $kind, string $option, array $current ): void {
		$current = array_values( array_map( 'strval', $current ) );
		sort( $current );

		$stored = get_option( $option, null );

		// First run: just record the baseline, no events.
		if ( ! is_array( $stored ) ) {
			update_option( $option, $current, false );

			return;
		}

		$added   = array_values( array_diff( $current, $stored ) );
		$removed = array_values( array_diff( $stored, $current ) );

		if ( $added === array() && $removed === array() ) {
			return;
		}

		foreach ( $added as $name ) {
			$this->recordRegistration( $kind, 'registered', $name );
		}

		foreach ( $removed as $name ) {
			$this->recordRegistration( $kind, 'unregistered', $name );
		}

		update_option( $option, $current, false );
	}

	private function recordRegistration( string $kind, string $action, string $name ): void {
		$eventType = match ( $kind ) {
			'post_type' => 'registered' === $action ? EventType::SYSTEM_POST_TYPE_REGISTERED : EventType::SYSTEM_POST_TYPE_UNREGISTERED,
			default => 'registered' === $action ? EventType::SYSTEM_TAXONOMY_REGISTERED : EventType::SYSTEM_TAXONOMY_UNREGISTERED,
		};

		$this->record(
			EventType::fromString( $eventType ),
			ResourceType::fromString( ResourceType::SYSTEM ),
			null,
			sprintf( 'Custom %s "%s" %s.', $kind, $name, $action ),
			array(
				'kind' => $kind,
				'name' => $name,
			),
		);
	}
}
