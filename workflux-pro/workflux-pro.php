<?php
/**
 * Plugin Name: WorkFlux Pro
 * Description: Workforce and project management plugin with roles, attendance, leaves, projects, tasks, and dashboards.
 * Version: 0.1.0
 * Author: Sri Hayavadhana
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: workflux-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WFP_VERSION', '0.1.0');
define('WFP_PLUGIN_FILE', __FILE__);
define('WFP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WFP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Simple PSR-4 style autoloader for the WFP namespace
spl_autoload_register(function ($class) {
    $prefix = 'WFP\\';
    $base_dir = WFP_PLUGIN_DIR . 'src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

register_activation_hook(__FILE__, function () {
    if (class_exists('WFP\\Activator')) {
        \WFP\Activator::activate();
    }
});

register_deactivation_hook(__FILE__, function () {
    if (class_exists('WFP\\Deactivator')) {
        \WFP\Deactivator::deactivate();
    }
});

add_action('plugins_loaded', function () {
    if (class_exists('WFP\\Plugin')) {
        $plugin = new \WFP\Plugin();
        $plugin->run();
    }
});

