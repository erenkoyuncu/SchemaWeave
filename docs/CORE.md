# SchemaWeave PHP core in the WordPress repository

SchemaWeave for WordPress uses the framework-independent PHP core from:

https://github.com/erenkoyuncu/SchemaWeave-PHP

## Development checkout

In the GitHub source repository, `includes/SchemaWeave` is a git submodule pinned to the exact core commit tested by the WordPress plugin release.

Clone with:

```bash
git clone --recurse-submodules https://github.com/erenkoyuncu/SchemaWeave.git
```

If the repository was cloned without submodules:

```bash
git submodule update --init --recursive
```

Core changes should be developed and tested in `SchemaWeave-PHP` first. The WordPress repository then updates its submodule pointer to the tested core commit.

## WordPress.org / release ZIPs

Published WordPress plugin ZIPs do **not** require Git, Composer, or submodule support. The release build materializes the pinned PHP core into `includes/SchemaWeave/` before creating the installable archive.

WordPress-specific adapters, admin UI, WooCommerce integration, meta boxes, diagnostics, settings and frontend behavior remain in this repository.
