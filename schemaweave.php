<?php
/**
 * Plugin Name: SchemaWeave
 * Plugin URI: https://github.com/erenkoyuncu/SchemaWeave
 * Description: Schema.org JSON-LD generation for WordPress using a framework-agnostic PHP core.
 * Version: 1.0.1
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Eren Koyuncu
 * Author URI: https://github.com/erenkoyuncu
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: schemaweave
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SCHEMAWEAVE_VERSION', '1.0.1');
define('SCHEMAWEAVE_DIR', plugin_dir_path(__FILE__));
define('SCHEMAWEAVE_URL', plugin_dir_url(__FILE__));

require_once SCHEMAWEAVE_DIR . 'includes/autoload.php';
require_once SCHEMAWEAVE_DIR . 'includes/class-settings.php';
require_once SCHEMAWEAVE_DIR . 'includes/class-installer.php';
require_once SCHEMAWEAVE_DIR . 'includes/class-post-meta.php';
require_once SCHEMAWEAVE_DIR . 'includes/class-faq-display.php';
require_once SCHEMAWEAVE_DIR . 'includes/class-tools.php';
require_once SCHEMAWEAVE_DIR . 'includes/class-diagnostics.php';
require_once SCHEMAWEAVE_DIR . 'includes/class-cli.php';
require_once SCHEMAWEAVE_DIR . 'includes/class-wordpress-data-provider.php';
require_once SCHEMAWEAVE_DIR . 'includes/class-wordpress-url-resolver.php';
require_once SCHEMAWEAVE_DIR . 'includes/class-wordpress-schema-bridge.php';

SchemaWeave_WordPress_Settings::boot();
SchemaWeave_WordPress_Installer::boot();
SchemaWeave_WordPress_Post_Meta::boot();
SchemaWeave_WordPress_FAQ_Display::boot();
SchemaWeave_WordPress_Tools::boot();
SchemaWeave_WordPress_Diagnostics::boot();
SchemaWeave_WordPress_CLI::boot();

register_activation_hook(__FILE__, ['SchemaWeave_WordPress_Installer', 'activate']);

add_action('wp_head', ['SchemaWeave_WordPress_Schema_Bridge', 'render'], 99);

add_filter('plugin_action_links_' . plugin_basename(__FILE__), static function (array $links): array {
    $settingsUrl = admin_url('options-general.php?page=' . SchemaWeave_WordPress_Settings::PAGE_SLUG);
    $diagnosticsUrl = admin_url('options-general.php?page=' . SchemaWeave_WordPress_Diagnostics::PAGE_SLUG);
    array_unshift($links, '<a href="' . esc_url($diagnosticsUrl) . '">' . esc_html__('Diagnostics', 'schemaweave') . '</a>');
    array_unshift($links, '<a href="' . esc_url($settingsUrl) . '">' . esc_html__('Settings', 'schemaweave') . '</a>');
    return $links;
});
