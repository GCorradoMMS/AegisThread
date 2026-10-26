<?php

declare(strict_types=1);

namespace AegisThread\Application\Configuration;

/**
 * Database section of the configuration report.
 *
 * `reachable` reflects live connectivity; `serverVersion` is null when it
 * cannot be determined (e.g. database unreachable).
 */
final readonly class DatabaseSection implements \JsonSerializable
{
    public function __construct(
        public readonly bool $reachable,
        public readonly ?string $serverVersion,
    ) {
    }

    /**
     * @return array{reachable: bool, serverVersion: string|null}
     */
    public function jsonSerialize(): array
    {
        return [
            'reachable' => $this->reachable,
            'serverVersion' => $this->serverVersion,
        ];
    }
}
