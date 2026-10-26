<?php

declare(strict_types=1);

namespace AegisThread\Application\Configuration;

/**
 * Environment section of the configuration report: environment name plus
 * set/unset visibility for each required variable (never their values).
 *
 * @phpstan-type RequiredVariables array<string, RequiredVariableStatus>
 */
final readonly class EnvironmentSection implements \JsonSerializable
{
    /**
     * @param array<string, RequiredVariableStatus> $requiredVariables
     */
    public function __construct(
        public readonly string $name,
        public readonly array $requiredVariables,
    ) {
    }

    /**
     * @return array{name: string, requiredVariables: array<string, RequiredVariableStatus>}
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'requiredVariables' => $this->requiredVariables,
        ];
    }
}
