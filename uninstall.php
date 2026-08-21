<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$optionName = 'schemaweave_settings';
$settings = get_option($optionName, []);
$deleteData = is_array($settings)
    && !empty($settings['advanced'])
    && is_array($settings['advanced'])
    && !empty($settings['advanced']['delete_data_on_uninstall']);

if (!$deleteData) {
    return;
}

delete_option($optionName);
delete_option('schemaweave_db_version');

if (is_multisite()) {
    delete_site_option('schemaweave_db_version');
}

global $wpdb;
$metaKeys = [
    '_schemaweave_disabled',
    '_schemaweave_type_override',
    '_schemaweave_page_type_override',
    '_schemaweave_image',
    '_schemaweave_brand',
    '_schemaweave_description',
    '_schemaweave_faq',
];

foreach ($metaKeys as $metaKey) {
    $wpdb->delete($wpdb->postmeta, ['meta_key' => $metaKey], ['%s']);
}
