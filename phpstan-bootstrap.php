<?php
/**
 * PHPStan bootstrap: defines WordPress/plugin constants that are resolved at
 * runtime but absent from the wordpress-stubs package.
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', __DIR__ . '/');
}

if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}

if (!defined('WP_LOG_WATCH_FILE')) {
    define('WP_LOG_WATCH_FILE', __DIR__ . '/wp-log-watch.php');
}

if (!defined('WP_LOG_WATCH_VERSION')) {
    define('WP_LOG_WATCH_VERSION', '0.1.0');
}
