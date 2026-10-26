// AegisThread — live backend status view (M1 Pass C).
//
// Server component: every request fetches the backend health endpoint
// server-side (BACKEND_BASE_URL, server-only — never NEXT_PUBLIC_, so it
// cannot leak into the client bundle).
//
// Next 16 Cache Components note: uncached data is request-time by default.
// `instant = false` opts this page out of the static-shell validation: the
// whole page body is the probe result, so a prerendered shell would be
// empty anyway. There is deliberately no Suspense boundary to keep the
// status page a single atomic state (healthy / degraded / unavailable).
//
// All failure modes — network error, timeout (2 s), non-2xx/503-only
// responses outside the contract, invalid JSON, schema mismatch — collapse
// into one distinct "Backend unavailable" state.

export const instant = false;

/** The allowlist contract of GET /api/health (see backend HealthReport). */
type BackendHealth = {
  status: "healthy" | "degraded";
  checks: { database: string };
  checkedAt: string;
};

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === "object" && value !== null && !Array.isArray(value);
}

/**
 * Hand-rolled structural type guard: every field must match the contract,
 * else the payload is rejected (null) and rendered as "unavailable".
 */
function parseBackendHealth(payload: unknown): BackendHealth | null {
  if (!isRecord(payload)) {
    return null;
  }
  const { status, checks, checkedAt } = payload;
  if (status !== "healthy" && status !== "degraded") {
    return null;
  }
  if (!isRecord(checks) || typeof checks.database !== "string") {
    return null;
  }
  if (typeof checkedAt !== "string") {
    return null;
  }
  return { status, checks: { database: checks.database }, checkedAt };
}

async function fetchBackendHealth(): Promise<BackendHealth | null> {
  // Fallback = the backend's published HOST port (127.0.0.1:8081), for
  // running the dev server outside compose. In the compose stack the
  // container env always sets BACKEND_BASE_URL (http://backend:8080).
  const baseUrl = process.env.BACKEND_BASE_URL ?? "http://localhost:8081";
  try {
    const response = await fetch(`${baseUrl}/api/health`, {
      cache: "no-store",
      signal: AbortSignal.timeout(2000),
    });
    // 200 (healthy) and 503 (degraded) both carry a health report body;
    // any other status is treated as unavailable.
    if (response.status !== 200 && response.status !== 503) {
      return null;
    }
    return parseBackendHealth(await response.json());
  } catch {
    return null;
  }
}

const shell: React.CSSProperties = {
  minHeight: "100dvh",
  display: "flex",
  flexDirection: "column",
  alignItems: "center",
  justifyContent: "center",
  gap: "0.75rem",
  fontFamily: "system-ui, sans-serif",
  margin: "0 auto",
  padding: "2rem",
  textAlign: "center",
};

const statusWord: React.CSSProperties = {
  fontSize: "2rem",
  fontWeight: 700,
  margin: 0,
  textTransform: "uppercase",
  letterSpacing: "0.05em",
};

const detail: React.CSSProperties = {
  color: "#555",
  margin: 0,
  fontSize: "0.95rem",
};

export default async function Home() {
  const health = await fetchBackendHealth();

  if (health === null) {
    return (
      <main style={shell}>
        <h1>AegisThread</h1>
        <p style={{ ...statusWord, color: "#b91c1c" }}>Backend unavailable</p>
        <p style={detail}>
          The backend did not answer with a valid health report.
        </p>
      </main>
    );
  }

  if (health.status === "degraded") {
    return (
      <main style={shell}>
        <h1>AegisThread</h1>
        <p style={{ ...statusWord, color: "#b45309" }}>{health.status}</p>
        <p style={detail}>
          database: {health.checks.database} — checked at {health.checkedAt}
        </p>
      </main>
    );
  }

  return (
    <main style={shell}>
      <h1>AegisThread</h1>
      <p style={{ ...statusWord, color: "#15803d" }}>{health.status}</p>
      <p style={detail}>
        database: {health.checks.database} — checked at {health.checkedAt}
      </p>
    </main>
  );
}
