# Contributing

Thanks for helping improve SchemaWeave.

## Project boundaries

SchemaWeave is intended to remain reusable and customer-independent.

Please do not contribute:

- customer names, domains, addresses, phone numbers or private business data;
- adapters that hard-code a private project's table names into the core;
- API keys, access tokens, passwords, private keys or production credentials;
- fabricated Product prices, availability, SKU/MPN/GTIN values, ratings, reviews or offers.

Project-specific database logic belongs in a separate adapter maintained by the integrating application.

## Development checks

Before opening a pull request, run:

```bash
composer validate --strict --no-check-publish
composer lint
composer test
composer build-plugin
composer test-built-plugin
composer build-php-package
composer test-php-package
```

The WordPress plugin archive should also pass:

```bash
unzip -t dist/schemaweave-*.zip
```

## Compatibility

The reusable core targets PHP 7.4+.

Avoid language features that require PHP 8 unless the minimum supported version is intentionally changed in a dedicated release.

## Data integrity

Structured data must describe real, verifiable page content. Do not add fallback logic that invents commercial or reputation data solely to make a rich result look more complete.
