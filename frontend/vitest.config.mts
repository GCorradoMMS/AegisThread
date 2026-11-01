import { defineConfig } from "vitest/config";

export default defineConfig({
  test: {
    // Node environment, no jsdom: StatusView is tested through
    // react-dom/server's renderToString (synchronous server render),
    // so no DOM implementation is needed.
    environment: "node",
    include: ["app/**/*.test.tsx", "app/**/*.test.ts"],
  },
});
