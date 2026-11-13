<?php

declare(strict_types=1);

namespace WPLogWatch\Shared\Container;

use RuntimeException;

/**
 * A minimal service container.
 *
 * The plugin is small enough that a full PSR-11 implementation would be
 * overkill; this container keeps wiring in one place (the Plugin composition
 * root) and makes every service a singleton by default.
 */
final class Container {

	/**
	 * @var array<string, object>
	 */
	private array $instances = array();

	/**
	 * @var array<string, callable(): object>
	 */
	private array $factories = array();

	public function set( string $id, object $service ): void {
		$this->instances[ $id ] = $service;
	}

	/**
	 * @param callable(): object $factory
	 */
	public function factory( string $id, callable $factory ): void {
		$this->factories[ $id ] = $factory;
	}

	public function get( string $id ): object {
		if ( isset( $this->instances[ $id ] ) ) {
			return $this->instances[ $id ];
		}

		if ( isset( $this->factories[ $id ] ) ) {
			$service                = ( $this->factories[ $id ] )();
			$this->instances[ $id ] = $service;

			return $service;
		}

		throw new RuntimeException( sprintf( 'Service not found in container: "%s".', $id ) );
	}

	public function has( string $id ): bool {
		return isset( $this->instances[ $id ] ) || isset( $this->factories[ $id ] );
	}
}
