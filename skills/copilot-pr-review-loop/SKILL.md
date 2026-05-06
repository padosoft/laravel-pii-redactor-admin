---
name: copilot-pr-review-loop
description: Mandatory GitHub Copilot Code Review + CI loop for PRs. Use after opening a PR, after pushing to a PR branch, or when fixing CI/review feedback.
---

# Copilot PR Review Loop

## Rule

Do not close a PR iteration after only pushing code or seeing CI green. A PR is ready only when:

- local gates are green
- GitHub Actions checks for the current head are green
- GitHub Copilot Code Review was requested and has either no actionable comments or all actionable comments are fixed/resolved

For this repository the local gates are:

```text
composer validate --strict
vendor/bin/phpunit
npm run typecheck
npm run test
npm run build
npm run e2e
```

## Request Copilot

Try the normal reviewer path first:

```powershell
gh pr edit <PR> --add-reviewer copilot
```

If GitHub CLI cannot resolve `copilot`, use GraphQL with the Copilot review bot login. This is the reliable fallback.

```powershell
$prNodeId = gh pr view <PR> --json id --jq .id

$query = @'
mutation RequestReviewsByLogin($pullRequestId: ID!, $botLogins: [String!], $union: Boolean!) {
  requestReviewsByLogin(input: {pullRequestId: $pullRequestId, botLogins: $botLogins, union: $union}) {
    clientMutationId
  }
}
'@

gh api graphql `
  -f query="$query" `
  -F pullRequestId="$prNodeId" `
  -F botLogins[]='copilot-pull-request-reviewer[bot]' `
  -F union=true
```

Do not use the REST `reviewers[]=copilot` shortcut as proof. It can succeed without creating a visible Copilot Code Review request.

## Verify Copilot Was Requested

First derive the repository name:

```powershell
$repo = gh repo view --json nameWithOwner --jq .nameWithOwner
```

Then verify requested reviewers:

```powershell
gh api "repos/$repo/pulls/<PR>/requested_reviewers"
```

The response should show Copilot as a requested reviewer. If it does not, Copilot was not actually engaged; retry the GraphQL request or record the exact blocker in `docs/PROGRESS.md`.

## Check Whether Copilot Responded

Review summaries:

```powershell
gh api "repos/$repo/pulls/<PR>/reviews" `
  --jq '.[] | {user:.user.login,state,commit_id,body,submitted_at}'
```

Top-level PR comments:

```powershell
gh api "repos/$repo/issues/<PR>/comments" `
  --jq '.[] | {user:.user.login,body,created_at}'
```

Inline review comments:

```powershell
gh api "repos/$repo/pulls/<PR>/comments" `
  --jq '.[] | {user:.user.login,path,line,body,commit_id}'
```

Thread state, including resolved/outdated:

```powershell
$threadQuery = @'
query($owner:String!, $repo:String!, $number:Int!) {
  repository(owner:$owner, name:$repo) {
    pullRequest(number:$number) {
      reviewDecision
      reviewThreads(first:100) {
        nodes {
          id
          isResolved
          isOutdated
          comments(first:10) {
            nodes {
              author { login }
              path
              line
              outdated
              body
            }
          }
        }
      }
    }
  }
}
'@

$owner = $repo.Split('/')[0]
$name = $repo.Split('/')[1]

gh api graphql `
  -f query="$threadQuery" `
  -f owner="$owner" `
  -f repo="$name" `
  -F number=<PR>
```

If there are no Copilot reviews/comments after requesting it, wait a practical interval before concluding it did not run. Do not wait blindly: check requested reviewers, reviews, inline comments, and thread state.

## CI Loop

Watch checks:

```powershell
gh pr checks <PR> --watch --fail-fast
```

For failed runs:

```powershell
gh run view <run-id> --log-failed
```

Fix failures locally, run all required local gates, commit, push, then repeat Copilot + CI verification.

## Resolving Feedback

- Must-fix: security, raw PII exposure, authorization gaps, persistence of sensitive output, broken tests, behavioral regressions, missing coverage for touched behavior.
- Should-fix: small clarity, docs, maintainability, and style improvements unless there is a concrete reason not to.
- Discuss: false positives or context-dependent suggestions; reply with rationale before resolving.

GitHub may not mark a thread outdated when the fix lands near, but not exactly on, the anchored line. Read the current file and verify the issue is actually fixed before treating the thread as resolved.
