import { expect, test } from '@playwright/test';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const fixtures = require('../../resources/demo/admin-api-fixtures.json');

test.beforeEach(async ({ page }) => {
  await page.route('**/pii-redactor-admin/api/**', async (route) => {
    const url = new URL(route.request().url());
    const path = url.pathname.split('/api/')[1];
    const body = route.request().postDataJSON?.() ?? {};
    const auditRows = fixtures.auditEvents.filter((row) => {
      const eventType = url.searchParams.get('event_type');
      const statusCode = url.searchParams.get('status_code');
      return (!eventType || row.event_type === eventType) && (!statusCode || String(row.status_code) === statusCode);
    });
    const tokenRows = fixtures.tokenMaps.filter((row) => {
      const search = url.searchParams.get('search');
      const detector = url.searchParams.get('detector');
      return (!search || row.token.includes(search)) && (!detector || row.detector === detector);
    });
    const payloads: Record<string, unknown> = {
      status: fixtures.status,
      detectors: fixtures.detectors,
      'custom-rules': fixtures.customRules,
      settings: fixtures.settings,
      'audit-events': { data: auditRows },
      'token-maps': { available: true, maps: { data: tokenRows } },
      scan: fixtures.scan,
      redact: { ...fixtures.redact, strategy: body.strategy ?? fixtures.redact.strategy },
      detokenise: fixtures.detokenise,
    };
    await route.fulfill({ json: payloads[path] ?? {} });
  });
});

test('overview loads and command shortcut navigates to playground', async ({ page }) => {
  await page.goto('/tests/e2e/fixtures/index.html');
  await expect(page.getByRole('heading', { name: 'Overview' })).toBeVisible();
  await expect(page.getByText('database')).toBeVisible();
  await page.keyboard.press(process.platform === 'darwin' ? 'Meta+K' : 'Control+K');
  await expect(page.getByRole('heading', { name: 'Playground' })).toBeVisible();
});

test('playground scans and redacts while raw samples are disabled', async ({ page }) => {
  await page.goto('/tests/e2e/fixtures/index.html');
  await page.getByRole('button', { name: 'Playground' }).click();
  await expect(page.getByLabel('raw samples')).toBeDisabled();
  await page.getByRole('button', { name: 'Scan' }).click();
  await expect(page.getByText('"[email]"')).toBeVisible();
  await page.getByRole('button', { name: 'tokenise', exact: true }).click();
  await page.getByRole('button', { name: 'Redact' }).click();
  await expect(page.getByText('"output": "[REDACTED]"')).toBeVisible();
});

test('token map omits original and detokenise requires arm flow', async ({ page }) => {
  await page.goto('/tests/e2e/fixtures/index.html');
  await page.getByRole('button', { name: 'Token map' }).click();
  await expect(page.getByText('[tok:email:abcdef012345]')).toBeVisible();
  await expect(page.getByText('original')).toHaveCount(0);
  await page.getByRole('button', { name: 'Detokenise' }).click();
  await expect(page.getByRole('button', { name: 'Reveal', exact: true })).toBeDisabled();
  await page.getByPlaceholder('Justification, at least 10 characters').fill('incident response');
  await page.getByLabel('I understand this reveals sensitive data.').check();
  await page.getByRole('button', { name: 'Arm reveal' }).click();
  await page.getByRole('button', { name: 'Reveal', exact: true }).click();
  await expect(page.getByText('revealed@example.test')).toBeVisible();
  await page.getByPlaceholder('Justification, at least 10 characters').fill('changed reason');
  await expect(page.getByRole('button', { name: 'Reveal', exact: true })).toBeDisabled();
});

test('audit and token map filters update operational tables', async ({ page }) => {
  await page.goto('/tests/e2e/fixtures/index.html');
  await page.getByRole('button', { name: 'Audit log' }).click();
  await expect(page.getByText('detokenise.denied')).toBeVisible();
  await page.getByPlaceholder('detokenise.denied').fill('scan');
  await page.getByPlaceholder('403').fill('200');
  await page.getByRole('button', { name: 'Apply' }).click();
  await expect(page.getByText('scan')).toBeVisible();
  await expect(page.getByText('detokenise.denied')).toHaveCount(0);

  await page.getByRole('button', { name: 'Token map' }).click();
  await expect(page.getByText('[tok:iban:111122223333]')).toBeVisible();
  await page.getByRole('textbox', { name: 'Search token' }).fill('abcdef');
  await page.getByRole('textbox', { name: 'Detector' }).fill('email');
  await page.getByRole('button', { name: 'Apply' }).click();
  await expect(page.getByText('[tok:email:abcdef012345]')).toBeVisible();
  await expect(page.getByText('[tok:iban:111122223333]')).toHaveCount(0);
});

test('detectors custom rules settings and mobile layout render without overflow', async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 840 });
  await page.goto('/tests/e2e/fixtures/index.html');
  await page.getByRole('button', { name: 'Detectors' }).click();
  await expect(page.getByText('EmailDetector')).toBeVisible();
  await page.getByRole('button', { name: 'Custom rules' }).click();
  await expect(page.getByText('tenant_rules')).toBeVisible();
  await page.getByRole('button', { name: 'Settings' }).click();
  await expect(page.getByText('"strategy": "mask"')).toBeVisible();
  const width = await page.evaluate(() => document.documentElement.scrollWidth);
  expect(width).toBeLessThanOrEqual(390);
});

test('read-only registry pages surface API errors', async ({ page }) => {
  await page.route('**/pii-redactor-admin/api/detectors', async (route) => {
    await route.fulfill({ status: 500, json: { message: 'Detector registry unavailable' } });
  });
  await page.route('**/pii-redactor-admin/api/custom-rules', async (route) => {
    await route.fulfill({ status: 500, json: { message: 'Custom rules unavailable' } });
  });
  await page.route('**/pii-redactor-admin/api/settings', async (route) => {
    await route.fulfill({ status: 500, json: { message: 'Settings unavailable' } });
  });

  await page.goto('/tests/e2e/fixtures/index.html');
  await page.getByRole('button', { name: 'Detectors' }).click();
  await expect(page.getByText('Detector registry unavailable')).toBeVisible();

  await page.getByRole('button', { name: 'Custom rules' }).click();
  await expect(page.getByText('Custom rules unavailable')).toBeVisible();

  await page.getByRole('button', { name: 'Settings' }).click();
  await expect(page.getByText('Settings unavailable')).toBeVisible();
});
