# Laravel PII Redactor Admin Agent Guide

This repository is an installable Laravel package for `padosoft/laravel-pii-redactor`.

Before implementation work, read:

- `docs/RULES.md`
- `docs/PROGRESS.md`
- `docs/LESSON.md`
- `docs/IMPLEMENTATION_PLAN.md`
- `skills/laravel-pii-redactor-admin-plan/SKILL.md`
- `skills/copilot-pr-review-loop/SKILL.md` when a PR is open or being updated

## Non-Negotiable Gates

Every task and every PR iteration is incomplete until these gates pass locally and in GitHub Actions:

```text
composer validate --strict
vendor/bin/phpunit
npm run typecheck
npm run test
npm run build
npm run e2e
```

Add or update PHPUnit, Vitest, and Playwright coverage for every backend endpoint and every meaningful UI/UX interaction touched by the task.

If a gate cannot run, record the exact blocker in `docs/PROGRESS.md`. Do not call the task closed.

## PR Review Loop

- Every PR requires GitHub Copilot Code Review plus green GitHub Actions.
- If `gh pr edit <PR> --add-reviewer copilot` fails, use the GraphQL fallback in `skills/copilot-pr-review-loop/SKILL.md` with `copilot-pull-request-reviewer[bot]`.
- Verify Copilot was actually requested via `gh api repos/<owner>/<repo>/pulls/<PR>/requested_reviewers`; do not assume a failed or silent reviewer command engaged Copilot.
- Check reviews, inline comments, and review threads before deciding Copilot has responded or has no actionable feedback.

## Security Rules

- Never expose raw token originals through token-map listing.
- Never persist detokenised output.
- Raw samples require the dedicated raw-samples ability.
- Detokenise requires the dedicated ability, explicit justification, confirmation in UI, and audit rows for success and denial.
- Audit rows may store counts, target hashes, status codes, actor metadata, and justification. They must not store raw text, redacted output, detokenised output, salts, API keys, or token originals.

## UI Rules

- Build the actual admin console, not a landing page.
- Keep the interface dense, operational, and table-oriented where appropriate.
- Cards are only for repeated items and framed tools; do not nest cards.
- Keep radius at 8px or less.
- Verify desktop and narrow/mobile viewport behavior with Playwright.
