<?php
use SchemaWeave\Contracts\DataProviderInterface;

final class SchemaWeave_WordPress_Data_Provider implements DataProviderInterface
{
    public function getBreadcrumbItems(array $page): array
    {
        $items = [[
            'name' => get_bloginfo('name'),
            'url' => home_url('/'),
        ]];

        $postId = !empty($page['id']) ? (int) $page['id'] : 0;
        if ($postId > 0) {
            $postType = (string) get_post_type($postId);
            $postTypeObject = $postType !== '' ? get_post_type_object($postType) : null;

            if ($postTypeObject && !empty($postTypeObject->has_archive)) {
                $archiveUrl = get_post_type_archive_link($postType);
                if ($archiveUrl) {
                    $items[] = [
                        'name' => (string) $postTypeObject->labels->name,
                        'url' => (string) $archiveUrl,
                    ];
                }
            }

            if ($postTypeObject && !empty($postTypeObject->hierarchical)) {
                $ancestorIds = array_reverse(get_post_ancestors($postId));
                foreach ($ancestorIds as $ancestorId) {
                    $ancestorUrl = get_permalink((int) $ancestorId);
                    if ($ancestorUrl) {
                        $items[] = [
                            'name' => get_the_title((int) $ancestorId),
                            'url' => (string) $ancestorUrl,
                        ];
                    }
                }
            } elseif ($postType === 'product' && taxonomy_exists('product_cat')) {
                $items = array_merge($items, $this->productCategoryBreadcrumbs($postId));
            } elseif ($postType === 'post') {
                $items = array_merge($items, $this->postCategoryBreadcrumbs($postId));
            }
        }

        if (!empty($page['name']) && !empty($page['url'])) {
            $last = end($items);
            if (!is_array($last) || ($last['url'] ?? '') !== $page['url']) {
                $items[] = [
                    'name' => (string) $page['name'],
                    'url' => (string) $page['url'],
                ];
            }
        }

        return $this->deduplicateByUrl($items);
    }

    public function getCollectionItems(array $page): array
    {
        global $wp_query;

        if (!isset($wp_query) || empty($wp_query->posts) || !is_array($wp_query->posts)) {
            return [];
        }

        $items = [];
        foreach ($wp_query->posts as $post) {
            if (!is_object($post) || empty($post->ID)) {
                continue;
            }

            $url = get_permalink((int) $post->ID);
            if (!$url) {
                continue;
            }

            $items[] = [
                'name' => get_the_title((int) $post->ID),
                'url' => (string) $url,
            ];
        }

        return $items;
    }

    public function getFaqItems(array $page): array
    {
        if (empty($page['id'])) {
            return [];
        }

        $rows = get_post_meta((int) $page['id'], '_schemaweave_faq', true);
        return is_array($rows) ? $rows : [];
    }

    public function getRelatedItems(array $page): array
    {
        return [];
    }

    public function getProductImages(array $page): array
    {
        if (empty($page['id'])) {
            return [];
        }

        $images = [];
        $featured = get_the_post_thumbnail_url((int) $page['id'], 'full');
        if ($featured) {
            $images[] = $featured;
        }

        if (function_exists('wc_get_product')) {
            $product = wc_get_product((int) $page['id']);
            if ($product) {
                foreach ($product->get_gallery_image_ids() as $imageId) {
                    $url = wp_get_attachment_image_url($imageId, 'full');
                    if ($url) {
                        $images[] = $url;
                    }
                }
            }
        }

        return array_values(array_unique($images));
    }

    private function postCategoryBreadcrumbs(int $postId): array
    {
        $categories = get_the_category($postId);
        if (empty($categories)) {
            return [];
        }

        $category = $categories[0];
        $rows = [];
        $ancestors = array_reverse(get_ancestors((int) $category->term_id, 'category', 'taxonomy'));
        $ancestors[] = (int) $category->term_id;

        foreach ($ancestors as $termId) {
            $term = get_term((int) $termId, 'category');
            if (!$term || is_wp_error($term)) {
                continue;
            }
            $url = get_term_link($term);
            if (!is_wp_error($url)) {
                $rows[] = ['name' => $term->name, 'url' => (string) $url];
            }
        }

        return $rows;
    }

    private function productCategoryBreadcrumbs(int $postId): array
    {
        $terms = get_the_terms($postId, 'product_cat');
        if (!is_array($terms) || empty($terms)) {
            return [];
        }

        usort($terms, static function ($left, $right): int {
            $leftDepth = count(get_ancestors((int) $left->term_id, 'product_cat', 'taxonomy'));
            $rightDepth = count(get_ancestors((int) $right->term_id, 'product_cat', 'taxonomy'));
            return $rightDepth <=> $leftDepth;
        });

        $term = $terms[0];
        $termIds = array_reverse(get_ancestors((int) $term->term_id, 'product_cat', 'taxonomy'));
        $termIds[] = (int) $term->term_id;
        $rows = [];

        foreach ($termIds as $termId) {
            $category = get_term((int) $termId, 'product_cat');
            if (!$category || is_wp_error($category)) {
                continue;
            }
            $url = get_term_link($category);
            if (!is_wp_error($url)) {
                $rows[] = ['name' => $category->name, 'url' => (string) $url];
            }
        }

        return $rows;
    }

    private function deduplicateByUrl(array $items): array
    {
        $seen = [];
        $clean = [];
        foreach ($items as $item) {
            if (!is_array($item) || empty($item['url'])) {
                continue;
            }
            $url = (string) $item['url'];
            if (isset($seen[$url])) {
                continue;
            }
            $seen[$url] = true;
            $clean[] = $item;
        }

        return $clean;
    }
}
