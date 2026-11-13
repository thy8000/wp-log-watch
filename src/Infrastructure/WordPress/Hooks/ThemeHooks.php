<?php

declare(strict_types=1);

namespace WPLogWatch\Infrastructure\WordPress\Hooks;

use WP_Theme;
use WPLogWatch\Domain\ValueObjects\EventType;
use WPLogWatch\Domain\ValueObjects\ResourceType;

/**
 * Audits theme activation. Theme installs/updates are handled by PluginHooks
 * through the shared upgrader_process_complete event.
 */
final class ThemeHooks extends AbstractHook {

	public function register(): void {
		add_action( 'switch_theme', array( $this, 'onSwitch' ), 10, 3 );
	}

	public function onSwitch( string $newName, WP_Theme $newTheme, WP_Theme $oldTheme ): void {
		$this->record(
			EventType::fromString( EventType::THEME_ACTIVATED ),
			ResourceType::fromString( ResourceType::THEME ),
			null,
			sprintf( 'Theme switched from "%s" to "%s".', $oldTheme->get( 'Name' ), $newTheme->get( 'Name' ) ),
			array(
				'theme'    => $newTheme->get_stylesheet(),
				'new_name' => $newTheme->get( 'Name' ),
				'old_name' => $oldTheme->get( 'Name' ),
			),
		);
	}
}
