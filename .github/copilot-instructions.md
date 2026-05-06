# Copilot Review Instructions

Review this package as a security-sensitive Laravel admin surface for PII redaction.

Focus on:

- raw PII leaks in JSON, logs, audit rows, localStorage, screenshots, and tests
- token-map endpoints selecting or serializing `original`
- detokenise paths missing authorization, justification, confirmation, throttling, or audit rows
- raw sample access missing `viewPiiRedactorRawSamples`
- route registration when `pii-redactor-admin.enabled=false`
- missing PHPUnit, Vitest, Playwright, or CI coverage for changed behavior

Every task must keep these gates green:

```text
composer validate --strict
vendor/bin/phpunit
npm run typecheck
npm run test
npm run build
npm run e2e
```

Agents maintain a mandatory Copilot review loop in `skills/copilot-pr-review-loop/SKILL.md`. If normal reviewer assignment fails, they should request `copilot-pull-request-reviewer[bot]` through GraphQL and verify the pending reviewer/review thread state before merging.
