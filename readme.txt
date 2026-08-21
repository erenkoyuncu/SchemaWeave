=== SchemaWeave ===
Contributors: erenkoyuncu
Tags: schema, json-ld, schema.org, seo, woocommerce
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate Schema.org JSON-LD from real WordPress and WooCommerce content using a reusable PHP core.

== Description ==

SchemaWeave generates structured data locally from WordPress/WooCommerce content and administrator configuration.

Key features:

* Organization, WebSite, LocalBusiness, WebPage, BlogPosting, Product, BreadcrumbList, ItemList and FAQPage graph entities.
* Per-entity switches to reduce overlap with other SEO/schema plugins.
* Public post type mapping to WebPage, BlogPosting, Product or Disabled.
* Per-content schema overrides, image/description/brand controls and FAQ editor.
* Visitor-visible FAQ output through automatic append or `[schemaweave_faq]` shortcode mode.
* WooCommerce Offer/AggregateOffer, SKU, availability and AggregateRating only when real source data exists.
* Diagnostics, graph inspector, lightweight structural validator and conservative overlap warnings.
* Settings export/import/reset and opt-in uninstall cleanup.
* WP-CLI status, inspect and validate commands.
* No telemetry, hosted API, tracking or license-server dependency.

SchemaWeave does not fabricate prices, offers, identifiers, ratings or reviews.

== Installation ==

1. Upload the SchemaWeave plugin ZIP from Plugins > Add New > Upload Plugin.
2. Activate SchemaWeave.
3. Open Settings > SchemaWeave.
4. Configure organization identity, locations, schema switches and post type mapping.
5. Edit any public post/page/product to use the SchemaWeave content panel for URL-specific overrides and FAQ data.
6. If another SEO plugin emits overlapping structured data, disable only the overlapping SchemaWeave entity types.

== Frequently Asked Questions ==

= Does SchemaWeave send site data to an external service? =
No. SchemaWeave has no telemetry, analytics, license server or hosted API dependency. Structured data is generated locally.

= Does it invent Product prices or ratings for SEO? =
No. Missing commercial, identifier, rating and review data is omitted.

= Can I use it with another SEO plugin? =
Yes, but avoid having multiple systems emit the same entity for the same page. SchemaWeave provides entity-level switches and diagnostics to help review potential overlap.

= Why can visitors see FAQ content added in the SchemaWeave editor? =
FAQ structured data should represent content visitors can access. The default mode appends the FAQ to the page; shortcode mode uses `[schemaweave_faq]`.

= Does SchemaWeave guarantee rich results or ranking improvements? =
No. SchemaWeave generates structured data; search engines apply their own eligibility and display rules.

== Privacy ==

SchemaWeave does not transmit site content, product data, settings or diagnostics to the project maintainers or to a SchemaWeave service.

== Changelog ==

= 1.0.1 =
* Improved WordPress.org Plugin Check compliance for output, request validation, uninstall cleanup and translations.
* Updated Tested up to for WordPress 7.1.

= 1.0.0 =
* First stable release.
* Added the framework-independent PHP 7.4+ JSON-LD engine and WordPress/WooCommerce bridge.
* Added Organization, WebSite, LocalBusiness, WebPage variants, Product, BlogPosting, BreadcrumbList, ItemList and FAQPage support.
* Added WordPress settings, post type mapping, per-content overrides, Media Library image selection and JSON-LD preview.
* Added visible FAQ rendering and shortcode mode.
* Added WooCommerce Offer/AggregateOffer, SKU, availability and rating integration using real catalog data only.
* Added diagnostics, graph validation, settings backup/recovery and WP-CLI tools.
* Added migration/uninstall safety, script-output hardening, CI, Plugin Check and runtime smoke workflows.
