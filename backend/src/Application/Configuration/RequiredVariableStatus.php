<?php

declare(strict_types=1);

namespace AegisThread\Application\Configuration;

/**
 * Presence status of one required environment variable.
 *
 * Deliberately carries a boolean only — never the variable's value.
 */
final readonly class RequiredVariableStatus implements \JsonSerializable
{
    public function __construct(
        public readonly bool $set,
    ) {
    }

    /**
     * @return array{set: bool}
     */
    public function jsonSerialize(): array
    {
        return [
            'set' => $this->set,
        ];
    }
}
