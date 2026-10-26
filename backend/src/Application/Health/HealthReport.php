<?php

declare(strict_types=1);

namespace AegisThread\Application\Health;

/**
 * Immutable outcome of the CheckHealth use case.
 *
 * The JSON representation (JsonSerializable) is the allowlist contract of
 * GET /api/health: exactly the keys `status`, `checks`, `checkedAt`. It can
 * never carry exception text or stack traces.
 */
final readonly class HealthReport implements \JsonSerializable
{
    public const STATUS_HEALTHY = 'healthy';
    public const STATUS_DEGRADED = 'degraded';

    public const CHECK_DATABASE = 'database';
    public const CHECK_UP = 'up';
    public const CHECK_DOWN = 'down';

    /**
     * @param array<string, string> $checks check name => 'up'|'down'
     */
    public function __construct(
        public readonly string $status,
        public readonly array $checks,
        public readonly \DateTimeImmutable $checkedAt,
    ) {
    }

    public function isHealthy(): bool
    {
        return self::STATUS_HEALTHY === $this->status;
    }

    /**
     * @return array{status: string, checks: array<string, string>, checkedAt: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'status' => $this->status,
            'checks' => $this->checks,
            'checkedAt' => $this->checkedAt->format(\DATE_RFC3339),
        ];
    }
}
