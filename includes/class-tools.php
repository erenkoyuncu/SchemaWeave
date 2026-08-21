<?php
final class SchemaWeave_WordPress_Tools
{
    public const EXPORT_ACTION = 'schemaweave_export_settings';
    public const IMPORT_ACTION = 'schemaweave_import_settings';
    public const RESET_ACTION = 'schemaweave_reset_settings';

    public static function boot(): void
    {
        add_action('admin_post_' . self::EXPORT_ACTION, [self::class, 'export']);
        add_action('admin_post_' . self::IMPORT_ACTION, [self::class, 'import']);
        add_action('admin_post_' . self::RESET_ACTION, [self::class, 'reset']);
        add_action('admin_notices', [self::class, 'notice']);
    }

    public static function export(): void
    {
        self::authorize(self::EXPORT_ACTION);

        $payload = [
            'format' => 'schemaweave-settings',
            'version' => SCHEMAWEAVE_VERSION,
            'exported_at' => gmdate('c'),
            'settings' => SchemaWeave_WordPress_Settings::get(),
        ];

        $json = wp_json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            wp_die(esc_html__('Could not encode SchemaWeave settings.', 'schemaweave'));
        }

        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="schemaweave-settings-' . gmdate('Y-m-d') . '.json"');
        echo $json;
        exit;
    }

    public static function import(): void
    {
        self::authorize(self::IMPORT_ACTION);

        if (
            empty($_FILES['schemaweave_settings_file'])
            || !is_array($_FILES['schemaweave_settings_file'])
            || (int) ($_FILES['schemaweave_settings_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
        ) {
            self::redirect('import-error');
        }

        $tmp = (string) ($_FILES['schemaweave_settings_file']['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            self::redirect('import-error');
        }

        $size = (int) ($_FILES['schemaweave_settings_file']['size'] ?? 0);
        if ($size <= 0 || $size > 1024 * 1024) {
            self::redirect('import-size');
        }

        $contents = file_get_contents($tmp);
        if ($contents === false) {
            self::redirect('import-error');
        }

        $decoded = json_decode($contents, true);
        if (!is_array($decoded)) {
            self::redirect('import-json');
        }

        $settings = isset($decoded['settings']) && is_array($decoded['settings'])
            ? $decoded['settings']
            : $decoded;

        update_option(
            SchemaWeave_WordPress_Settings::OPTION,
            SchemaWeave_WordPress_Settings::sanitize($settings)
        );

        self::redirect('imported');
    }

    public static function reset(): void
    {
        self::authorize(self::RESET_ACTION);
        update_option(SchemaWeave_WordPress_Settings::OPTION, SchemaWeave_WordPress_Settings::defaults());
        self::redirect('reset');
    }

    public static function notice(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $status = isset($_GET['schemaweave_status'])
            ? sanitize_key((string) wp_unslash($_GET['schemaweave_status']))
            : '';

        $messages = [
            'imported' => ['success', __('SchemaWeave settings imported successfully.', 'schemaweave')],
            'reset' => ['success', __('SchemaWeave settings were reset to defaults.', 'schemaweave')],
            'import-error' => ['error', __('The settings file could not be uploaded.', 'schemaweave')],
            'import-size' => ['error', __('The settings file is too large. Maximum size is 1 MB.', 'schemaweave')],
            'import-json' => ['error', __('The uploaded file is not valid JSON.', 'schemaweave')],
        ];

        if (!isset($messages[$status])) {
            return;
        }

        [$class, $message] = $messages[$status];
        echo '<div class="notice notice-' . esc_attr($class) . ' is-dismissible"><p>' . esc_html($message) . '</p></div>';
    }

    public static function renderSettingsTools(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <section class="schemaweave-card">
            <div class="schemaweave-card-heading">
                <div>
                    <h2><?php esc_html_e('Backup & Recovery', 'schemaweave'); ?></h2>
                    <p><?php esc_html_e('Export, import, or reset SchemaWeave configuration. Export files contain plugin settings only.', 'schemaweave'); ?></p>
                </div>
            </div>

            <div class="schemaweave-tools-grid">
                <div class="schemaweave-tool-panel">
                    <h3><?php esc_html_e('Export settings', 'schemaweave'); ?></h3>
                    <p><?php esc_html_e('Download the current SchemaWeave configuration as JSON.', 'schemaweave'); ?></p>
                    <a class="button button-secondary" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=' . self::EXPORT_ACTION), self::EXPORT_ACTION)); ?>">
                        <?php esc_html_e('Export JSON', 'schemaweave'); ?>
                    </a>
                </div>

                <div class="schemaweave-tool-panel">
                    <h3><?php esc_html_e('Import settings', 'schemaweave'); ?></h3>
                    <p><?php esc_html_e('Restore settings from an SchemaWeave JSON export. Imported values are sanitized before saving.', 'schemaweave'); ?></p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="<?php echo esc_attr(self::IMPORT_ACTION); ?>">
                        <?php wp_nonce_field(self::IMPORT_ACTION); ?>
                        <input type="file" name="schemaweave_settings_file" accept="application/json,.json" required>
                        <button type="submit" class="button button-secondary"><?php esc_html_e('Import JSON', 'schemaweave'); ?></button>
                    </form>
                </div>

                <div class="schemaweave-tool-panel schemaweave-tool-danger">
                    <h3><?php esc_html_e('Reset settings', 'schemaweave'); ?></h3>
                    <p><?php esc_html_e('Restore plugin configuration to its default values. Content-level SchemaWeave meta is not deleted.', 'schemaweave'); ?></p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php echo esc_js(__('Reset SchemaWeave settings to defaults?', 'schemaweave')); ?>');">
                        <input type="hidden" name="action" value="<?php echo esc_attr(self::RESET_ACTION); ?>">
                        <?php wp_nonce_field(self::RESET_ACTION); ?>
                        <button type="submit" class="button button-secondary"><?php esc_html_e('Reset to defaults', 'schemaweave'); ?></button>
                    </form>
                </div>
            </div>
        </section>
        <?php
    }

    private static function authorize(string $action): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to manage SchemaWeave settings.', 'schemaweave'));
        }

        check_admin_referer($action);
    }

    private static function redirect(string $status): void
    {
        wp_safe_redirect(add_query_arg(
            [
                'page' => SchemaWeave_WordPress_Settings::PAGE_SLUG,
                'schemaweave_status' => $status,
            ],
            admin_url('options-general.php')
        ));
        exit;
    }
}
