# Changelog

## 1.0.1 - 2026-08-21

- Improved WordPress.org Plugin Check compliance.
- Hardened request validation and output handling without changing schema behavior.
- Updated WordPress compatibility metadata for 7.1.
- Replaced direct postmeta cleanup queries with the WordPress metadata API.

## 1.0.0 - 2026-08-20

- First stable WordPress release.
- Added Organization, WebSite, LocalBusiness, WebPage variants, BlogPosting, Product, BreadcrumbList, ItemList and FAQPage.
- Added post-type mappings and per-content overrides.
- Added visible FAQ output and shortcode mode.
- Added WooCommerce simple and variable product integration using real catalog data.
- Added diagnostics, graph validation, settings import/export/reset and WP-CLI tools.
- Added conservative duplicate-schema overlap warnings.
- Added safe uninstall cleanup as an explicit opt-in.
