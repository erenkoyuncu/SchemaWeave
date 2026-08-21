<?php
final class SchemaWeave_WordPress_CLI
{
    public static function boot(): void
    {
        if (!defined('WP_CLI') || !WP_CLI || !class_exists('WP_CLI')) {
            return;
        }

        WP_CLI::add_command('schemaweave', self::class);
    }

    /**
     * Show SchemaWeave plugin status.
     *
     * ## EXAMPLES
     *
     *     wp schemaweave status
     */
    public function status(array $args, array $assocArgs): void
    {
        $settings = SchemaWeave_WordPress_Settings::get();
        $rows = [
            ['field' => 'version', 'value' => SCHEMAWEAVE_VERSION],
            ['field' => 'enabled', 'value' => !empty($settings['enabled']) ? 'yes' : 'no'],
            ['field' => 'wordpress', 'value' => (string) get_bloginfo('version')],
            ['field' => 'php', 'value' => PHP_VERSION],
            ['field' => 'woocommerce', 'value' => defined('WC_VERSION') ? (string) WC_VERSION : 'not detected'],
        ];

        WP_CLI\Utils\format_items('table', $rows, ['field', 'value']);
    }

    /**
     * Print generated JSON-LD for a post.
     *
     * ## OPTIONS
     *
     * <post-id>
     * : WordPress post ID to inspect.
     *
     * ## EXAMPLES
     *
     *     wp schemaweave inspect 42
     */
    public function inspect(array $args, array $assocArgs): void
    {
        $postId = isset($args[0]) ? absint($args[0]) : 0;
        if ($postId <= 0 || !get_post($postId)) {
            WP_CLI::error('A valid WordPress post ID is required.');
        }

        $document = SchemaWeave_WordPress_Schema_Bridge::buildDocumentForPost($postId);
        if (empty($document)) {
            WP_CLI::warning('No SchemaWeave document is generated for this content.');
            return;
        }

        $json = wp_json_encode(
            $document,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        WP_CLI::line((string) $json);
    }

    /**
     * Validate generated SchemaWeave JSON-LD for a post.
     *
     * ## OPTIONS
     *
     * <post-id>
     * : WordPress post ID to validate.
     *
     * ## EXAMPLES
     *
     *     wp schemaweave validate 42
     */
    public function validate(array $args, array $assocArgs): void
    {
        $postId = isset($args[0]) ? absint($args[0]) : 0;
        if ($postId <= 0 || !get_post($postId)) {
            WP_CLI::error('A valid WordPress post ID is required.');
        }

        $document = SchemaWeave_WordPress_Schema_Bridge::buildDocumentForPost($postId);
        if (empty($document)) {
            WP_CLI::error('No SchemaWeave document is generated for this content.');
        }

        $validator = new SchemaWeave\GraphValidator();
        $issues = $validator->validate($document);
        if (empty($issues)) {
            WP_CLI::success('No internal graph validation issues found.');
            return;
        }

        WP_CLI\Utils\format_items('table', $issues, ['severity', 'code', 'message']);
        if ($validator->hasErrors($issues)) {
            WP_CLI::halt(1);
        }
    }
}
