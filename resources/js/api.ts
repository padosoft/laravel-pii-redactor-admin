export type AdminConfig = {
  apiBase: string;
  csrfToken: string;
  routePrefix: string;
  userDisplay: string;
  abilities: { view: boolean; detokenise: boolean; rawSamples: boolean };
};

declare global {
  interface Window {
    PII_REDACTOR_ADMIN?: AdminConfig;
  }
}

export class AdminApiError extends Error {
  constructor(
    message: string,
    public readonly status: number,
    public readonly payload: unknown,
  ) {
    super(message);
  }
}

export function getAdminConfig(): AdminConfig {
  const config = window.PII_REDACTOR_ADMIN;
  if (!config) {
    throw new Error('Missing PII_REDACTOR_ADMIN config.');
  }

  return config;
}

function resolveAdminUrl(path: string): URL {
  const config = getAdminConfig();
  const base = new URL(config.apiBase.replace(/\/+$/, '') + '/', window.location.origin);
  const url = new URL(path.replace(/^\/+/, ''), base);
  if (url.origin !== window.location.origin) {
    throw new Error('Admin API requests must stay on the current origin.');
  }

  return url;
}

export async function adminFetch<T>(path: string, options: RequestInit = {}): Promise<T> {
  const config = getAdminConfig();
  const headers = new Headers(options.headers);
  headers.set('Accept', 'application/json');

  if (options.body !== undefined && !headers.has('Content-Type')) {
    headers.set('Content-Type', 'application/json');
  }

  if (!['GET', 'HEAD'].includes((options.method ?? 'GET').toUpperCase())) {
    headers.set('X-CSRF-TOKEN', config.csrfToken);
  }

  const response = await fetch(resolveAdminUrl(path), {
    ...options,
    headers,
    credentials: 'same-origin',
  });

  const text = await response.text();
  let payload: unknown = null;
  if (text !== '') {
    try {
      payload = JSON.parse(text);
    } catch {
      payload = { message: text };
    }
  }

  if (!response.ok) {
    const message = typeof payload === 'object' && payload !== null && 'message' in payload
      ? String((payload as { message: unknown }).message)
      : `Request failed with status ${response.status}.`;
    throw new AdminApiError(message, response.status, payload);
  }

  return payload as T;
}

export function buildAdminQuery(filters: Record<string, string>): string {
  const params = new URLSearchParams();
  Object.entries(filters).forEach(([key, value]) => {
    if (value.trim() !== '') {
      params.set(key, value.trim());
    }
  });

  const query = params.toString();

  return query === '' ? '' : `?${query}`;
}
