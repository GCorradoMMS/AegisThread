// AegisThread — live backend status view (M1 Pass C).
//
// Server component: every request fetches the backend health endpoint
// server-side and renders exactly one of three atomic states (healthy /
// degraded / unavailable). The fetch and the three-state view live in
// app/status.tsx — extracted so the synchronous units are unit-testable
// (Vitest does not support async Server Components).
//
// Next 16 Cache Components note: uncached data is request-time by default.
// `instant = false` opts this page out of the static-shell validation: the
// whole page body is the probe result, so a prerendered shell would be
// empty anyway. There is deliberately no Suspense boundary to keep the
// status page a single atomic state.

import { fetchBackendHealth, StatusView } from "./status";

export const instant = false;

export default async function Home() {
  return <StatusView health={await fetchBackendHealth()} />;
}
