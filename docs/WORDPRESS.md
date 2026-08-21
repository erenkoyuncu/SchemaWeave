# WordPress integration

## Installation

Install the generated `schemaweave-<version>.zip` from **Plugins → Add New → Upload Plugin**.

After activation, open **Settings → SchemaWeave**.

## Global settings

The plugin supports:

- Organization identity and logo
- Social profile `sameAs` URLs
- Multiple optional LocalBusiness locations
- Entity-level schema switches
- Public post-type mappings
- FAQ display mode
- WooCommerce Offer/rating controls
- Settings export/import/reset
- Opt-in cleanup on uninstall

## Per-content controls

Public post types receive a SchemaWeave meta box with:

- Per-URL disable switch
- WebPage / BlogPosting / Product override
- WebPage subtype override
- Description override
- Media Library image override
- Product brand override
- FAQ editor
- JSON-LD preview

## FAQ content parity

Saved FAQ rows are visitor-visible by default. In `auto_append` mode they are appended after the main content. In shortcode mode use:

```text
[schemaweave_faq]
```

FAQPage JSON-LD is emitted only when the same FAQ rows are considered visible. Integrations that render the exact rows themselves can use `schemaweave_faq_is_visible`.

## WooCommerce

When WooCommerce is available:

- SKU is included only when WooCommerce has a value.
- A simple product gets `Offer` only when a real product price exists.
- A variable product gets `AggregateOffer` only when real variation prices exist.
- `AggregateRating` is included only when at least one real rating exists.
- Missing price/rating values are omitted instead of synthesized.

## Diagnostics

**Settings → SchemaWeave Diagnostics** shows environment information, potential schema overlap from commonly used SEO/schema plugins, enabled entity groups, an inspector, and internal graph-validation findings.

Potential overlap is intentionally advisory; detecting an active SEO plugin is not proof that duplicate entities are actually being output.

## WP-CLI

```bash
wp schemaweave status
wp schemaweave inspect 42
wp schemaweave validate 42
```

## Filters

- `schemaweave_page`
- `schemaweave_config`
- `schemaweave_graph`
- `schemaweave_faq_is_visible`
