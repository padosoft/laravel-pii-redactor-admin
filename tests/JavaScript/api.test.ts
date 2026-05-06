import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { AdminApiError, adminFetch, buildAdminQuery } from '../../resources/js/api';

describe('adminFetch', () => {
  beforeEach(() => {
    window.PII_REDACTOR_ADMIN = {
      apiBase: 'http://localhost/pii-redactor-admin/api',
      csrfToken: 'csrf-token',
      routePrefix: 'pii-redactor-admin',
      userDisplay: 'Tester',
      abilities: { view: true, detokenise: false, rawSamples: false },
    };
    Object.defineProperty(window, 'location', {
      value: new URL('http://localhost/pii-redactor-admin'),
      writable: true,
    });
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('attaches csrf headers for mutating same-origin requests', async () => {
    const fetchMock = vi.spyOn(window, 'fetch').mockResolvedValue(new Response(JSON.stringify({ ok: true }), { status: 200 }));
    await expect(adminFetch('scan', { method: 'POST', body: '{}' })).resolves.toEqual({ ok: true });
    const [, init] = fetchMock.mock.calls[0];
    expect((init?.headers as Headers).get('X-CSRF-TOKEN')).toBe('csrf-token');
    expect(init?.credentials).toBe('same-origin');
  });

  it('rejects cross-origin api bases before fetching', async () => {
    window.PII_REDACTOR_ADMIN!.apiBase = 'https://evil.example/api';
    const fetchMock = vi.spyOn(window, 'fetch');
    await expect(adminFetch('status')).rejects.toThrow('current origin');
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it('normalizes json errors', async () => {
    vi.spyOn(window, 'fetch').mockResolvedValue(new Response(JSON.stringify({ message: 'Forbidden' }), { status: 403 }));
    await expect(adminFetch('status')).rejects.toMatchObject(new AdminApiError('Forbidden', 403, { message: 'Forbidden' }));
  });

  it('builds compact encoded query strings for admin filters', () => {
    expect(buildAdminQuery({ search: ' abcdef ', detector: 'email', empty: '   ' })).toBe('?search=abcdef&detector=email');
    expect(buildAdminQuery({ event_type: 'detokenise.denied', status_code: '403' })).toBe('?event_type=detokenise.denied&status_code=403');
    expect(buildAdminQuery({ search: '' })).toBe('');
  });
});
