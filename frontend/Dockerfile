# AegisThread frontend — multi-stage build producing a standalone Next.js
# server (next.config.ts `output: "standalone"`).
#
# deps/builder: node:26 (full Debian image — npm + build tooling).
# runner:       node:26-alpine (verified tag; busybox wget is present for
#               the compose healthcheck) running only the standalone bundle.

FROM node:26 AS deps
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci

FROM node:26 AS builder
WORKDIR /app
COPY --from=deps /app/node_modules ./node_modules
COPY . .
ENV NEXT_TELEMETRY_DISABLED=1
RUN npm run build

FROM node:26-alpine AS runner
WORKDIR /app
ENV NODE_ENV=production \
    NEXT_TELEMETRY_DISABLED=1 \
    PORT=3000 \
    HOSTNAME=0.0.0.0
RUN addgroup --system --gid 1001 nodejs \
    && adduser --system --uid 1001 nextjs
COPY --from=builder /app/public ./public
COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static
USER nextjs
EXPOSE 3000
CMD ["node", "server.js"]
