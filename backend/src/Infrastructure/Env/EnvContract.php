<?php

declare(strict_types=1);

namespace AegisThread\Infrastructure\Env;

/**
 * The environment contract: variables the application cannot boot without.
 *
 * Single source of truth for fail-fast boot (BootEnvGuard) and for the
 * set/unset visibility section of GET /api/configuration. Values are never
 * exposed through either path.
 */
final class EnvContract
{
    /**
     * Required environment variable => human-readable purpose.
     *
     * @var array<string, string>
     */
    public const REQUIRED = [
        'DATABASE_URL' => 'PostgreSQL connection DSN',
        'APP_SECRET' => 'Framework secret',
    ];
}
