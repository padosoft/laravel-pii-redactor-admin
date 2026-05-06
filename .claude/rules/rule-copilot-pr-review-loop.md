# Rule: GitHub Copilot PR Review Loop

- Every PR must request GitHub Copilot Code Review and wait for it; CI green alone is not enough.
- If `gh pr edit <PR> --add-reviewer copilot` cannot resolve the reviewer, request `copilot-pull-request-reviewer[bot]` through GraphQL `requestReviewsByLogin`.
- Verify Copilot was actually engaged with `gh api repos/<owner>/<repo>/pulls/<PR>/requested_reviewers`.
- Check response state through PR reviews, issue comments, inline review comments, and GraphQL review threads; do not wait blindly.
- Fix or explicitly resolve all actionable Copilot feedback before merge.
- After every fix push, rerun local gates, wait for GitHub Actions, and re-check Copilot comments/threads.
- If Copilot or GitHub access is blocked, record the exact command, error, and next remote action in `docs/PROGRESS.md`.

Repository skill: `skills/copilot-pr-review-loop/SKILL.md`.
