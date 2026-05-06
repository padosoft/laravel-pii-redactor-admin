---
name: laravel-pii-redactor-admin-plan
description: Repository-specific guidance for implementing the Laravel PII Redactor Admin package.
---

# Laravel PII Redactor Admin Plan Skill

Read these files before changing code:

- `docs/IMPLEMENTATION_PLAN.md`
- `docs/RULES.md`
- `docs/LESSON.md`
- `docs/PROGRESS.md`

Required gates for every slice:

```text
composer validate --strict
vendor/bin/phpunit
npm run typecheck
npm run test
npm run build
npm run e2e
```

Add PHPUnit, Vitest, and Playwright coverage for each changed backend endpoint and UI interaction. The task is not complete until local gates and GitHub Actions are green.
