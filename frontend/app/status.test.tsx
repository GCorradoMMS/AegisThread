// AegisThread — unit tests for the status view implementation.
//
// Scope: the synchronous units extracted into app/status.tsx (parsing,
// the three-state view, the fetch contract). The async Home Server
// Component itself is not unit-tested — Vitest does not support async
// Server Components (bundled Next.js testing guide); its only logic is
// `await fetchBackendHealth()` handed to StatusView, and the end-to-end
// states are exercised by the compose-stack smokes in CI.
//
// renderToString runs synchronously in the node environment — no jsdom.

import { describe, expect, it, vi, beforeEach, afterEach } from "vitest";
import { renderToString } from "react-dom/server";
import {
  fetchBackendHealth,
  parseBackendHealth,
  StatusView,
  type BackendHealth,
} from "./status";

const validHealth = (status: "healthy" | "degraded"): BackendHealth => ({
  status,
  checks: { database: "up" },
  checkedAt: "2026-10-31T12:00:00+00:00",
});

describe("parseBackendHealth", () => {
  it("accepts a valid healthy report", () => {
    const payload = {
      status: "healthy",
      checks: { database: "up" },
      checkedAt: "2026-10-31T12:00:00+00:00",
    };
    expect(parseBackendHealth(payload)).toEqual({
      status: "healthy",
      checks: { database: "up" },
      checkedAt: "2026-10-31T12:00:00+00:00",
    });
  });

  it("accepts a valid degraded report", () => {
    const payload = {
      status: "degraded",
      checks: { database: "down" },
      checkedAt: "2026-10-31T12:00:00+00:00",
    };
    expect(parseBackendHealth(payload)?.status).toBe("degraded");
    expect(parseBackendHealth(payload)?.checks.database).toBe("down");
  });

  it("rejects an unknown status", () => {
    expect(
      parseBackendHealth({
        status: "error",
        checks: { database: "up" },
        checkedAt: "2026-10-31T12:00:00+00:00",
      }),
    ).toBeNull();
  });

  it("rejects non-object payloads", () => {
    expect(parseBackendHealth("healthy")).toBeNull();
    expect(parseBackendHealth(42)).toBeNull();
    expect(parseBackendHealth(null)).toBeNull();
    expect(parseBackendHealth(undefined)).toBeNull();
  });

  it("rejects arrays", () => {
    expect(parseBackendHealth([1, 2, 3])).toBeNull();
  });

  it("rejects a report whose checks.database is not a string", () => {
    expect(
      parseBackendHealth({
        status: "healthy",
        checks: { database: 1 },
        checkedAt: "2026-10-31T12:00:00+00:00",
      }),
    ).toBeNull();
    expect(
      parseBackendHealth({
        status: "healthy",
        checks: "up",
        checkedAt: "2026-10-31T12:00:00+00:00",
      }),
    ).toBeNull();
  });

  it("rejects a report whose checkedAt is not a string", () => {
    expect(
      parseBackendHealth({
        status: "healthy",
        checks: { database: "up" },
        checkedAt: 1730376000000,
      }),
    ).toBeNull();
  });

  it("rejects a status with wrong casing — contract values are strict", () => {
    expect(
      parseBackendHealth({
        status: "Healthy",
        checks: { database: "up" },
        checkedAt: "2026-10-31T12:00:00+00:00",
      }),
    ).toBeNull();
  });

  it("accepts an empty database check string — value content is lenient", () => {
    expect(
      parseBackendHealth({
        status: "healthy",
        checks: { database: "" },
        checkedAt: "2026-10-31T12:00:00+00:00",
      }),
    ).toEqual({
      status: "healthy",
      checks: { database: "" },
      checkedAt: "2026-10-31T12:00:00+00:00",
    });
  });

  it("projects unknown extra fields away — top-level and inside checks", () => {
    const payload = {
      status: "healthy",
      checks: { database: "up", latencyMs: 3 },
      checkedAt: "2026-10-31T12:00:00+00:00",
      extra: "future",
    };
    expect(parseBackendHealth(payload)).toEqual({
      status: "healthy",
      checks: { database: "up" },
      checkedAt: "2026-10-31T12:00:00+00:00",
    });
  });

  it("rejects a report whose checks is an empty array", () => {
    expect(
      parseBackendHealth({
        status: "healthy",
        checks: [],
        checkedAt: "2026-10-31T12:00:00+00:00",
      }),
    ).toBeNull();
  });
});

describe("StatusView", () => {
  // React's server renderer emits <!-- --> delimiters between adjacent text
  // nodes; strip that rendering artifact so assertions check visible text.
  const renderText = (health: BackendHealth | null): string =>
    renderToString(<StatusView health={health} />).replaceAll("<!-- -->", "");

  // The three states differ only by the inline color (#b91c1c unavailable /
  // #b45309 degraded / #15803d healthy), so the color is the state signal:
  // asserting it pins the state design — a deleted or swapped branch now
  // fails the suite, not just text the healthy branch would also render.
  it("renders the unavailable state for null", () => {
    const html = renderText(null);
    expect(html).toContain("Backend unavailable");
    expect(html).toContain("#b91c1c");
  });

  it("renders the degraded state in the degraded color", () => {
    const html = renderText(validHealth("degraded"));
    expect(html).toContain("degraded");
    expect(html).toContain("database: up");
    expect(html).toContain("checked at 2026-10-31T12:00:00+00:00");
    expect(html).toContain("#b45309");
    expect(html).not.toContain("#15803d");
  });

  it("renders the healthy state in the healthy color and never the degraded word", () => {
    const html = renderText(validHealth("healthy"));
    expect(html).toContain("healthy");
    expect(html).not.toContain("degraded");
    expect(html).not.toContain("Backend unavailable");
    expect(html).toContain("#15803d");
    expect(html).not.toContain("#b45309");
  });
});

describe("fetchBackendHealth", () => {
  const okJson = (body: unknown, status: number): Response =>
    new Response(JSON.stringify(body), { status });

  beforeEach(() => {
    vi.stubGlobal(
      "fetch",
      vi.fn(async () => okJson(validHealth("healthy"), 200)),
    );
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    vi.unstubAllEnvs();
  });

  it("returns the parsed report for a 200 response", async () => {
    await expect(fetchBackendHealth()).resolves.toEqual(validHealth("healthy"));
  });

  it("returns the parsed report for a 503 (degraded) response", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn(async () => okJson(validHealth("degraded"), 503)),
    );
    await expect(fetchBackendHealth()).resolves.toEqual(
      validHealth("degraded"),
    );
  });

  it("returns null for any other status code", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn(async () => okJson(validHealth("healthy"), 500)),
    );
    await expect(fetchBackendHealth()).resolves.toBeNull();
  });

  it("returns null when fetch rejects — a timeout collapses into the same catch branch as a rejection", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn(async () => {
        throw new Error("network down");
      }),
    );
    await expect(fetchBackendHealth()).resolves.toBeNull();
  });

  it("returns null on invalid JSON", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn(async () => new Response("not json", { status: 200 })),
    );
    await expect(fetchBackendHealth()).resolves.toBeNull();
  });

  it("returns null on a schema-mismatched body", async () => {
    vi.stubGlobal(
      "fetch",
      vi.fn(async () => okJson({ status: "ok" }, 200)),
    );
    await expect(fetchBackendHealth()).resolves.toBeNull();
  });

  it("honors BACKEND_BASE_URL", async () => {
    vi.stubEnv("BACKEND_BASE_URL", "http://backend:8080");
    await fetchBackendHealth();
    const fetchMock = vi.mocked(globalThis.fetch);
    expect(fetchMock.mock.calls[0]?.[0]).toBe("http://backend:8080/api/health");
  });

  it("falls back to http://localhost:8081 when BACKEND_BASE_URL is unset", async () => {
    vi.stubEnv("BACKEND_BASE_URL", undefined);
    await fetchBackendHealth();
    const fetchMock = vi.mocked(globalThis.fetch);
    expect(fetchMock.mock.calls[0]?.[0]).toBe("http://localhost:8081/api/health");
  });

  it("passes the no-store cache mode and a timeout signal to fetch", async () => {
    await fetchBackendHealth();
    const fetchMock = vi.mocked(globalThis.fetch);
    expect(fetchMock.mock.calls[0]?.[1]).toMatchObject({ cache: "no-store" });
    const init = fetchMock.mock.calls[0]?.[1] as RequestInit;
    expect(init.signal).toBeTruthy();
    expect(init.signal).toBeInstanceOf(AbortSignal);
  });
});
