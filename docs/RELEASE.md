# Release Readiness

This package can be released only when the current PR/branch satisfies the same gates required for every task:

```bash
composer validate --strict
vendor/bin/phpunit
npm run typecheck
npm run test
npm run build
npm run e2e
```

GitHub Actions must also pass, including the fresh Laravel host install smoke.

## Pre-Release Checklist

- Confirm `config/pii-redactor-admin.php` remains disabled by default.
- Confirm token-map listing never selects or serializes `original`.
- Confirm detokenise requires ability, token-shaped input, justification, throttling, and audit rows.
- Confirm audit rows do not contain raw text, redacted output, detokenised output, salts, API keys, secrets, or token originals.
- Confirm `resources/dist` is committed after `npm run build`.
- Confirm `resources/screenshots` contains only design references.
- Confirm README installation notes mention the core package Composer repository requirement when the core package is not available from the host's configured repositories.
- Run `scripts/verify-fresh-laravel-host.ps1` from the package root against a fresh Laravel 13 host.

## Packagist

Packagist publication is required before host apps can install with only:

```bash
composer require padosoft/laravel-pii-redactor-admin
```

Both packages must be indexed by Packagist:

- `padosoft/laravel-pii-redactor`
- `padosoft/laravel-pii-redactor-admin`

Composer ignores repositories declared by dependencies, so publishing only the admin package is not enough while the core package is unavailable from the host app's configured repositories.

If Packagist API credentials are available in the environment, submit or update packages through Packagist's API. Without credentials, record the blocker in `docs/PROGRESS.md` and publish manually from the Packagist UI.

## Tagging

Use semantic versioning. For the first stable release:

```bash
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0
```

For verified patch releases after the first stable tag:

```bash
git tag -a v1.0.1 -m "Release v1.0.1"
git push origin v1.0.1
```

`v1.0.1` is the current runtime release. PR #5 added package asset route test hardening after `v1.0.1` without changing runtime package behavior; it does not require a new runtime tag by itself.

For the final docs/test-hardening ledger after `v1.0.1`:

```bash
git tag -a v1.0.2 -m "Release v1.0.2"
git push origin v1.0.2
```

Do not tag while GitHub Actions, Copilot review, or any review thread is pending.
