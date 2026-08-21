# SchemaWeave

**Schema.org JSON-LD structured data for WordPress and WooCommerce.**

SchemaWeave generates structured data from real WordPress content and administrator configuration. It is built on the independent [SchemaWeave PHP](https://github.com/erenkoyuncu/SchemaWeave-PHP) core.

## Highlights

- Organization, WebSite and multi-location LocalBusiness graphs.
- WebPage variants including AboutPage, ContactPage and ProfilePage.
- BlogPosting with the real WordPress author and publication/modification dates.
- Product schema for WooCommerce simple and variable products.
- Real WooCommerce Offer / AggregateOffer, SKU, availability and rating data only when available.
- BreadcrumbList, ItemList and FAQPage.
- Per-post/page/product schema overrides.
- Media Library schema image selection.
- Visitor-visible FAQ output and `[schemaweave_faq]` shortcode mode.
- Schema Inspector, diagnostics and structural validation.
- Settings export/import/reset and safe uninstall behavior.
- WP-CLI inspection commands.
- No telemetry, hosted API, tracking, or license server.

## Requirements

- WordPress 5.8+
- PHP 7.4+
- WooCommerce is optional and only required for WooCommerce Product integration.

## Installation

1. Download a release ZIP or install SchemaWeave from WordPress.org when available.
2. Activate the plugin.
3. Open **Settings → SchemaWeave**.
4. Configure the organization, locations, schema switches and post-type mappings.
5. Use the SchemaWeave panel on individual pages/posts/products for content-specific overrides.

## Data integrity

SchemaWeave does **not** fabricate prices, offers, identifiers, ratings, or reviews. Missing data is omitted from JSON-LD.

## FAQ visibility

FAQ structured data should correspond to visitor-visible content. SchemaWeave therefore renders saved FAQ content on the frontend by default. Shortcode mode emits FAQ schema only when `[schemaweave_faq]` is actually present.

## WooCommerce

Simple products use a real `Offer` when price data exists. Variable products use a real `AggregateOffer` derived from WooCommerce variation prices. Ratings are emitted only when WooCommerce has real rating data.

## Bundled PHP core

The `includes/SchemaWeave/` directory is a bundled copy of the framework-independent core used so WordPress.org installations do not require Composer. The source of truth for core development is:

https://github.com/erenkoyuncu/SchemaWeave-PHP

The bundled core remains MIT-licensed; the WordPress plugin is GPLv2 or later.

## Documentation

- [`docs/WORDPRESS.md`](docs/WORDPRESS.md)
- [`PRIVACY.md`](PRIVACY.md)
- [`SECURITY.md`](SECURITY.md)

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Issues and pull requests are welcome.

## License

SchemaWeave WordPress plugin: GPLv2 or later.

Bundled SchemaWeave PHP core: MIT License. See `includes/SchemaWeave/LICENSE`.
