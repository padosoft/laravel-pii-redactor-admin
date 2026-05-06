# Rule: GitHub Copilot PR Review Loop

- Every PR must request GitHub Copilot Code Review and wait for it; CI green alone is not enough.
- If `gh pr edit <PR> --add-reviewer copilot` cannot resolve the reviewer, request `copilot-pull-request-reviewer[bot]` through GraphQL `requestReviewsByLogin`.
- Verify Copilot was actually engaged with `gh api repos/<owner>/<repo>/pulls/<PR>/requested_reviewers`.
- Also check the PR issue timeline: `copilot_work_started` is the API form of the GitHub UI message "Copilot started reviewing on behalf of ...". When this event exists after the latest request, Copilot is running and the agent must wait for the review result instead of declaring a blocker.
- Do not request another Copilot review for the same head SHA after `copilot_work_started`; overlapping requests can accumulate and make the PR state misleading.
- Check response state through PR reviews, issue comments, inline review comments, and GraphQL review threads; do not wait blindly.
- Fix or explicitly resolve all actionable Copilot feedback before merge.
- After every fix push, rerun local gates, wait for GitHub Actions, and re-check Copilot comments/threads.
- Prefer small, focused PRs after the bootstrap PR so each review loop covers one coherent slice.
- If Copilot or GitHub access is blocked, record the exact command, error, and next remote action in `docs/PROGRESS.md`.

Repository skill: `skills/copilot-pr-review-loop/SKILL.md`.
