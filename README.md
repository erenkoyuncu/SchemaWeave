<p align="center">
  <img src="branding/schemaweave-hero.jpg" alt="SchemaWeave — Structured Data Engine for WordPress & PHP" width="100%">
</p>

<h1 align="center">SchemaWeave</h1>

<p align="center">
  <strong>Schema.org JSON-LD structured data for WordPress and WooCommerce.</strong><br>
  Clean graphs, real data, practical controls — without inventing SEO signals.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/WordPress-5.8%2B-21759B?style=flat-square&logo=wordpress&logoColor=white" alt="WordPress 5.8+">
  <img src="https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 7.4+">
  <img src="https://img.shields.io/badge/WooCommerce-Optional-96588A?style=flat-square&logo=woocommerce&logoColor=white" alt="WooCommerce optional">
  <img src="https://img.shields.io/badge/JSON--LD-Schema.org-2563EB?style=flat-square" alt="JSON-LD Schema.org">
  <img src="https://img.shields.io/badge/License-GPLv2%2B-16A34A?style=flat-square" alt="GPLv2 or later">
</p>

---

## Why SchemaWeave?

SchemaWeave generates structured data from **real WordPress content, WooCommerce data and administrator configuration**. It is designed to create coherent Schema.org `@graph` output while staying conservative about commercial and reputation data.

- No fabricated prices, offers, SKU/MPN values, ratings or reviews.
- Real WooCommerce `Offer` / `AggregateOffer` output when product data exists.
- Real WordPress author data for `BlogPosting`.
- Visitor-visible FAQ output so FAQ schema stays aligned with page content.
- Per-content schema mapping and overrides.
- Diagnostics, graph inspection and validation tools.
- No telemetry, hosted API, tracking pixel or license server.

## Supported structured data

| Area | Schema types / behavior |
| --- | --- |
| Site | `Organization`, `WebSite` |
| Business | `LocalBusiness`, multiple configured locations |
| Pages | `WebPage`, `AboutPage`, `ContactPage`, `ProfilePage`, `SearchResultsPage`, `CollectionPage` |
| Content | `BlogPosting`, real WordPress `Person` author, publication/modification dates |
| Navigation | `BreadcrumbList`, `ItemList` |
| FAQ | `FAQPage`, `Question`, `Answer` with visible-content safeguards |
| WooCommerce | `Product`, `Offer`, `AggregateOffer`, SKU, availability, ratings when real data exists |

## WooCommerce

SchemaWeave integrates with WooCommerce without manufacturing missing product data.

### Simple products

When WooCommerce contains real values, SchemaWeave can emit:

```text
Product
├── sku
├── brand
└── Offer
    ├── price
    ├── priceCurrency
    └── availability
```

### Variable products

Variable products use the actual WooCommerce variation price set:

```text
Product
└── AggregateOffer
    ├── lowPrice
    ├── highPrice
    ├── offerCount
    ├── priceCurrency
    └── availability
```

If ratings or reviews do not exist, SchemaWeave does not invent them.

## Content-level controls

Pages, posts, products and supported public post types can override the global mapping through the SchemaWeave meta box.

Available controls include:

- Disable schema for an individual item.
- Map content to `WebPage`, `BlogPosting` or `Product`.
- Select `AboutPage`, `ContactPage` or `ProfilePage` subtypes.
- Override the schema description.
- Select a schema image from the WordPress Media Library.
- Set a product brand.
- Add repeating FAQ entries.
- Preview generated JSON-LD before saving.

## FAQ integrity

FAQ structured data should match content users can actually see.

SchemaWeave therefore renders saved FAQ entries as accessible frontend content by default. A shortcode mode is also available:

```text
[schemaweave_faq]
```

When shortcode mode is selected, FAQ schema is emitted only when the shortcode is present on the page.

## Installation

### WordPress ZIP

1. Download the latest installable SchemaWeave ZIP.
2. Open **Plugins → Add New Plugin → Upload Plugin**.
3. Install and activate SchemaWeave.
4. Open **Settings → SchemaWeave**.
5. Configure the organization, schema switches, locations and post-type mappings.

WordPress.org publication is planned for the same free/open-source plugin distribution.

### Development checkout

The WordPress repository uses the independent PHP engine as a git submodule:

```bash
git clone --recurse-submodules https://github.com/erenkoyuncu/SchemaWeave.git
```

## SchemaWeave ecosystem

SchemaWeave is intentionally split into two repositories:

| Project | Purpose | License |
| --- | --- | --- |
| **SchemaWeave** | WordPress & WooCommerce integration, admin UI, meta boxes, diagnostics and WP-CLI | GPLv2 or later |
| **[SchemaWeave-PHP](https://github.com/erenkoyuncu/SchemaWeave-PHP)** | Framework-agnostic Schema.org JSON-LD engine for PHP 7.4+ | MIT |

The PHP repository is the **source of truth for core development**. WordPress release ZIPs bundle the core so end users do not need Composer or git submodules.

## Data integrity principle

SchemaWeave follows one simple rule:

> If the source application does not provide trustworthy data, SchemaWeave omits the field instead of inventing it.

This is especially important for:

- prices and offers,
- SKU / identifiers,
- product availability,
- aggregate ratings,
- reviews,
- author and publication metadata.

## Diagnostics & tooling

SchemaWeave includes tools for inspecting the generated graph and the active WordPress environment.

- Schema Inspector for selected content.
- Structural graph validation.
- Environment diagnostics.
- Conservative potential-overlap warning when other SEO/schema plugins are active.
- Settings export, import and reset.
- WP-CLI inspection commands.

## Privacy

SchemaWeave does not require an external API and does not include telemetry or a license server. See [PRIVACY.md](PRIVACY.md) for the project privacy notes.

## Documentation

- [WordPress integration guide](docs/WORDPRESS.md)
- [PHP core relationship](docs/CORE.md)
- [Privacy](PRIVACY.md)
- [Security policy](SECURITY.md)
- [Changelog](CHANGELOG.md)

## Contributing

Issues and pull requests are welcome. See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

SchemaWeave WordPress plugin is licensed under **GPLv2 or later**.

The bundled SchemaWeave PHP core remains **MIT licensed** and is developed independently in [SchemaWeave-PHP](https://github.com/erenkoyuncu/SchemaWeave-PHP).
