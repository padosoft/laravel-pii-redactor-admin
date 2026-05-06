# Claude / Agent Instructions

Follow `AGENTS.md`. Read `docs/LESSON.md` and `docs/PROGRESS.md` before making changes.

No task is complete until Composer validate, PHPUnit, TypeScript typecheck, Vitest, Vite build, and Playwright pass locally and in GitHub Actions.

For PRs, use `skills/copilot-pr-review-loop/SKILL.md`: request GitHub Copilot Code Review, use the GraphQL `copilot-pull-request-reviewer[bot]` fallback when the normal reviewer name fails, verify the request, then wait for CI and actionable review feedback.
