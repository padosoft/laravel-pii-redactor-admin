# Project Rules

## Source Of Truth

- The implementation plan lives in `docs/IMPLEMENTATION_PLAN.md`.
- Current progress lives in `docs/PROGRESS.md`.
- Non-obvious setup facts live in `docs/LESSON.md`.
- Copilot PR review loop lives in `skills/copilot-pr-review-loop/SKILL.md`.

## Testing Rules

Every implementation slice must add or update appropriate coverage:

- PHPUnit for service provider, routes, controllers, authorization, audit, and no-disclosure guarantees.
- Vitest for API client behavior and React UI states.
- Playwright for user-visible UI/UX flows, including desktop and narrow viewport checks.

Required local and GitHub Actions gates:

```text
composer validate --strict
vendor/bin/phpunit
npm run typecheck
npm run test
npm run build
npm run e2e
```

A task is not closed until all gates are green in CI. If CI or GitHub access is blocked, record the blocker and exact next remote action in `docs/PROGRESS.md`.

## PR Rules

- Every PR requires GitHub Copilot Code Review and GitHub Actions checks for the current head.
- Normal request command: `gh pr edit <PR> --add-reviewer copilot`.
- If the normal command cannot resolve Copilot, use GraphQL `requestReviewsByLogin` with `copilot-pull-request-reviewer[bot]` as documented in `skills/copilot-pr-review-loop/SKILL.md`.
- Verify the request with `gh api repos/<owner>/<repo>/pulls/<PR>/requested_reviewers`.
- Check the PR issue timeline with `gh api repos/<owner>/<repo>/issues/<PR>/timeline --paginate`. The `copilot_work_started` event is the API signal for the GitHub UI message "Copilot started reviewing on behalf of ..."; when present after the latest request, Copilot is actively reviewing.
- Check Copilot response through PR reviews, top-level comments, inline comments, and review threads. Do not wait blindly or treat CI green as proof that Copilot ran.
- Do not mark Copilot blocked while a recent `copilot_work_started` event exists and no completed `reviewed` event has arrived yet. Continue polling for the review.
- Do not request another Copilot review for the same head SHA after `copilot_work_started`; wait for completion or a concrete GitHub error.
- Fix or explicitly resolve all actionable Copilot feedback before merge.
- Keep PRs small and roadmap-scoped after bootstrap. Each PR should cover one coherent slice so CI, Copilot feedback, and fixes do not pile up across unrelated changes.

## Security Rules

- Do not expose `salt`, `api_key`, `original`, raw input, redacted output, detokenised output, or token originals in unsafe responses or audit storage.
- Token-map listing may expose only `token`, `detector`, and `created_at`.
- Detokenise is high-risk and must require authorization, justification, UI confirmation, throttling, and audit rows.
- Raw samples require the raw-samples ability and must be audited when requested.

## UI Rules

- Dense admin shell with sidebar and topbar.
- No marketing landing page.
- Radius must stay at or below 8px.
- Text must not overlap or overflow on mobile or desktop.
