import React, { useEffect, useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';
import { Activity, Database, EyeOff, FileSearch, Filter, Gauge, KeyRound, ListChecks, LoaderCircle, Moon, PackageCheck, RotateCcw, Search, Settings, ShieldAlert, Sun } from 'lucide-react';
import { AdminApiError, adminFetch, buildAdminQuery, getAdminConfig } from './api';

type Page = 'overview' | 'playground' | 'audit' | 'tokens' | 'detokenise' | 'detectors' | 'custom-rules' | 'settings';
type StatusPayload = { package: Record<string, unknown>; strategies: string[]; snapshot: Record<string, any> };
type DataRow = Record<string, unknown>;

const nav: Array<{ page: Page; label: string; icon: React.ComponentType<{ size?: number }> }> = [
  { page: 'overview', label: 'Overview', icon: Gauge },
  { page: 'playground', label: 'Playground', icon: FileSearch },
  { page: 'audit', label: 'Audit log', icon: ListChecks },
  { page: 'tokens', label: 'Token map', icon: Database },
  { page: 'detokenise', label: 'Detokenise', icon: ShieldAlert },
  { page: 'detectors', label: 'Detectors', icon: PackageCheck },
  { page: 'custom-rules', label: 'Custom rules', icon: Activity },
  { page: 'settings', label: 'Settings', icon: Settings },
];

function App() {
  const config = getAdminConfig();
  const [page, setPage] = useState<Page>('overview');
  const [dark, setDark] = useState(true);
  const [status, setStatus] = useState<StatusPayload | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    document.documentElement.dataset.theme = dark ? 'dark' : 'light';
  }, [dark]);

  useEffect(() => {
    adminFetch<StatusPayload>('status').then(setStatus).catch((e) => setError(errorMessage(e)));
  }, []);

  useEffect(() => {
    const onKey = (event: KeyboardEvent) => {
      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        setPage('playground');
      }
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, []);

  return (
    <div className="pra-shell">
      <aside className="pra-sidebar">
        <div className="pra-brand">
          <div className="pra-mark"><KeyRound size={18} /></div>
          <div>
            <strong>PII Redactor</strong>
            <span>Admin console</span>
          </div>
        </div>
        <nav className="pra-nav">
          {nav.map(({ page: item, label, icon: Icon }) => (
            <button key={item} className={page === item ? 'is-active' : ''} onClick={() => setPage(item)}>
              <Icon size={16} /> {label}
            </button>
          ))}
        </nav>
      </aside>
      <main className="pra-main">
        <header className="pra-topbar">
          <div>
            <h1>{nav.find((item) => item.page === page)?.label}</h1>
            <p>{config.userDisplay} · safe operational access</p>
          </div>
          <div className="pra-actions">
            <button title="Open playground" onClick={() => setPage('playground')}><Search size={16} /> Ctrl K</button>
            <button title="Toggle theme" onClick={() => setDark(!dark)}>{dark ? <Sun size={16} /> : <Moon size={16} />}</button>
          </div>
        </header>
        {error && <div className="pra-alert">{error}</div>}
        <PageView page={page} status={status} abilities={config.abilities} />
      </main>
    </div>
  );
}

function PageView({ page, status, abilities }: { page: Page; status: StatusPayload | null; abilities: ReturnType<typeof getAdminConfig>['abilities'] }) {
  if (page === 'playground') return <Playground abilities={abilities} strategies={status?.strategies ?? ['mask', 'hash', 'tokenise', 'drop']} />;
  if (page === 'detokenise') return <Detokenise abilities={abilities} />;
  if (page === 'tokens') return <DataBrowser kind="tokens" endpoint="token-maps" rootKey="maps" empty="Token metadata is unavailable for this token-store driver." />;
  if (page === 'audit') return <DataBrowser kind="audit" endpoint="audit-events" rootKey="data" empty="No admin audit events yet." />;
  if (page === 'detectors') return <Detectors />;
  if (page === 'custom-rules') return <CustomRules />;
  if (page === 'settings') return <JsonPanel endpoint="settings" />;
  return <Overview status={status} />;
}

function Overview({ status }: { status: StatusPayload | null }) {
  const snapshot = status?.snapshot;
  const cards = [
    ['Engine', snapshot?.enabled ? 'Enabled' : 'Disabled'],
    ['Strategy', snapshot?.default_strategy ?? 'loading'],
    ['Token store', snapshot?.token_store?.driver ?? 'loading'],
    ['Detectors', String(snapshot?.detectors?.length ?? 0)],
  ];
  return <section className="pra-grid">{cards.map(([label, value]) => <MetricPanel key={label} label={label} value={value} />)}</section>;
}

function Playground({ abilities, strategies }: { abilities: { rawSamples: boolean }; strategies: string[] }) {
  const [text, setText] = useState('Mario Rossi email mario.rossi@example.test IBAN IT60X0542811101000000123456');
  const [strategy, setStrategy] = useState(strategies[0] ?? 'mask');
  const [raw, setRaw] = useState(false);
  const [result, setResult] = useState<unknown>(null);
  const [error, setError] = useState<string | null>(null);

  async function run(kind: 'scan' | 'redact') {
    setError(null);
    try {
      setResult(await adminFetch(kind, { method: 'POST', body: JSON.stringify({ text, strategy, include_raw_samples: raw }) }));
    } catch (e) {
      setError(errorMessage(e));
    }
  }

  return <section className="pra-workbench">
    <textarea value={text} onChange={(event) => setText(event.target.value)} />
    <div className="pra-toolbar">
      {strategies.map((name) => <button key={name} className={strategy === name ? 'is-active' : ''} onClick={() => setStrategy(name)}>{name}</button>)}
      <label><input type="checkbox" checked={raw} disabled={!abilities.rawSamples} onChange={(event) => setRaw(event.target.checked)} /> raw samples</label>
      <button onClick={() => run('scan')}>Scan</button>
      <button onClick={() => run('redact')}>Redact</button>
    </div>
    {error && <div className="pra-alert">{error}</div>}
    <pre>{JSON.stringify(result, null, 2)}</pre>
  </section>;
}

function Detokenise({ abilities }: { abilities: { detokenise: boolean } }) {
  const [text, setText] = useState('[tok:email:012345abcdef]');
  const [justification, setJustification] = useState('');
  const [ack, setAck] = useState(false);
  const [armed, setArmed] = useState(false);
  const [result, setResult] = useState<unknown>(null);
  const [error, setError] = useState<string | null>(null);
  const canReveal = abilities.detokenise && justification.length >= 10 && ack && armed;

  useEffect(() => {
    setArmed(false);
    setResult(null);
    setError(null);
  }, [text, justification, ack]);

  async function reveal() {
    setError(null);
    try {
      setResult(await adminFetch('detokenise', { method: 'POST', body: JSON.stringify({ text, justification }) }));
    } catch (e) {
      setError(errorMessage(e));
    }
  }

  return <section className="pra-workbench danger">
    <p>Detokenise can reveal raw originals. Results are displayed only after confirmation and are not stored by this UI.</p>
    {!abilities.detokenise && <InlineNotice tone="danger">The configured detokenise ability is denied for this operator.</InlineNotice>}
    <textarea value={text} onChange={(event) => setText(event.target.value)} />
    <input value={justification} onChange={(event) => setJustification(event.target.value)} placeholder="Justification, at least 10 characters" />
    <label><input type="checkbox" checked={ack} onChange={(event) => setAck(event.target.checked)} /> I understand this reveals sensitive data.</label>
    <div className="pra-toolbar"><button disabled={!abilities.detokenise} onClick={() => setArmed(true)}>Arm reveal</button><button disabled={!canReveal} onClick={reveal}>Reveal</button></div>
    {error && <InlineNotice tone="danger">{error}</InlineNotice>}
    <pre>{JSON.stringify(result, null, 2)}</pre>
  </section>;
}

function DataBrowser({ kind, endpoint, rootKey, empty }: { kind: 'audit' | 'tokens'; endpoint: string; rootKey: string; empty: string }) {
  const [filters, setFilters] = useState({ search: '', detector: '', event_type: '', status_code: '' });
  const [applied, setApplied] = useState(filters);
  const [data, setData] = useState<unknown>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const query = useMemo(() => buildAdminQuery(kind === 'tokens'
    ? { search: applied.search, detector: applied.detector }
    : { event_type: applied.event_type, status_code: applied.status_code }), [applied, kind]);

  useEffect(() => {
    setLoading(true);
    setError(null);
    adminFetch(`${endpoint}${query}`)
      .then(setData)
      .catch((e) => setError(errorMessage(e)))
      .finally(() => setLoading(false));
  }, [endpoint, query]);

  const rows = useMemo(() => {
    const root = data && typeof data === 'object' && rootKey in data ? (data as Record<string, any>)[rootKey] : data;
    return Array.isArray(root?.data) ? root.data : Array.isArray(root) ? root : [];
  }, [data, rootKey]);

  function reset() {
    const cleared = { search: '', detector: '', event_type: '', status_code: '' };
    setFilters(cleared);
    setApplied(cleared);
  }

  return <section className="pra-workbench">
    <div className="pra-filterbar">
      {kind === 'tokens' ? <>
        <label>Search token<input value={filters.search} onChange={(event) => setFilters({ ...filters, search: event.target.value })} placeholder="[tok:email" /></label>
        <label>Detector<input value={filters.detector} onChange={(event) => setFilters({ ...filters, detector: event.target.value })} placeholder="email" /></label>
      </> : <>
        <label>Event type<input value={filters.event_type} onChange={(event) => setFilters({ ...filters, event_type: event.target.value })} placeholder="detokenise.denied" /></label>
        <label>Status<input value={filters.status_code} onChange={(event) => setFilters({ ...filters, status_code: event.target.value })} placeholder="403" inputMode="numeric" /></label>
      </>}
      <button onClick={() => setApplied(filters)}><Filter size={16} /> Apply</button>
      <button onClick={reset}><RotateCcw size={16} /> Reset</button>
    </div>
    {loading && <EmptyState icon={LoaderCircle} label="Loading records..." />}
    {error && <InlineNotice tone="danger">{error}</InlineNotice>}
    {!loading && !error && rows.length === 0 && <EmptyState icon={EyeOff} label={empty} />}
    {!loading && !error && rows.length > 0 && <DataTable rows={rows} />}
  </section>;
}

function Detectors() {
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setLoading(true);
    setError(null);
    adminFetch('detectors')
      .then(setData)
      .catch((e) => setError(errorMessage(e)))
      .finally(() => setLoading(false));
  }, []);

  const detectors = data?.detectors ?? [];

  return <section className="pra-grid">
    {loading && <EmptyState icon={LoaderCircle} label="Loading detectors..." />}
    {error && <InlineNotice tone="danger">{error}</InlineNotice>}
    {!loading && !error && detectors.length === 0 && <EmptyState icon={EyeOff} label="No detectors are configured." />}
    {!loading && !error && detectors.map((detector: any) => <MetricPanel key={detector.name} label={detector.class} value={detector.name} />)}
  </section>;
}

function CustomRules() {
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setLoading(true);
    setError(null);
    adminFetch('custom-rules')
      .then(setData)
      .catch((e) => setError(errorMessage(e)))
      .finally(() => setLoading(false));
  }, []);

  const packs = data?.packs ?? [];

  return <section className="pra-grid">
    {loading && <EmptyState icon={LoaderCircle} label="Loading custom rules..." />}
    {error && <InlineNotice tone="danger">{error}</InlineNotice>}
    {!loading && !error && packs.length === 0 && <EmptyState icon={EyeOff} label="No custom rule packs are configured." />}
    {!loading && !error && packs.map((pack: any) => <div className="pra-panel" key={pack.name || pack.path}><span>{pack.path}</span><strong>{pack.name || 'Invalid pack'}</strong><p>{pack.valid ? `${pack.rule_count} rules` : pack.error}</p></div>)}
  </section>;
}

function JsonPanel({ endpoint }: { endpoint: string }) {
  const [data, setData] = useState<unknown>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    setLoading(true);
    setError(null);
    adminFetch(endpoint)
      .then(setData)
      .catch((e) => setError(errorMessage(e)))
      .finally(() => setLoading(false));
  }, [endpoint]);

  if (loading) {
    return <EmptyState icon={LoaderCircle} label="Loading settings..." />;
  }

  if (error) {
    return <InlineNotice tone="danger">{error}</InlineNotice>;
  }

  return <pre>{JSON.stringify(data, null, 2)}</pre>;
}

function errorMessage(error: unknown) {
  return error instanceof AdminApiError || error instanceof Error ? error.message : 'Request failed.';
}

function MetricPanel({ label, value }: { label: string; value: string }) {
  return <div className="pra-panel"><span>{label}</span><strong>{value}</strong></div>;
}

function EmptyState({ icon: Icon, label }: { icon: React.ComponentType<{ size?: number; className?: string }>; label: string }) {
  return <div className="pra-empty"><Icon size={18} className="pra-empty-icon" /> {label}</div>;
}

function InlineNotice({ children, tone = 'default' }: { children: React.ReactNode; tone?: 'default' | 'danger' }) {
  return <div className={tone === 'danger' ? 'pra-alert compact' : 'pra-notice'}>{children}</div>;
}

function DataTable({ rows }: { rows: DataRow[] }) {
  const safeRows = rows.map((row) => Object.fromEntries(Object.entries(row).filter(([key]) => !['original', 'raw_text', 'redacted_output', 'detokenised_output'].includes(key))));

  return <table className="pra-table"><tbody>{safeRows.map((row, i) => <tr key={i}>{Object.entries(row).map(([k, v]) => <td key={k}><span>{k}</span>{formatValue(v)}</td>)}</tr>)}</tbody></table>;
}

function formatValue(value: unknown) {
  if (value === null || value === undefined) {
    return 'null';
  }

  if (typeof value === 'object') {
    return JSON.stringify(value);
  }

  return String(value);
}

createRoot(document.getElementById('pii-redactor-admin-root')!).render(<App />);
