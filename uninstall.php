<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$schemaweave_option_name = 'schemaweave_settings';
$schemaweave_settings = get_option($schemaweave_option_name, []);
$schemaweave_delete_data = is_array($schemaweave_settings)
    && !empty($schemaweave_settings['advanced'])
    && is_array($schemaweave_settings['advanced'])
    && !empty($schemaweave_settings['advanced']['delete_data_on_uninstall']);

if (!$schemaweave_delete_data) {
    return;
}

delete_option($schemaweave_option_name);
delete_option('schemaweave_db_version');

if (is_multisite()) {
    delete_site_option('schemaweave_db_version');
}

$schemaweave_meta_keys = [
    '_schemaweave_disabled',
    '_schemaweave_type_override',
    '_schemaweave_page_type_override',
    '_schemaweave_image',
    '_schemaweave_brand',
    '_schemaweave_description',
    '_schemaweave_faq',
];

foreach ($schemaweave_meta_keys as $schemaweave_meta_key) {
    delete_post_meta_by_key($schemaweave_meta_key);
}
