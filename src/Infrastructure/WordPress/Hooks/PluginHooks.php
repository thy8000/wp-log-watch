<?php

declare(strict_types=1);

namespace WPLogWatch\Infrastructure\WordPress\Hooks;

use WPLogWatch\Domain\ValueObjects\EventType;
use WPLogWatch\Domain\ValueObjects\ResourceType;

/**
 * Audits plugin installation, activation, deactivation, updates and removal.
 */
final class PluginHooks extends AbstractHook {

	public function register(): void {
		add_action( 'activated_plugin', array( $this, 'onActivated' ), 10, 1 );
		add_action( 'deactivated_plugin', array( $this, 'onDeactivated' ), 10, 1 );
		add_action( 'deleted_plugin', array( $this, 'onDeleted' ), 10, 1 );
		add_action( 'upgrader_process_complete', array( $this, 'onUpgrade' ), 10, 2 );
	}

	public function onActivated( string $plugin ): void {
		$this->recordPluginEvent( EventType::fromString( EventType::PLUGIN_ACTIVATED ), $plugin, 'activated' );
	}

	public function onDeactivated( string $plugin ): void {
		$this->recordPluginEvent( EventType::fromString( EventType::PLUGIN_DEACTIVATED ), $plugin, 'deactivated' );
	}

	public function onDeleted( string $plugin ): void {
		$this->recordPluginEvent( EventType::fromString( EventType::PLUGIN_DELETED ), $plugin, 'deleted' );
	}

	/**
	 * @param array<string, mixed> $hookExtra
	 */
	public function onUpgrade( \WP_Upgrader $upgrader, array $hookExtra ): void {
		$action = isset( $hookExtra['action'] ) ? (string) $hookExtra['action'] : '';
		$type   = isset( $hookExtra['type'] ) ? (string) $hookExtra['type'] : '';

		if ( $action === 'update' && $type === 'core' ) {
			$version = isset( $hookExtra['version'] ) ? (string) $hookExtra['version'] : get_bloginfo( 'version' );

			$this->record(
				EventType::fromString( EventType::SYSTEM_CORE_UPDATED ),
				ResourceType::fromString( ResourceType::SYSTEM ),
				null,
				sprintf( 'WordPress core updated to version %s.', $version ),
				array( 'version' => $version ),
			);

			return;
		}

		if ( $type !== 'plugin' && $type !== 'theme' ) {
			return;
		}

		$items    = isset( $hookExtra['plugins'] ) ? (array) $hookExtra['plugins'] : (array) ( $hookExtra['themes'] ?? array() );
		$isUpdate = $action === 'update';

		foreach ( $items as $item ) {
			$item = (string) $item;

			if ( $type === 'plugin' ) {
				$eventType = $isUpdate ? EventType::PLUGIN_UPDATED : EventType::PLUGIN_INSTALLED;
				$this->recordPluginEvent( EventType::fromString( $eventType ), $item, $isUpdate ? 'updated' : 'installed' );
			} else {
				$eventType = $isUpdate ? EventType::THEME_UPDATED : EventType::THEME_INSTALLED;
				$this->recordThemeEvent( EventType::fromString( $eventType ), $item, $isUpdate ? 'updated' : 'installed' );
			}
		}
	}

	private function recordPluginEvent( EventType $eventType, string $plugin, string $verb ): void {
		$name = $this->pluginName( $plugin );

		$this->record(
			$eventType,
			ResourceType::fromString( ResourceType::PLUGIN ),
			null,
			sprintf( 'Plugin "%s" %s.', $name, $verb ),
			array(
				'plugin'      => $plugin,
				'plugin_name' => $name,
			),
		);
	}

	private function recordThemeEvent( EventType $eventType, string $theme, string $verb ): void {
		$this->record(
			$eventType,
			ResourceType::fromString( ResourceType::THEME ),
			null,
			sprintf( 'Theme "%s" %s.', $theme, $verb ),
			array( 'theme' => $theme ),
		);
	}

	private function pluginName( string $plugin ): string {
		$path = WP_PLUGIN_DIR . '/' . $plugin;

		if ( ! file_exists( $path ) ) {
			return $plugin;
		}

		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$data = get_plugin_data( $path, false, false );

		return ! empty( $data['Name'] ) ? $data['Name'] : $plugin;
	}
}
