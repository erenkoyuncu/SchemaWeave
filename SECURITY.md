# Security Policy

## Supported versions

| Version | Supported |
| --- | --- |
| 1.x | Yes |
| Pre-1.0 development snapshots | No |

Security fixes are applied to the latest supported 1.x release. Backports may be provided when practical but are not guaranteed.

## Reporting a vulnerability

Please do not publish credentials, exploit details, private customer information, or sensitive production data in a public issue.

Use GitHub Security Advisories for private vulnerability reports when the public repository is available.

A useful report includes:

- affected SchemaWeave version;
- affected component (core, WordPress bridge, WooCommerce integration, or build tooling);
- minimal reproduction steps;
- expected and actual behavior;
- security impact;
- a fictional/minimal example containing no real customer data or secrets.

## Trust boundary

SchemaWeave renders structured data supplied by the host application, WordPress, WooCommerce, administrators, and configured adapters. Integrations remain responsible for authenticating their own data sources and ensuring values represent legitimate, visitor-accessible content.

The project does not provide a remote service, telemetry endpoint, or license server.
