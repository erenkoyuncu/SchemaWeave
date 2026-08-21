<?php
final class SchemaWeave_WordPress_Diagnostics
{
    public const PAGE_SLUG = 'schemaweave-diagnostics';

    public static function boot(): void
    {
        add_action('admin_menu', [self::class, 'addMenu']);
    }

    public static function addMenu(): void
    {
        add_options_page(
            __('SchemaWeave Diagnostics', 'schemaweave'),
            __('SchemaWeave Diagnostics', 'schemaweave'),
            'manage_options',
            self::PAGE_SLUG,
            [self::class, 'render']
        );
    }

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = SchemaWeave_WordPress_Settings::get();
        $overlaps = self::potentialOverlapPlugins();
        $inspectPostId = isset($_GET['inspect_post']) ? absint($_GET['inspect_post']) : 0;
        $document = $inspectPostId > 0
            ? SchemaWeave_WordPress_Schema_Bridge::buildDocumentForPost($inspectPostId)
            : [];
        $validationIssues = !empty($document)
            ? (new \SchemaWeave\GraphValidator())->validate($document)
            : [];
        $posts = self::inspectablePosts();
        ?>
        <div class="wrap schemaweave-admin">
            <div class="schemaweave-hero">
                <div>
                    <p class="schemaweave-eyebrow">Schema.org JSON-LD</p>
                    <h1><?php esc_html_e('SchemaWeave Diagnostics', 'schemaweave'); ?></h1>
                    <p><?php esc_html_e('Inspect the environment, review potential overlap, and preview generated graph output.', 'schemaweave'); ?></p>
                </div>
                <span class="schemaweave-version">v<?php echo esc_html(SCHEMAWEAVE_VERSION); ?></span>
            </div>

            <section class="schemaweave-card">
                <div class="schemaweave-card-heading">
                    <div>
                        <h2><?php esc_html_e('Environment', 'schemaweave'); ?></h2>
                        <p><?php esc_html_e('Runtime information useful when reporting integration issues.', 'schemaweave'); ?></p>
                    </div>
                </div>
                <table class="widefat striped schemaweave-diagnostics-table">
                    <tbody>
                        <?php foreach (self::environmentRows() as $label => $value) { ?>
                            <tr><th><?php echo esc_html($label); ?></th><td><code><?php echo esc_html($value); ?></code></td></tr>
                        <?php } ?>
                    </tbody>
                </table>
            </section>

            <section class="schemaweave-card">
                <div class="schemaweave-card-heading">
                    <div>
                        <h2><?php esc_html_e('Potential Schema Overlap', 'schemaweave'); ?></h2>
                        <p><?php esc_html_e('This is a conservative compatibility warning, not proof of duplicate output.', 'schemaweave'); ?></p>
                    </div>
                </div>
                <?php if (empty($overlaps)) { ?>
                    <div class="notice notice-success inline"><p><?php esc_html_e('No commonly known schema/SEO plugin overlap was detected among active plugins.', 'schemaweave'); ?></p></div>
                <?php } else { ?>
                    <div class="notice notice-warning inline"><p><?php esc_html_e('One or more active plugins may also emit structured data. Review entity ownership before enabling overlapping SchemaWeave types.', 'schemaweave'); ?></p></div>
                    <ul class="schemaweave-diagnostic-list">
                        <?php foreach ($overlaps as $plugin) { ?>
                            <li><strong><?php echo esc_html($plugin['name']); ?></strong> <code><?php echo esc_html($plugin['file']); ?></code></li>
                        <?php } ?>
                    </ul>
                <?php } ?>

                <h3><?php esc_html_e('Currently enabled SchemaWeave entity groups', 'schemaweave'); ?></h3>
                <p><code><?php echo esc_html(implode(', ', self::enabledSchemas($settings))); ?></code></p>
            </section>

            <section class="schemaweave-card">
                <div class="schemaweave-card-heading">
                    <div>
                        <h2><?php esc_html_e('Schema Inspector', 'schemaweave'); ?></h2>
                        <p><?php esc_html_e('Generate the current saved SchemaWeave graph for a published WordPress content item.', 'schemaweave'); ?></p>
                    </div>
                </div>

                <form method="get" action="<?php echo esc_url(admin_url('options-general.php')); ?>" class="schemaweave-inspector-form">
                    <input type="hidden" name="page" value="<?php echo esc_attr(self::PAGE_SLUG); ?>">
                    <select name="inspect_post">
                        <option value="0"><?php esc_html_e('Select content…', 'schemaweave'); ?></option>
                        <?php foreach ($posts as $post) { ?>
                            <option value="<?php echo esc_attr((string) $post->ID); ?>" <?php selected($inspectPostId, (int) $post->ID); ?>>
                                <?php echo esc_html(get_post_type_object($post->post_type)->labels->singular_name . ': ' . get_the_title($post)); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <button type="submit" class="button button-primary"><?php esc_html_e('Inspect JSON-LD', 'schemaweave'); ?></button>
                </form>

                <?php if ($inspectPostId > 0) { ?>
                    <?php if (empty($document)) { ?>
                        <div class="notice notice-info inline"><p><?php esc_html_e('No SchemaWeave document is generated for this content with the current settings.', 'schemaweave'); ?></p></div>
                    <?php } else { ?>
                        <?php if (empty($validationIssues)) { ?>
                            <div class="notice notice-success inline"><p><?php esc_html_e('Internal graph validation found no structural issues.', 'schemaweave'); ?></p></div>
                        <?php } else { ?>
                            <div class="schemaweave-validation-results">
                                <h3><?php esc_html_e('Internal validation findings', 'schemaweave'); ?></h3>
                                <ul class="schemaweave-diagnostic-list">
                                    <?php foreach ($validationIssues as $issue) { ?>
                                        <li><strong><?php echo esc_html(strtoupper((string) ($issue['severity'] ?? 'info'))); ?></strong> <code><?php echo esc_html((string) ($issue['code'] ?? '')); ?></code> — <?php echo esc_html((string) ($issue['message'] ?? '')); ?></li>
                                    <?php } ?>
                                </ul>
                                <p class="description"><?php esc_html_e('This lightweight validator checks SchemaWeave graph integrity. It does not replace Schema.org or search-engine rich-result validation tools.', 'schemaweave'); ?></p>
                            </div>
                        <?php } ?>
                        <textarea class="large-text code schemaweave-diagnostics-json" rows="24" readonly><?php echo esc_textarea((string) wp_json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></textarea>
                    <?php } ?>
                <?php } ?>
            </section>
        </div>
        <?php
    }

    private static function environmentRows(): array
    {
        global $wp_version;

        $woocommerce = defined('WC_VERSION') ? (string) WC_VERSION : 'not detected';

        return [
            'SchemaWeave' => SCHEMAWEAVE_VERSION,
            'WordPress' => (string) $wp_version,
            'PHP' => PHP_VERSION,
            'WooCommerce' => $woocommerce,
            'Site URL' => site_url('/'),
            'Home URL' => home_url('/'),
            'Permalink structure' => (string) get_option('permalink_structure', ''),
            'HTTPS' => is_ssl() ? 'yes' : 'no',
            'Multisite' => is_multisite() ? 'yes' : 'no',
        ];
    }

    private static function potentialOverlapPlugins(): array
    {
        $active = (array) get_option('active_plugins', []);
        if (is_multisite()) {
            $active = array_merge($active, array_keys((array) get_site_option('active_sitewide_plugins', [])));
        }
        $active = array_values(array_unique(array_map('strval', $active)));

        $known = [
            'wordpress-seo/' => 'Yoast SEO',
            'seo-by-rank-math/' => 'Rank Math SEO',
            'all-in-one-seo-pack/' => 'All in One SEO',
            'wp-seopress/' => 'SEOPress',
            'schema-and-structured-data-for-wp/' => 'Schema & Structured Data for WP',
            'schema-pro/' => 'Schema Pro',
        ];

        $matches = [];
        foreach ($active as $pluginFile) {
            foreach ($known as $needle => $name) {
                if (strpos($pluginFile, $needle) !== false) {
                    $matches[] = ['name' => $name, 'file' => $pluginFile];
                    break;
                }
            }
        }

        return $matches;
    }

    private static function enabledSchemas(array $settings): array
    {
        $labels = [
            'organization' => 'Organization',
            'website' => 'WebSite',
            'local_business' => 'LocalBusiness',
            'webpage' => 'WebPage',
            'breadcrumb' => 'BreadcrumbList',
            'product' => 'Product',
            'blog_posting' => 'BlogPosting',
            'item_list' => 'ItemList',
            'faq' => 'FAQPage',
            'related' => 'Related',
        ];

        $enabled = [];
        foreach ($labels as $key => $label) {
            if (!empty($settings['schemas'][$key])) {
                $enabled[] = $label;
            }
        }

        return $enabled;
    }

    private static function inspectablePosts(): array
    {
        $postTypes = get_post_types(['public' => true], 'names');
        unset($postTypes['attachment']);

        return get_posts([
            'post_type' => array_values($postTypes),
            'post_status' => 'publish',
            'posts_per_page' => 100,
            'orderby' => 'modified',
            'order' => 'DESC',
            'suppress_filters' => false,
        ]);
    }
}
