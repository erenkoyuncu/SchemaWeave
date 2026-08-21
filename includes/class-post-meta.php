<?php
final class SchemaWeave_WordPress_Post_Meta
{
    public const NONCE_ACTION = 'schemaweave_post_meta';
    public const NONCE_NAME = 'schemaweave_post_meta_nonce';
    public const PREVIEW_NONCE_ACTION = 'schemaweave_preview';

    private const META_DISABLED = '_schemaweave_disabled';
    private const META_TYPE = '_schemaweave_type_override';
    private const META_PAGE_TYPE = '_schemaweave_page_type_override';
    private const META_IMAGE = '_schemaweave_image';
    private const META_BRAND = '_schemaweave_brand';
    private const META_DESCRIPTION = '_schemaweave_description';
    private const META_FAQ = '_schemaweave_faq';

    public static function boot(): void
    {
        add_action('add_meta_boxes', [self::class, 'registerMetaBox'], 20, 2);
        add_action('save_post', [self::class, 'save'], 10, 2);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue']);
        add_action('wp_ajax_schemaweave_preview', [self::class, 'ajaxPreview']);
    }

    public static function registerMetaBox(string $postType, $post): void
    {
        if ($postType === 'attachment' || !post_type_exists($postType)) {
            return;
        }

        $object = get_post_type_object($postType);
        if (!$object || empty($object->public)) {
            return;
        }

        add_meta_box(
            'schemaweave-meta',
            __('SchemaWeave', 'schemaweave'),
            [self::class, 'render'],
            $postType,
            'normal',
            'high'
        );
    }

    public static function enqueue(string $hook): void
    {
        if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }

        $screen = get_current_screen();
        if (!$screen || $screen->base !== 'post') {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style(
            'schemaweave-post-meta',
            SCHEMAWEAVE_URL . 'assets/post-meta.css',
            [],
            SCHEMAWEAVE_VERSION
        );

        wp_enqueue_script(
            'schemaweave-post-meta',
            SCHEMAWEAVE_URL . 'assets/post-meta.js',
            ['jquery'],
            SCHEMAWEAVE_VERSION,
            true
        );

        wp_localize_script(
            'schemaweave-post-meta',
            'SchemaWeaveEditor',
            [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'previewNonce' => wp_create_nonce(self::PREVIEW_NONCE_ACTION),
                'mediaTitle' => __('Select schema image', 'schemaweave'),
                'mediaButton' => __('Use this image', 'schemaweave'),
                'previewing' => __('Generating preview…', 'schemaweave'),
                'previewError' => __('Preview could not be generated.', 'schemaweave'),
            ]
        );
    }

    public static function render($post): void
    {
        $postId = isset($post->ID) ? (int) $post->ID : 0;
        $values = self::getSaved($postId);
        $settings = SchemaWeave_WordPress_Settings::get();
        $mapping = self::mappingLabel((string) ($post->post_type ?? ''), $settings);

        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);
        ?>
        <div class="schemaweave-editor" data-schemaweave-editor data-post-id="<?php echo esc_attr((string) $postId); ?>">
            <div class="schemaweave-editor-head">
                <div>
                    <strong><?php esc_html_e('Structured data controls', 'schemaweave'); ?></strong>
                    <p><?php
                    /* translators: %s: current global SchemaWeave mapping label. */
                    echo esc_html(sprintf(__('Global mapping: %s. Use overrides only when this content needs different schema behavior.', 'schemaweave'), $mapping));
                    ?></p>
                </div>
                <button type="button" class="button button-secondary" data-schemaweave-preview><?php esc_html_e('Preview JSON-LD', 'schemaweave'); ?></button>
            </div>

            <label class="schemaweave-editor-toggle">
                <input type="checkbox" name="schemaweave_meta[disabled]" value="1" <?php checked(!empty($values['disabled'])); ?>>
                <span>
                    <strong><?php esc_html_e('Disable SchemaWeave on this content', 'schemaweave'); ?></strong>
                    <small><?php esc_html_e('No SchemaWeave JSON-LD will be emitted for this singular URL.', 'schemaweave'); ?></small>
                </span>
            </label>

            <div class="schemaweave-editor-grid">
                <label class="schemaweave-editor-field">
                    <span><?php esc_html_e('Entity mapping override', 'schemaweave'); ?></span>
                    <select name="schemaweave_meta[type]">
                        <?php foreach (self::typeOptions() as $value => $label) { ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($values['type'], $value); ?>><?php echo esc_html($label); ?></option>
                        <?php } ?>
                    </select>
                </label>

                <label class="schemaweave-editor-field">
                    <span><?php esc_html_e('Web page type', 'schemaweave'); ?></span>
                    <select name="schemaweave_meta[page_type]">
                        <?php foreach (self::pageTypeOptions() as $value => $label) { ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($values['page_type'], $value); ?>><?php echo esc_html($label); ?></option>
                        <?php } ?>
                    </select>
                </label>

                <label class="schemaweave-editor-field schemaweave-editor-span-2">
                    <span><?php esc_html_e('Schema description override', 'schemaweave'); ?></span>
                    <textarea rows="3" name="schemaweave_meta[description]" placeholder="<?php esc_attr_e('Leave empty to use the post excerpt/content.', 'schemaweave'); ?>"><?php echo esc_textarea($values['description']); ?></textarea>
                </label>

                <div class="schemaweave-editor-field schemaweave-editor-span-2">
                    <span><?php esc_html_e('Schema image override', 'schemaweave'); ?></span>
                    <div class="schemaweave-media-row">
                        <input type="url" name="schemaweave_meta[image]" value="<?php echo esc_attr($values['image']); ?>" placeholder="https://example.com/image.jpg" data-schemaweave-image-input>
                        <button type="button" class="button" data-schemaweave-media><?php esc_html_e('Choose image', 'schemaweave'); ?></button>
                        <button type="button" class="button-link-delete" data-schemaweave-clear-image><?php esc_html_e('Clear', 'schemaweave'); ?></button>
                    </div>
                </div>

                <label class="schemaweave-editor-field schemaweave-editor-span-2">
                    <span><?php esc_html_e('Product brand override', 'schemaweave'); ?></span>
                    <input type="text" name="schemaweave_meta[brand]" value="<?php echo esc_attr($values['brand']); ?>" placeholder="<?php esc_attr_e('Only used when this content resolves to Product.', 'schemaweave'); ?>">
                </label>
            </div>

            <div class="schemaweave-faq-wrap">
                <div class="schemaweave-editor-section-head">
                    <div>
                        <strong><?php esc_html_e('FAQPage', 'schemaweave'); ?></strong>
                        <p><?php esc_html_e('Only add questions and answers that are visibly available to users on this page.', 'schemaweave'); ?></p>
                    </div>
                    <button type="button" class="button" data-schemaweave-add-faq><?php esc_html_e('Add FAQ', 'schemaweave'); ?></button>
                </div>

                <div data-schemaweave-faq-list>
                    <?php foreach ($values['faq'] as $index => $row) { self::renderFaqRow((int) $index, $row); } ?>
                </div>

                <template data-schemaweave-faq-template>
                    <?php self::renderFaqRow('__INDEX__', ['question' => '', 'answer' => '']); ?>
                </template>
            </div>

            <div class="schemaweave-preview" data-schemaweave-preview-wrap hidden>
                <div class="schemaweave-editor-section-head">
                    <div>
                        <strong><?php esc_html_e('JSON-LD preview', 'schemaweave'); ?></strong>
                        <p><?php esc_html_e('Preview includes unsaved SchemaWeave fields from this panel. WordPress title/content values come from the currently saved post.', 'schemaweave'); ?></p>
                    </div>
                </div>
                <pre data-schemaweave-preview-output></pre>
            </div>
        </div>
        <?php
    }

    public static function save(int $postId, $post): void
    {
        if (!isset($_POST[self::NONCE_NAME])) {
            return;
        }

        $nonce = sanitize_text_field((string) wp_unslash($_POST[self::NONCE_NAME]));
        if (!wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return;
        }

        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_autosave($postId) || wp_is_post_revision($postId)) {
            return;
        }

        if (is_multisite() && function_exists('ms_is_switched') && ms_is_switched()) {
            return;
        }

        if (!current_user_can('edit_post', $postId)) {
            return;
        }

        $raw = [];
        if (isset($_POST['schemaweave_meta']) && is_array($_POST['schemaweave_meta'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized field-by-field by sanitizePayload() before persistence.
            $raw = wp_unslash($_POST['schemaweave_meta']);
        }

        self::persist($postId, self::sanitizePayload($raw));
    }

    public static function ajaxPreview(): void
    {
        check_ajax_referer(self::PREVIEW_NONCE_ACTION, 'nonce');

        $postId = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        if ($postId <= 0 || !current_user_can('edit_post', $postId)) {
            wp_send_json_error(['message' => __('You are not allowed to preview this content.', 'schemaweave')], 403);
        }

        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON is decoded and sanitized field-by-field by sanitizePayload().
        $rawJson = isset($_POST['meta']) ? (string) wp_unslash($_POST['meta']) : '{}';
        $decoded = json_decode($rawJson, true);
        $payload = self::sanitizePayload(is_array($decoded) ? $decoded : []);

        $document = SchemaWeave_WordPress_Schema_Bridge::buildDocumentForPost($postId, $payload);
        if (empty($document)) {
            wp_send_json_success([
                'json' => __('SchemaWeave output is disabled for this content.', 'schemaweave'),
                'document' => [],
            ]);
        }

        $json = wp_json_encode(
            $document,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        if ($json === false) {
            wp_send_json_error(['message' => __('Could not encode the schema preview.', 'schemaweave')], 500);
        }

        wp_send_json_success([
            'json' => $json,
            'document' => $document,
        ]);
    }

    public static function getSaved(int $postId): array
    {
        $faq = get_post_meta($postId, self::META_FAQ, true);

        return [
            'disabled' => (int) get_post_meta($postId, self::META_DISABLED, true) === 1 ? 1 : 0,
            'type' => self::allowedType((string) get_post_meta($postId, self::META_TYPE, true)),
            'page_type' => self::allowedPageType((string) get_post_meta($postId, self::META_PAGE_TYPE, true)),
            'image' => (string) get_post_meta($postId, self::META_IMAGE, true),
            'brand' => (string) get_post_meta($postId, self::META_BRAND, true),
            'description' => (string) get_post_meta($postId, self::META_DESCRIPTION, true),
            'faq' => is_array($faq) ? self::sanitizeFaq($faq) : [],
        ];
    }

    public static function sanitizePayload(array $raw): array
    {
        return [
            'disabled' => empty($raw['disabled']) ? 0 : 1,
            'type' => self::allowedType((string) ($raw['type'] ?? 'auto')),
            'page_type' => self::allowedPageType((string) ($raw['page_type'] ?? 'auto')),
            'image' => esc_url_raw((string) ($raw['image'] ?? '')),
            'brand' => sanitize_text_field((string) ($raw['brand'] ?? '')),
            'description' => sanitize_textarea_field((string) ($raw['description'] ?? '')),
            'faq' => self::sanitizeFaq(isset($raw['faq']) && is_array($raw['faq']) ? $raw['faq'] : []),
        ];
    }

    private static function persist(int $postId, array $values): void
    {
        self::persistScalar($postId, self::META_DISABLED, !empty($values['disabled']) ? '1' : '');
        self::persistScalar($postId, self::META_TYPE, $values['type'] !== 'auto' ? $values['type'] : '');
        self::persistScalar($postId, self::META_PAGE_TYPE, $values['page_type'] !== 'auto' ? $values['page_type'] : '');
        self::persistScalar($postId, self::META_IMAGE, $values['image']);
        self::persistScalar($postId, self::META_BRAND, $values['brand']);
        self::persistScalar($postId, self::META_DESCRIPTION, $values['description']);

        if (!empty($values['faq'])) {
            update_post_meta($postId, self::META_FAQ, $values['faq']);
        } else {
            delete_post_meta($postId, self::META_FAQ);
        }
    }

    private static function persistScalar(int $postId, string $key, string $value): void
    {
        if ($value === '') {
            delete_post_meta($postId, $key);
            return;
        }

        update_post_meta($postId, $key, $value);
    }

    private static function sanitizeFaq(array $rows): array
    {
        $clean = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $question = sanitize_text_field((string) ($row['question'] ?? ''));
            $answer = sanitize_textarea_field((string) ($row['answer'] ?? ''));
            if ($question === '' || $answer === '') {
                continue;
            }

            $clean[] = [
                'question' => $question,
                'answer' => $answer,
            ];

            if (count($clean) >= 50) {
                break;
            }
        }

        return $clean;
    }

    private static function allowedType(string $value): string
    {
        return array_key_exists($value, self::typeOptions()) ? $value : 'auto';
    }

    private static function allowedPageType(string $value): string
    {
        return array_key_exists($value, self::pageTypeOptions()) ? $value : 'auto';
    }

    private static function typeOptions(): array
    {
        return [
            'auto' => __('Use global post type mapping', 'schemaweave'),
            'page' => __('WebPage', 'schemaweave'),
            'blog_post' => __('BlogPosting', 'schemaweave'),
            'product' => __('Product', 'schemaweave'),
        ];
    }

    private static function pageTypeOptions(): array
    {
        return [
            'auto' => __('Automatic WebPage', 'schemaweave'),
            'WebPage' => 'WebPage',
            'AboutPage' => 'AboutPage',
            'ContactPage' => 'ContactPage',
            'ProfilePage' => 'ProfilePage',
        ];
    }

    private static function mappingLabel(string $postType, array $settings): string
    {
        $mapping = isset($settings['post_type_mappings'][$postType])
            ? (string) $settings['post_type_mappings'][$postType]
            : ($postType === 'post' ? 'blog_post' : ($postType === 'product' ? 'product' : 'page'));

        $labels = [
            'page' => 'WebPage',
            'blog_post' => 'BlogPosting',
            'product' => 'Product',
            'disabled' => __('Disabled', 'schemaweave'),
        ];

        return (string) ($labels[$mapping] ?? 'WebPage');
    }

    private static function renderFaqRow($index, array $row): void
    {
        ?>
        <div class="schemaweave-faq-row" data-schemaweave-faq-row>
            <div class="schemaweave-faq-fields">
                <label class="schemaweave-editor-field">
                    <span><?php esc_html_e('Question', 'schemaweave'); ?></span>
                    <input type="text" name="schemaweave_meta[faq][<?php echo esc_attr((string) $index); ?>][question]" value="<?php echo esc_attr((string) ($row['question'] ?? '')); ?>">
                </label>
                <label class="schemaweave-editor-field">
                    <span><?php esc_html_e('Answer', 'schemaweave'); ?></span>
                    <textarea rows="3" name="schemaweave_meta[faq][<?php echo esc_attr((string) $index); ?>][answer]"><?php echo esc_textarea((string) ($row['answer'] ?? '')); ?></textarea>
                </label>
            </div>
            <button type="button" class="button-link-delete" data-schemaweave-remove-faq><?php esc_html_e('Remove FAQ', 'schemaweave'); ?></button>
        </div>
        <?php
    }
}
