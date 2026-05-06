# Laravel PII Redactor Admin Implementation Plan

Build `padosoft/laravel-pii-redactor-admin` as an installable Laravel 13 package for PHP `^8.3`, using Blade + Vite + React + TypeScript + Tailwind-compatible CSS.

The admin UI consumes only safe APIs from `padosoft/laravel-pii-redactor`, especially:

- `RedactorAdminInspector`
- `RedactionStrategyFactory`
- `DetectionReportFormatter`
- `TokenResolutionService`
- `CustomRulePackInspector`

## Defaults

- Package is disabled by default.
- UI route: `/pii-redactor-admin`.
- API prefix: `/pii-redactor-admin/api`.
- Default middleware: `web,auth`.
- Host applications define gates:
  - `viewPiiRedactorAdmin`
  - `detokenisePiiRedactor`
  - `viewPiiRedactorRawSamples`

## Security Model

- Token-map listing never selects or serializes `original`.
- Detokenise requires a dedicated gate, at least 10 characters of justification, token-pattern validation, throttling, and audit rows.
- Raw samples require `viewPiiRedactorRawSamples`.
- Audit events store metadata, counts, target hashes, status, actor metadata, and justification only.
- Raw input, redacted output, detokenised output, salts, API keys, secrets, and token originals must never be stored in audit rows.

## Backend Scope

- Service provider with disabled-by-default route registration.
- Config publishing.
- Audit-event migration/model.
- Status, detector, custom-rules, settings, audit, playground, token-map, and detokenise endpoints.
- PHPUnit coverage for route registration, auth/gate denial, audit rows, raw-sample gating, and no-disclosure guarantees.

## Frontend Scope

- Blade shell loading Vite assets.
- React admin shell with sidebar, topbar, theme toggle, keyboard shortcut, operational pages, safe API client, and same-origin guard.
- Pages: overview, playground, audit log, token map, detokenise, detectors, custom rules, settings.
- Vitest coverage for API client safety.
- Playwright coverage for UI navigation, playground, raw-sample disabled state, token-map non-disclosure, detokenise arm/reveal flow, settings/custom-rules/detectors, and narrow viewport.

## Required Gates

Every task iteration is incomplete until these pass locally and in GitHub Actions:

```text
composer validate --strict
vendor/bin/phpunit
npm run typecheck
npm run test
npm run build
npm run e2e
```

If CI/Copilot/GitHub access is blocked, record exact next steps in `docs/PROGRESS.md`.
