import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Container-friendly build: emits a self-contained server bundle
  // (.next/standalone/server.js) that frontend/Dockerfile runs on a minimal
  // node:26-alpine image.
  output: "standalone",
  // Kept from the create-next-app scaffold (Next 16 defaults): data fetching
  // is request-time unless wrapped in `use cache`, which is exactly the
  // model the live status page needs (fresh probe on every request).
  cacheComponents: true,
  partialPrefetching: true,
};

export default nextConfig;
