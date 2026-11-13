<?php
/**
 * Plugin Name:       WP Log Watch
 * Plugin URI:        https://example.com/wp-log-watch
 * Description:       Audit log plugin for WordPress. Tracks content, user, system and security events and protects the records against unauthorized access.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.2
 * Author:            WP Log Watch
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-log-watch
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_LOG_WATCH_FILE', __FILE__ );
define( 'WP_LOG_WATCH_VERSION', '0.1.0' );

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	spl_autoload_register(
		static function ( string $class_name ): void {
			$prefix = 'WPLogWatch\\';

			if ( ! str_starts_with( $class_name, $prefix ) ) {
				return;
			}

			$relative = substr( $class_name, strlen( $prefix ) );
			$path     = __DIR__ . '/src/' . str_replace( '\\', '/', $relative ) . '.php';

			if ( file_exists( $path ) ) {
				require_once $path;
			}
		}
	);
}

WPLogWatch\Plugin::instance()->boot();
