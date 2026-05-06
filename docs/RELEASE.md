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

## Tagging

Use semantic versioning. For the first stable release:

```bash
git tag -a v1.0.0 -m "Release v1.0.0"
git push origin v1.0.0
```

Do not tag while GitHub Actions, Copilot review, or any review thread is pending.
