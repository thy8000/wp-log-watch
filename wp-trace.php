<?php
/**
 * Plugin Name:       WP Trace
 * Description:       Audit log plugin for WordPress.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            You
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-trace
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_TRACE_FILE', __FILE__ );

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

use \WPTrace\Infrastructure\PluginActivator;
use \WPTrace\Infrastructure\WordPress\Posts\PostUpdatedListener;

\register_activation_hook(
	__FILE__,
	[PluginActivator::class, 'activate']
);

$PostUpdatedListener = new PostUpdatedListener();
$PostUpdatedListener->register();
