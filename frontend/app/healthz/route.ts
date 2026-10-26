// Container healthcheck target (compose `frontend` service): proves the
// standalone Node server process is accepting HTTP. Static by design — no
// runtime data access, so Cache Components prerenders it at build time.
export function GET(): Response {
  return new Response("ok\n", {
    status: 200,
    headers: { "content-type": "text/plain; charset=utf-8" },
  });
}
