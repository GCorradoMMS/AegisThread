<?php

declare(strict_types=1);

namespace AegisThread\Application\Configuration;

/**
 * Extensions section of the configuration report.
 */
final readonly class ExtensionsSection implements \JsonSerializable
{
    public function __construct(
        public readonly PgvectorSection $pgvector,
    ) {
    }

    /**
     * @return array{pgvector: PgvectorSection}
     */
    public function jsonSerialize(): array
    {
        return [
            'pgvector' => $this->pgvector,
        ];
    }
}
