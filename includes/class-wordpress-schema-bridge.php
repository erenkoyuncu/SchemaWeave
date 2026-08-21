<?php
use SchemaWeave\Config;
use SchemaWeave\SchemaEngine;

final class SchemaWeave_WordPress_Schema_Bridge
{
    public static function render(): void
    {
        if (is_admin() || is_feed() || is_404()) {
            return;
        }

        $settings = SchemaWeave_WordPress_Settings::get();
        if (empty($settings['enabled'])) {
            return;
        }

        $page = self::currentPage($settings);
        if (!$page) {
            return;
        }

        $document = self::generateDocument($page, $settings);
        if (empty($document['@graph']) || !is_array($document['@graph'])) {
            return;
        }

        $json = wp_json_encode(
            $document,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
        );

        if ($json === false || $json === '') {
            return;
        }

        echo "\n<script type=\"application/ld+json\">" . $json . "</script>\n";
    }

    public static function buildDocumentForPost(int $postId, ?array $overrides = null): array
    {
        $settings = SchemaWeave_WordPress_Settings::get();
        if (empty($settings['enabled'])) {
            return [];
        }

        $post = get_post($postId);
        if (!$post) {
            return [];
        }

        $page = self::pageFromPost($post, $settings, $overrides);
        if (!$page) {
            return [];
        }

        return self::generateDocument($page, $settings);
    }

    private static function generateDocument(array $page, array $settings): array
    {
        $page = apply_filters('schemaweave_page', $page, $settings);
        if (!is_array($page) || empty($page)) {
            return [];
        }

        $config = self::config($settings);
        $config = apply_filters('schemaweave_config', $config, $page, $settings);
        if (!is_array($config)) {
            return [];
        }

        $engine = new SchemaEngine(
            new Config($config),
            new SchemaWeave_WordPress_Data_Provider(),
            new SchemaWeave_WordPress_Url_Resolver()
        );

        $document = $engine->generate($page)->toArray();
        $document = apply_filters('schemaweave_graph', $document, $page, $config);

        return is_array($document) ? $document : [];
    }

    private static function config(array $settings): array
    {
        $organization = isset($settings['organization']) && is_array($settings['organization'])
            ? $settings['organization']
            : [];

        $socialProfiles = isset($settings['social_profiles']) && is_array($settings['social_profiles'])
            ? $settings['social_profiles']
            : [];

        $sameAs = [];
        foreach ($socialProfiles as $url) {
            if (is_string($url) && trim($url) !== '') {
                $sameAs[] = trim($url);
            }
        }
        $organization['same_as'] = array_values(array_unique($sameAs));

        return [
            'enabled' => !empty($settings['enabled']),
            'base_url' => home_url(),
            'site_name' => get_bloginfo('name'),
            'home_name' => get_bloginfo('name'),
            'default_language' => str_replace('_', '-', get_locale()),
            'organization' => $organization,
            'locations' => isset($settings['locations']) && is_array($settings['locations'])
                ? $settings['locations']
                : [],
            'schemas' => isset($settings['schemas']) && is_array($settings['schemas'])
                ? $settings['schemas']
                : [],
        ];
    }

    private static function currentPage(array $settings): ?array
    {
        $objectId = get_queried_object_id();
        $name = wp_get_document_title();

        if (is_singular()) {
            $post = get_post($objectId);
            return $post ? self::pageFromPost($post, $settings) : null;
        }

        if (is_search()) {
            return [
                'type' => 'collection',
                'schema_page_type' => 'SearchResultsPage',
                'name' => $name,
                'url' => (string) get_search_link(get_search_query()),
                'description' => '',
                'language' => str_replace('_', '-', get_locale()),
                'include_locations' => false,
            ];
        }

        if (is_category() || is_tag() || is_tax() || is_author() || is_date() || is_post_type_archive()) {
            return [
                'type' => 'collection',
                'schema_page_type' => 'CollectionPage',
                'name' => $name,
                'url' => self::archiveUrl(),
                'description' => self::archiveDescription(),
                'language' => str_replace('_', '-', get_locale()),
                'include_locations' => false,
            ];
        }

        if (is_front_page() || is_home()) {
            return [
                'type' => 'page',
                'schema_page_type' => 'WebPage',
                'name' => get_bloginfo('name'),
                'url' => home_url('/'),
                'description' => get_bloginfo('description'),
                'language' => str_replace('_', '-', get_locale()),
                'include_locations' => true,
            ];
        }

        return null;
    }

    private static function pageFromPost($post, array $settings, ?array $overrides = null): ?array
    {
        $postId = (int) ($post->ID ?? 0);
        if ($postId <= 0) {
            return null;
        }

        $meta = $overrides !== null
            ? SchemaWeave_WordPress_Post_Meta::sanitizePayload($overrides)
            : SchemaWeave_WordPress_Post_Meta::getSaved($postId);

        if (!empty($meta['disabled'])) {
            return null;
        }

        $mapping = self::mappingForPostType((string) $post->post_type, $settings);
        if (($meta['type'] ?? 'auto') !== 'auto') {
            $mapping = (string) $meta['type'];
        }

        if ($mapping === 'disabled') {
            return null;
        }

        $url = get_permalink($post);
        if (!$url) {
            return null;
        }

        $description = trim((string) ($meta['description'] ?? ''));
        if ($description === '') {
            $description = has_excerpt($post)
                ? get_the_excerpt($post)
                : wp_trim_words(wp_strip_all_tags($post->post_content), 45);
        }

        $image = trim((string) ($meta['image'] ?? ''));
        if ($image === '') {
            $image = (string) (get_the_post_thumbnail_url($post, 'full') ?: '');
        }

        $schemaPageType = (string) ($meta['page_type'] ?? 'auto');
        if ($schemaPageType === 'auto') {
            $schemaPageType = 'WebPage';
        }

        $page = [
            'id' => $postId,
            'type' => $mapping,
            'schema_page_type' => $schemaPageType,
            'name' => get_the_title($post),
            'url' => (string) $url,
            'description' => $description,
            'image' => $image,
            'language' => str_replace('_', '-', get_locale()),
            'date_published' => (string) get_post_time(DATE_ATOM, true, $post),
            'date_modified' => (string) get_post_modified_time(DATE_ATOM, true, $post),
            'include_locations' => self::isFrontPagePost($postId),
            'faq_items' => SchemaWeave_WordPress_FAQ_Display::schemaItems(
                $postId,
                isset($meta['faq']) && is_array($meta['faq']) ? $meta['faq'] : [],
                $settings,
                $post
            ),
        ];

        $authorId = (int) ($post->post_author ?? 0);
        if ($authorId > 0) {
            $authorName = trim((string) get_the_author_meta('display_name', $authorId));
            if ($authorName !== '') {
                $page['author'] = [
                    '@type' => 'Person',
                    'name' => $authorName,
                ];

                $authorUrl = get_author_posts_url($authorId);
                if (is_string($authorUrl) && $authorUrl !== '') {
                    $page['author']['url'] = $authorUrl;
                }
            }
        }

        $brand = trim((string) ($meta['brand'] ?? ''));
        if ($brand !== '') {
            $page['brand'] = $brand;
        }

        if ($mapping === 'product') {
            self::enrichProduct($page, $post, $settings);
        }

        return $page;
    }

    private static function mappingForPostType(string $postType, array $settings): string
    {
        $mappings = isset($settings['post_type_mappings']) && is_array($settings['post_type_mappings'])
            ? $settings['post_type_mappings']
            : [];

        if (isset($mappings[$postType])) {
            return (string) $mappings[$postType];
        }

        if ($postType === 'post') {
            return 'blog_post';
        }
        if ($postType === 'product') {
            return 'product';
        }

        return 'page';
    }

    private static function enrichProduct(array &$page, $post, array $settings): void
    {
        $wooSettings = isset($settings['woocommerce']) && is_array($settings['woocommerce'])
            ? $settings['woocommerce']
            : [];

        if (empty($page['brand'])) {
            $brandMetaKey = (string) ($wooSettings['brand_meta_key'] ?? '_schemaweave_brand');
            if ($brandMetaKey !== '') {
                $brand = get_post_meta((int) $post->ID, $brandMetaKey, true);
                if (is_scalar($brand) && trim((string) $brand) !== '') {
                    $page['brand'] = trim((string) $brand);
                }
            }
        }

        if (!function_exists('wc_get_product')) {
            return;
        }

        $product = wc_get_product((int) $post->ID);
        if (!$product) {
            return;
        }

        $sku = $product->get_sku();
        if ($sku !== '') {
            $page['sku'] = $sku;
        }

        if (!empty($wooSettings['offers'])) {
            if (is_a($product, 'WC_Product_Variable') && method_exists($product, 'get_variation_prices')) {
                $variationPrices = $product->get_variation_prices(false);
                $prices = isset($variationPrices['price']) && is_array($variationPrices['price'])
                    ? array_values(array_filter($variationPrices['price'], static function ($value): bool {
                        return $value !== '' && is_numeric($value);
                    }))
                    : [];

                if (!empty($prices)) {
                    $numericPrices = array_map('floatval', $prices);
                    $lowPrice = min($numericPrices);
                    $highPrice = max($numericPrices);

                    $page['offers'] = [
                        '@type' => 'AggregateOffer',
                        'url' => (string) $page['url'],
                        'priceCurrency' => get_woocommerce_currency(),
                        'lowPrice' => wc_format_decimal($lowPrice, wc_get_price_decimals()),
                        'highPrice' => wc_format_decimal($highPrice, wc_get_price_decimals()),
                        'offerCount' => count($numericPrices),
                        'availability' => $product->is_in_stock()
                            ? 'https://schema.org/InStock'
                            : 'https://schema.org/OutOfStock',
                    ];
                }
            } else {
                $price = $product->get_price();
                if ($price !== '') {
                    $page['offers'] = [
                        '@type' => 'Offer',
                        'url' => (string) $page['url'],
                        'priceCurrency' => get_woocommerce_currency(),
                        'price' => (string) $price,
                        'availability' => $product->is_in_stock()
                            ? 'https://schema.org/InStock'
                            : 'https://schema.org/OutOfStock',
                    ];
                }
            }
        }

        if (!empty($wooSettings['ratings'])) {
            $ratingCount = (int) $product->get_rating_count();
            if ($ratingCount > 0) {
                $average = (string) $product->get_average_rating();
                if ($average !== '') {
                    $page['aggregate_rating'] = [
                        '@type' => 'AggregateRating',
                        'ratingValue' => $average,
                        'ratingCount' => $ratingCount,
                    ];
                }
            }
        }
    }

    private static function isFrontPagePost(int $postId): bool
    {
        return get_option('show_on_front') === 'page'
            && (int) get_option('page_on_front') === $postId;
    }

    private static function archiveUrl(): string
    {
        $object = get_queried_object();
        if (is_category() || is_tag() || is_tax()) {
            $url = get_term_link($object);
            return is_wp_error($url) ? home_url('/') : (string) $url;
        }

        if (is_author() && !empty($object->ID)) {
            return (string) get_author_posts_url((int) $object->ID);
        }

        if (is_date()) {
            if (is_day()) {
                return (string) get_day_link((int) get_query_var('year'), (int) get_query_var('monthnum'), (int) get_query_var('day'));
            }
            if (is_month()) {
                return (string) get_month_link((int) get_query_var('year'), (int) get_query_var('monthnum'));
            }
            return (string) get_year_link((int) get_query_var('year'));
        }

        if (is_search()) {
            return (string) get_search_link(get_search_query());
        }

        if (is_post_type_archive() && !empty($object->name)) {
            $url = get_post_type_archive_link($object->name);
            return $url ? (string) $url : home_url('/');
        }

        return home_url('/');
    }

    private static function archiveDescription(): string
    {
        if (is_category() || is_tag() || is_tax()) {
            return wp_strip_all_tags((string) term_description());
        }

        return '';
    }
}
