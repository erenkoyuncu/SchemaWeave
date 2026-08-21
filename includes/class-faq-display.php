<?php
final class SchemaWeave_WordPress_FAQ_Display
{
    public const SHORTCODE = 'schemaweave_faq';
    /** @var array<int,bool> */
    private static array $autoAppended = [];

    public static function boot(): void
    {
        add_shortcode(self::SHORTCODE, [self::class, 'shortcode']);
        add_filter('the_content', [self::class, 'appendToContent'], 20);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue']);
    }

    public static function appendToContent(string $content): string
    {
        if (is_admin() || is_feed() || !is_singular() || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        $postId = (int) get_the_ID();
        if ($postId <= 0) {
            return $content;
        }

        $settings = SchemaWeave_WordPress_Settings::get();
        if (!self::isAutoAppendMode($settings) || isset(self::$autoAppended[$postId])) {
            return $content;
        }

        $post = get_post($postId);
        if ($post && has_shortcode((string) $post->post_content, self::SHORTCODE)) {
            return $content;
        }

        $faq = self::savedFaq($postId);
        if (!self::isVisible($postId, $faq, $settings, $post)) {
            return $content;
        }

        $html = self::renderHtml($faq, $settings, $postId);
        if ($html === '') {
            return $content;
        }

        self::$autoAppended[$postId] = true;
        return $content . $html;
    }

    public static function shortcode($atts = []): string
    {
        if (is_admin() || !is_singular()) {
            return '';
        }

        $postId = (int) get_the_ID();
        if ($postId <= 0) {
            return '';
        }

        $settings = SchemaWeave_WordPress_Settings::get();
        $faq = self::savedFaq($postId);
        if (!self::isVisible($postId, $faq, $settings, get_post($postId))) {
            return '';
        }

        return self::renderHtml($faq, $settings, $postId);
    }

    public static function enqueue(): void
    {
        if (is_admin() || !is_singular()) {
            return;
        }

        $postId = (int) get_queried_object_id();
        if ($postId <= 0) {
            return;
        }

        $settings = SchemaWeave_WordPress_Settings::get();
        $faq = self::savedFaq($postId);
        $post = get_post($postId);
        if (!self::isVisible($postId, $faq, $settings, $post)) {
            return;
        }

        wp_enqueue_style(
            'schemaweave-faq',
            SCHEMAWEAVE_URL . 'assets/faq.css',
            [],
            SCHEMAWEAVE_VERSION
        );
    }

    /**
     * Return FAQ rows that may safely be included in JSON-LD for a post.
     */
    public static function schemaItems(int $postId, array $faq, array $settings, $post = null): array
    {
        return self::isVisible($postId, $faq, $settings, $post, false) ? $faq : [];
    }

    public static function isVisible(int $postId, array $faq, array $settings, $post = null, bool $respectSavedDisabled = true): bool
    {
        if ($postId <= 0 || empty($faq) || empty($settings['schemas']['faq'])) {
            return false;
        }

        if ($respectSavedDisabled) {
            $meta = SchemaWeave_WordPress_Post_Meta::getSaved($postId);
            if (!empty($meta['disabled'])) {
                return false;
            }
        }

        $mode = (string) ($settings['faq_display']['mode'] ?? 'auto_append');
        $visible = false;

        if ($mode === 'auto_append') {
            $visible = true;
        } elseif ($mode === 'shortcode') {
            if (!$post) {
                $post = get_post($postId);
            }
            $visible = $post && has_shortcode((string) ($post->post_content ?? ''), self::SHORTCODE);
        }

        /**
         * Allow integrations that render these exact FAQ rows outside the plugin.
         * Returning true means the integrator guarantees the FAQ content is visible
         * to normal visitors on the canonical page.
         */
        return (bool) apply_filters(
            'schemaweave_faq_is_visible',
            $visible,
            $postId,
            $faq,
            $settings
        );
    }

    private static function isAutoAppendMode(array $settings): bool
    {
        return (string) ($settings['faq_display']['mode'] ?? 'auto_append') === 'auto_append';
    }

    private static function savedFaq(int $postId): array
    {
        $meta = SchemaWeave_WordPress_Post_Meta::getSaved($postId);
        return isset($meta['faq']) && is_array($meta['faq']) ? $meta['faq'] : [];
    }

    private static function renderHtml(array $faq, array $settings, int $postId): string
    {
        if (empty($faq)) {
            return '';
        }

        $heading = trim((string) ($settings['faq_display']['heading'] ?? 'Frequently Asked Questions'));
        $headingId = 'schemaweave-faq-title-' . $postId;

        ob_start();
        ?>
        <section class="schemaweave-faq"<?php echo $heading !== '' ? ' aria-labelledby="' . esc_attr($headingId) . '"' : ' aria-label="' . esc_attr__('Frequently Asked Questions', 'schemaweave') . '"'; ?>>
            <?php if ($heading !== '') { ?>
                <h2 id="<?php echo esc_attr($headingId); ?>" class="schemaweave-faq__title"><?php echo esc_html($heading); ?></h2>
            <?php } ?>
            <div class="schemaweave-faq__items">
                <?php foreach ($faq as $row) {
                    $question = trim((string) ($row['question'] ?? ''));
                    $answer = trim((string) ($row['answer'] ?? ''));
                    if ($question === '' || $answer === '') {
                        continue;
                    }
                    ?>
                    <details class="schemaweave-faq__item">
                        <summary class="schemaweave-faq__question"><?php echo esc_html($question); ?></summary>
                        <div class="schemaweave-faq__answer"><?php echo wp_kses_post(wpautop(esc_html($answer))); ?></div>
                    </details>
                <?php } ?>
            </div>
        </section>
        <?php
        return (string) ob_get_clean();
    }
}
