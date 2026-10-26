<?php

declare(strict_types=1);

namespace AegisThread\Application\Configuration;

/**
 * Migrations section of the configuration report.
 *
 * State mapping: up-to-date when no available migration is pending;
 * pending otherwise; unknown when the state cannot be read (e.g. database
 * unreachable) — degraded data, not an error.
 */
final readonly class MigrationsSection implements \JsonSerializable
{
    public const STATE_UP_TO_DATE = 'up-to-date';
    public const STATE_PENDING = 'pending';
    public const STATE_UNKNOWN = 'unknown';

    public function __construct(
        public readonly string $state,
        public readonly int $executed,
        public readonly ?string $latest,
    ) {
    }

    /**
     * @return array{state: string, executed: int, latest: string|null}
     */
    public function jsonSerialize(): array
    {
        return [
            'state' => $this->state,
            'executed' => $this->executed,
            'latest' => $this->latest,
        ];
    }
}
