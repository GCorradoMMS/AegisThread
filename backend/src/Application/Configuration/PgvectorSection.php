<?php

declare(strict_types=1);

namespace AegisThread\Application\Configuration;

/**
 * pgvector section of the configuration report (ADR-0002, Proposed).
 *
 * `available` is true exactly when the extension reports a version.
 */
final readonly class PgvectorSection implements \JsonSerializable
{
    public function __construct(
        public readonly bool $available,
        public readonly ?string $version,
    ) {
    }

    /**
     * @return array{available: bool, version: string|null}
     */
    public function jsonSerialize(): array
    {
        return [
            'available' => $this->available,
            'version' => $this->version,
        ];
    }
}
