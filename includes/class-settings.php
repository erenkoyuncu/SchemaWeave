<?php
final class SchemaWeave_WordPress_Settings
{
    public const OPTION = 'schemaweave_settings';
    public const PAGE_SLUG = 'schemaweave';

    public static function boot(): void
    {
        add_action('admin_menu', [self::class, 'addMenu']);
        add_action('admin_init', [self::class, 'register']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue']);
    }

    public static function activate(): void
    {
        $current = get_option(self::OPTION, null);
        if ($current === null || $current === false) {
            add_option(self::OPTION, self::defaults());
        }
    }

    public static function defaults(): array
    {
        return [
            'enabled' => 1,
            'organization' => [
                'type' => 'Organization',
                'name' => get_bloginfo('name'),
                'alternate_name' => '',
                'description' => get_bloginfo('description'),
                'email' => get_option('admin_email'),
                'telephone' => '',
                'logo' => get_site_icon_url(),
            ],
            'social_profiles' => [
                'facebook' => '',
                'instagram' => '',
                'linkedin' => '',
                'youtube' => '',
                'x' => '',
                'tiktok' => '',
            ],
            'locations' => [],
            'schemas' => [
                'organization' => 1,
                'website' => 1,
                'local_business' => 1,
                'webpage' => 1,
                'breadcrumb' => 1,
                'product' => 1,
                'blog_posting' => 1,
                'item_list' => 1,
                'faq' => 1,
                'related' => 1,
            ],
            'post_type_mappings' => [
                'page' => 'page',
                'post' => 'blog_post',
                'product' => 'product',
            ],
            'faq_display' => [
                'mode' => 'auto_append',
                'heading' => 'Frequently Asked Questions',
            ],
            'woocommerce' => [
                'offers' => 1,
                'ratings' => 1,
                'brand_meta_key' => '_schemaweave_brand',
            ],
            'advanced' => [
                'delete_data_on_uninstall' => 0,
            ],
        ];
    }

    public static function get(): array
    {
        $saved = get_option(self::OPTION, []);
        if (!is_array($saved)) {
            $saved = [];
        }

        return array_replace_recursive(self::defaults(), $saved);
    }

    public static function register(): void
    {
        register_setting(
            'schemaweave_settings_group',
            self::OPTION,
            [
                'type' => 'array',
                'sanitize_callback' => [self::class, 'sanitize'],
                'default' => self::defaults(),
            ]
        );
    }

    public static function addMenu(): void
    {
        add_options_page(
            __('SchemaWeave', 'schemaweave'),
            __('SchemaWeave', 'schemaweave'),
            'manage_options',
            self::PAGE_SLUG,
            [self::class, 'render']
        );
    }

    public static function enqueue(string $hook): void
    {
        $allowedHooks = [
            'settings_page_' . self::PAGE_SLUG,
            'settings_page_' . SchemaWeave_WordPress_Diagnostics::PAGE_SLUG,
        ];

        if (!in_array($hook, $allowedHooks, true)) {
            return;
        }

        wp_enqueue_style(
            'schemaweave-admin',
            SCHEMAWEAVE_URL . 'assets/admin.css',
            [],
            SCHEMAWEAVE_VERSION
        );

        wp_enqueue_script(
            'schemaweave-admin',
            SCHEMAWEAVE_URL . 'assets/admin.js',
            [],
            SCHEMAWEAVE_VERSION,
            true
        );
    }

    public static function sanitize($input): array
    {
        $input = is_array($input) ? $input : [];
        $clean = self::defaults();

        $clean['enabled'] = empty($input['enabled']) ? 0 : 1;

        $organization = isset($input['organization']) && is_array($input['organization'])
            ? $input['organization']
            : [];

        $allowedOrganizationTypes = ['Organization', 'Corporation', 'NGO', 'EducationalOrganization'];
        $organizationType = isset($organization['type'])
            ? sanitize_text_field((string) $organization['type'])
            : 'Organization';

        $clean['organization'] = [
            'type' => in_array($organizationType, $allowedOrganizationTypes, true)
                ? $organizationType
                : 'Organization',
            'name' => sanitize_text_field((string) ($organization['name'] ?? '')),
            'alternate_name' => sanitize_text_field((string) ($organization['alternate_name'] ?? '')),
            'description' => sanitize_textarea_field((string) ($organization['description'] ?? '')),
            'email' => sanitize_email((string) ($organization['email'] ?? '')),
            'telephone' => sanitize_text_field((string) ($organization['telephone'] ?? '')),
            'logo' => esc_url_raw((string) ($organization['logo'] ?? '')),
        ];

        $socials = isset($input['social_profiles']) && is_array($input['social_profiles'])
            ? $input['social_profiles']
            : [];
        foreach (array_keys($clean['social_profiles']) as $network) {
            $clean['social_profiles'][$network] = esc_url_raw((string) ($socials[$network] ?? ''));
        }

        $clean['locations'] = [];
        $locations = isset($input['locations']) && is_array($input['locations'])
            ? $input['locations']
            : [];

        foreach ($locations as $index => $location) {
            if (!is_array($location)) {
                continue;
            }

            $name = sanitize_text_field((string) ($location['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $rawId = sanitize_title((string) ($location['id'] ?? ''));
            $id = $rawId !== '' ? $rawId : 'location-' . ((int) $index + 1);

            $clean['locations'][] = [
                'id' => $id,
                'type' => 'LocalBusiness',
                'name' => $name,
                'url' => esc_url_raw((string) ($location['url'] ?? '')),
                'telephone' => sanitize_text_field((string) ($location['telephone'] ?? '')),
                'email' => sanitize_email((string) ($location['email'] ?? '')),
                'faxNumber' => sanitize_text_field((string) ($location['faxNumber'] ?? '')),
                'address' => [
                    'streetAddress' => sanitize_text_field((string) ($location['streetAddress'] ?? '')),
                    'addressLocality' => sanitize_text_field((string) ($location['addressLocality'] ?? '')),
                    'addressRegion' => sanitize_text_field((string) ($location['addressRegion'] ?? '')),
                    'postalCode' => sanitize_text_field((string) ($location['postalCode'] ?? '')),
                    'addressCountry' => strtoupper(sanitize_text_field((string) ($location['addressCountry'] ?? ''))),
                ],
            ];
        }

        $schemas = isset($input['schemas']) && is_array($input['schemas'])
            ? $input['schemas']
            : [];
        foreach (array_keys($clean['schemas']) as $schema) {
            $clean['schemas'][$schema] = empty($schemas[$schema]) ? 0 : 1;
        }

        $allowedMappings = ['page', 'blog_post', 'product', 'disabled'];
        $mappings = isset($input['post_type_mappings']) && is_array($input['post_type_mappings'])
            ? $input['post_type_mappings']
            : [];
        $clean['post_type_mappings'] = [];

        foreach ($mappings as $postType => $mapping) {
            $postType = sanitize_key((string) $postType);
            $mapping = sanitize_key((string) $mapping);
            if ($postType !== '' && in_array($mapping, $allowedMappings, true)) {
                $clean['post_type_mappings'][$postType] = $mapping;
            }
        }

        foreach (self::defaults()['post_type_mappings'] as $postType => $mapping) {
            if (!isset($clean['post_type_mappings'][$postType])) {
                $clean['post_type_mappings'][$postType] = $mapping;
            }
        }

        $faqDisplay = isset($input['faq_display']) && is_array($input['faq_display'])
            ? $input['faq_display']
            : [];
        $faqMode = sanitize_key((string) ($faqDisplay['mode'] ?? 'auto_append'));
        if (!in_array($faqMode, ['auto_append', 'shortcode'], true)) {
            $faqMode = 'auto_append';
        }
        $clean['faq_display'] = [
            'mode' => $faqMode,
            'heading' => sanitize_text_field((string) ($faqDisplay['heading'] ?? 'Frequently Asked Questions')),
        ];

        $woocommerce = isset($input['woocommerce']) && is_array($input['woocommerce'])
            ? $input['woocommerce']
            : [];
        $clean['woocommerce'] = [
            'offers' => empty($woocommerce['offers']) ? 0 : 1,
            'ratings' => empty($woocommerce['ratings']) ? 0 : 1,
            'brand_meta_key' => sanitize_key((string) ($woocommerce['brand_meta_key'] ?? '_schemaweave_brand')),
        ];

        $advanced = isset($input['advanced']) && is_array($input['advanced'])
            ? $input['advanced']
            : [];
        $clean['advanced'] = [
            'delete_data_on_uninstall' => empty($advanced['delete_data_on_uninstall']) ? 0 : 1,
        ];

        return $clean;
    }

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = self::get();
        $postTypes = get_post_types(['public' => true], 'objects');
        unset($postTypes['attachment']);
        ?>
        <div class="wrap schemaweave-admin">
            <div class="schemaweave-hero">
                <div>
                    <p class="schemaweave-eyebrow">Schema.org JSON-LD</p>
                    <h1><?php esc_html_e('SchemaWeave', 'schemaweave'); ?></h1>
                    <p><?php esc_html_e('Configure structured data without fabricating commercial, rating, or review information.', 'schemaweave'); ?></p>
                </div>
                <span class="schemaweave-version">v<?php echo esc_html(SCHEMAWEAVE_VERSION); ?></span>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields('schemaweave_settings_group'); ?>

                <section class="schemaweave-card">
                    <div class="schemaweave-card-heading">
                        <div>
                            <h2><?php esc_html_e('General', 'schemaweave'); ?></h2>
                            <p><?php esc_html_e('Master switch for frontend JSON-LD output.', 'schemaweave'); ?></p>
                        </div>
                    </div>
                    <label class="schemaweave-toggle-row">
                        <input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[enabled]" value="1" <?php checked(!empty($settings['enabled'])); ?>>
                        <span><strong><?php esc_html_e('Enable SchemaWeave', 'schemaweave'); ?></strong><small><?php esc_html_e('Disable this to stop all SchemaWeave output without deleting settings.', 'schemaweave'); ?></small></span>
                    </label>
                </section>

                <section class="schemaweave-card">
                    <div class="schemaweave-card-heading">
                        <div>
                            <h2><?php esc_html_e('Organization', 'schemaweave'); ?></h2>
                            <p><?php esc_html_e('Public identity used by Organization, WebSite, WebPage, and article entities.', 'schemaweave'); ?></p>
                        </div>
                    </div>
                    <div class="schemaweave-grid schemaweave-grid-2">
                        <?php self::selectField('Organization type', 'organization[type]', $settings['organization']['type'], [
                            'Organization' => 'Organization',
                            'Corporation' => 'Corporation',
                            'NGO' => 'NGO',
                            'EducationalOrganization' => 'EducationalOrganization',
                        ]); ?>
                        <?php self::textField('Name', 'organization[name]', $settings['organization']['name'], 'Acme Industrial'); ?>
                        <?php self::textField('Alternate name', 'organization[alternate_name]', $settings['organization']['alternate_name'], 'Acme'); ?>
                        <?php self::textField('Email', 'organization[email]', $settings['organization']['email'], 'hello@example.com', 'email'); ?>
                        <?php self::textField('Telephone', 'organization[telephone]', $settings['organization']['telephone'], '+1 555 0100'); ?>
                        <?php self::textField('Logo URL', 'organization[logo]', $settings['organization']['logo'], 'https://example.com/logo.png', 'url'); ?>
                        <label class="schemaweave-field schemaweave-span-2">
                            <span><?php esc_html_e('Description', 'schemaweave'); ?></span>
                            <textarea name="<?php echo esc_attr(self::OPTION); ?>[organization][description]" rows="4" placeholder="<?php esc_attr_e('Short organization description', 'schemaweave'); ?>"><?php echo esc_textarea($settings['organization']['description']); ?></textarea>
                        </label>
                    </div>
                </section>

                <section class="schemaweave-card">
                    <div class="schemaweave-card-heading">
                        <div>
                            <h2><?php esc_html_e('Social Profiles', 'schemaweave'); ?></h2>
                            <p><?php esc_html_e('Non-empty URLs are published as Organization.sameAs.', 'schemaweave'); ?></p>
                        </div>
                    </div>
                    <div class="schemaweave-grid schemaweave-grid-2">
                        <?php foreach ([
                            'facebook' => 'Facebook',
                            'instagram' => 'Instagram',
                            'linkedin' => 'LinkedIn',
                            'youtube' => 'YouTube',
                            'x' => 'X / Twitter',
                            'tiktok' => 'TikTok',
                        ] as $key => $label) {
                            self::textField($label, 'social_profiles[' . $key . ']', $settings['social_profiles'][$key] ?? '', 'https://...', 'url');
                        } ?>
                    </div>
                </section>

                <section class="schemaweave-card">
                    <div class="schemaweave-card-heading schemaweave-card-heading-inline">
                        <div>
                            <h2><?php esc_html_e('Locations', 'schemaweave'); ?></h2>
                            <p><?php esc_html_e('Optional LocalBusiness entities. They are emitted only on pages marked to include locations, such as the front page.', 'schemaweave'); ?></p>
                        </div>
                        <button type="button" class="button button-secondary" id="schemaweave-add-location"><?php esc_html_e('Add location', 'schemaweave'); ?></button>
                    </div>
                    <div id="schemaweave-locations">
                        <?php foreach ($settings['locations'] as $index => $location) {
                            self::locationRow((int) $index, $location);
                        } ?>
                    </div>
                    <script type="text/template" id="schemaweave-location-template">
                        <?php self::locationRow('__INDEX__', []); ?>
                    </script>
                </section>

                <section class="schemaweave-card">
                    <div class="schemaweave-card-heading">
                        <div>
                            <h2><?php esc_html_e('Schema Types', 'schemaweave'); ?></h2>
                            <p><?php esc_html_e('Disable only the entity types that overlap with another SEO/schema plugin.', 'schemaweave'); ?></p>
                        </div>
                    </div>
                    <div class="schemaweave-checkbox-grid">
                        <?php foreach ([
                            'organization' => 'Organization',
                            'website' => 'WebSite',
                            'local_business' => 'LocalBusiness',
                            'webpage' => 'WebPage / CollectionPage',
                            'breadcrumb' => 'BreadcrumbList',
                            'product' => 'Product',
                            'blog_posting' => 'BlogPosting',
                            'item_list' => 'ItemList',
                            'faq' => 'FAQPage',
                            'related' => 'Related content references',
                        ] as $key => $label) { ?>
                            <label class="schemaweave-check">
                                <input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[schemas][<?php echo esc_attr($key); ?>]" value="1" <?php checked(!empty($settings['schemas'][$key])); ?>>
                                <span><?php echo esc_html($label); ?></span>
                            </label>
                        <?php } ?>
                    </div>
                </section>

                <section class="schemaweave-card">
                    <div class="schemaweave-card-heading">
                        <div>
                            <h2><?php esc_html_e('Post Type Mapping', 'schemaweave'); ?></h2>
                            <p><?php esc_html_e('Choose how each public WordPress post type should be interpreted by the schema engine.', 'schemaweave'); ?></p>
                        </div>
                    </div>
                    <div class="schemaweave-mapping-list">
                        <?php foreach ($postTypes as $postType => $object) {
                            $mapping = $settings['post_type_mappings'][$postType] ?? 'page'; ?>
                            <div class="schemaweave-mapping-row">
                                <div><strong><?php echo esc_html($object->labels->singular_name); ?></strong><code><?php echo esc_html($postType); ?></code></div>
                                <select name="<?php echo esc_attr(self::OPTION); ?>[post_type_mappings][<?php echo esc_attr($postType); ?>]">
                                    <option value="page" <?php selected($mapping, 'page'); ?>>WebPage</option>
                                    <option value="blog_post" <?php selected($mapping, 'blog_post'); ?>>BlogPosting</option>
                                    <option value="product" <?php selected($mapping, 'product'); ?>>Product</option>
                                    <option value="disabled" <?php selected($mapping, 'disabled'); ?>>Disabled</option>
                                </select>
                            </div>
                        <?php } ?>
                    </div>
                </section>

                <section class="schemaweave-card">
                    <div class="schemaweave-card-heading">
                        <div>
                            <h2><?php esc_html_e('FAQ display', 'schemaweave'); ?></h2>
                            <p><?php esc_html_e('FAQ structured data is emitted only when the same questions and answers are visible to visitors.', 'schemaweave'); ?></p>
                        </div>
                    </div>
                    <div class="schemaweave-grid schemaweave-grid-2">
                        <?php self::selectField('Display mode', 'faq_display[mode]', $settings['faq_display']['mode'] ?? 'auto_append', [
                            'auto_append' => __('Automatically append after content', 'schemaweave'),
                            'shortcode' => __('Shortcode only: [schemaweave_faq]', 'schemaweave'),
                        ]); ?>
                        <?php self::textField('FAQ heading', 'faq_display[heading]', $settings['faq_display']['heading'] ?? 'Frequently Asked Questions', 'Frequently Asked Questions'); ?>
                    </div>
                    <p class="description"><?php esc_html_e('In shortcode mode, FAQPage schema is generated only when [schemaweave_faq] is present in the saved post content. This keeps visible content and JSON-LD aligned.', 'schemaweave'); ?></p>
                </section>

                <section class="schemaweave-card">
                    <div class="schemaweave-card-heading">
                        <div>
                            <h2><?php esc_html_e('WooCommerce', 'schemaweave'); ?></h2>
                            <p><?php esc_html_e('Commercial and rating data are emitted only when WooCommerce has real values.', 'schemaweave'); ?></p>
                        </div>
                    </div>
                    <div class="schemaweave-stack">
                        <label class="schemaweave-toggle-row">
                            <input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[woocommerce][offers]" value="1" <?php checked(!empty($settings['woocommerce']['offers'])); ?>>
                            <span><strong><?php esc_html_e('Use verified WooCommerce offer data', 'schemaweave'); ?></strong><small><?php esc_html_e('Price, currency and stock availability are omitted when WooCommerce has no price.', 'schemaweave'); ?></small></span>
                        </label>
                        <label class="schemaweave-toggle-row">
                            <input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[woocommerce][ratings]" value="1" <?php checked(!empty($settings['woocommerce']['ratings'])); ?>>
                            <span><strong><?php esc_html_e('Use verified aggregate rating data', 'schemaweave'); ?></strong><small><?php esc_html_e('AggregateRating is emitted only when WooCommerce reports at least one rating.', 'schemaweave'); ?></small></span>
                        </label>
                        <?php self::textField('Brand meta key', 'woocommerce[brand_meta_key]', $settings['woocommerce']['brand_meta_key'], '_schemaweave_brand'); ?>
                    </div>
                </section>

                <section class="schemaweave-card">
                    <div class="schemaweave-card-heading">
                        <div>
                            <h2><?php esc_html_e('Advanced', 'schemaweave'); ?></h2>
                            <p><?php esc_html_e('Data lifecycle controls for plugin removal.', 'schemaweave'); ?></p>
                        </div>
                    </div>
                    <label class="schemaweave-toggle-row">
                        <input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[advanced][delete_data_on_uninstall]" value="1" <?php checked(!empty($settings['advanced']['delete_data_on_uninstall'])); ?>>
                        <span><strong><?php esc_html_e('Delete SchemaWeave data on uninstall', 'schemaweave'); ?></strong><small><?php esc_html_e('Off by default. When enabled, uninstall removes plugin settings and SchemaWeave post metadata.', 'schemaweave'); ?></small></span>
                    </label>
                </section>

                <section class="schemaweave-card schemaweave-integrity-card">
                    <h2><?php esc_html_e('Data integrity policy', 'schemaweave'); ?></h2>
                    <p><?php esc_html_e('SchemaWeave does not invent prices, offers, SKU/MPN/GTIN values, ratings, or reviews. Missing trusted data is omitted instead of fabricated.', 'schemaweave'); ?></p>
                </section>

                <?php submit_button(__('Save SchemaWeave settings', 'schemaweave')); ?>
            </form>

            <?php SchemaWeave_WordPress_Tools::renderSettingsTools(); ?>
        </div>
        <?php
    }

    private static function textField(string $label, string $path, $value, string $placeholder = '', string $type = 'text'): void
    {
        $name = self::fieldName($path);
        ?>
        <label class="schemaweave-field">
            <span><?php echo esc_html($label); ?></span>
            <input type="<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr((string) $value); ?>" placeholder="<?php echo esc_attr($placeholder); ?>">
        </label>
        <?php
    }

    private static function selectField(string $label, string $path, $value, array $options): void
    {
        $name = self::fieldName($path);
        ?>
        <label class="schemaweave-field">
            <span><?php echo esc_html($label); ?></span>
            <select name="<?php echo esc_attr($name); ?>">
                <?php foreach ($options as $optionValue => $optionLabel) { ?>
                    <option value="<?php echo esc_attr($optionValue); ?>" <?php selected((string) $value, (string) $optionValue); ?>><?php echo esc_html($optionLabel); ?></option>
                <?php } ?>
            </select>
        </label>
        <?php
    }


    private static function fieldName(string $path): string
    {
        $segments = preg_split('/\[|\]/', $path, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($segments) || empty($segments)) {
            return self::OPTION;
        }

        return self::OPTION . '[' . implode('][', $segments) . ']';
    }

    private static function locationRow($index, array $location): void
    {
        $address = isset($location['address']) && is_array($location['address'])
            ? $location['address']
            : [];
        $prefix = self::OPTION . '[locations][' . $index . ']';
        ?>
        <div class="schemaweave-location" data-location-row>
            <div class="schemaweave-location-head">
                <strong><?php esc_html_e('Location', 'schemaweave'); ?></strong>
                <button type="button" class="button-link-delete" data-remove-location><?php esc_html_e('Remove', 'schemaweave'); ?></button>
            </div>
            <div class="schemaweave-grid schemaweave-grid-2">
                <label class="schemaweave-field"><span><?php esc_html_e('ID / slug', 'schemaweave'); ?></span><input type="text" name="<?php echo esc_attr($prefix); ?>[id]" value="<?php echo esc_attr((string) ($location['id'] ?? '')); ?>" placeholder="main-office"></label>
                <label class="schemaweave-field"><span><?php esc_html_e('Name', 'schemaweave'); ?></span><input type="text" name="<?php echo esc_attr($prefix); ?>[name]" value="<?php echo esc_attr((string) ($location['name'] ?? '')); ?>" placeholder="Acme Main Office"></label>
                <label class="schemaweave-field"><span><?php esc_html_e('URL', 'schemaweave'); ?></span><input type="url" name="<?php echo esc_attr($prefix); ?>[url]" value="<?php echo esc_attr((string) ($location['url'] ?? '')); ?>" placeholder="https://example.com/contact"></label>
                <label class="schemaweave-field"><span><?php esc_html_e('Telephone', 'schemaweave'); ?></span><input type="text" name="<?php echo esc_attr($prefix); ?>[telephone]" value="<?php echo esc_attr((string) ($location['telephone'] ?? '')); ?>"></label>
                <label class="schemaweave-field"><span><?php esc_html_e('Email', 'schemaweave'); ?></span><input type="email" name="<?php echo esc_attr($prefix); ?>[email]" value="<?php echo esc_attr((string) ($location['email'] ?? '')); ?>"></label>
                <label class="schemaweave-field"><span><?php esc_html_e('Fax', 'schemaweave'); ?></span><input type="text" name="<?php echo esc_attr($prefix); ?>[faxNumber]" value="<?php echo esc_attr((string) ($location['faxNumber'] ?? '')); ?>"></label>
                <label class="schemaweave-field schemaweave-span-2"><span><?php esc_html_e('Street address', 'schemaweave'); ?></span><input type="text" name="<?php echo esc_attr($prefix); ?>[streetAddress]" value="<?php echo esc_attr((string) ($address['streetAddress'] ?? '')); ?>"></label>
                <label class="schemaweave-field"><span><?php esc_html_e('City / locality', 'schemaweave'); ?></span><input type="text" name="<?php echo esc_attr($prefix); ?>[addressLocality]" value="<?php echo esc_attr((string) ($address['addressLocality'] ?? '')); ?>"></label>
                <label class="schemaweave-field"><span><?php esc_html_e('Region / state', 'schemaweave'); ?></span><input type="text" name="<?php echo esc_attr($prefix); ?>[addressRegion]" value="<?php echo esc_attr((string) ($address['addressRegion'] ?? '')); ?>"></label>
                <label class="schemaweave-field"><span><?php esc_html_e('Postal code', 'schemaweave'); ?></span><input type="text" name="<?php echo esc_attr($prefix); ?>[postalCode]" value="<?php echo esc_attr((string) ($address['postalCode'] ?? '')); ?>"></label>
                <label class="schemaweave-field"><span><?php esc_html_e('Country code', 'schemaweave'); ?></span><input type="text" maxlength="2" name="<?php echo esc_attr($prefix); ?>[addressCountry]" value="<?php echo esc_attr((string) ($address['addressCountry'] ?? '')); ?>" placeholder="US"></label>
            </div>
        </div>
        <?php
    }
}
