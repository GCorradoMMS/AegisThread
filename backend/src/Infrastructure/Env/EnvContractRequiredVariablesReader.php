<?php

declare(strict_types=1);

namespace AegisThread\Infrastructure\Env;

use AegisThread\Application\Configuration\RequiredVariablesReader;

/**
 * Presence flags for EnvContract::REQUIRED, resolved the same way the
 * Symfony container resolves %env()% (real environment first: $_ENV,
 * $_SERVER, then the process environment). Values are checked for presence
 * only — they are never returned, logged, or stored.
 */
final class EnvContractRequiredVariablesReader implements RequiredVariablesReader
{
    public function requiredVariables(): array
    {
        $presence = [];

        foreach (EnvContract::REQUIRED as $name => $purpose) {
            $presence[$name] = $this->isSet($name);
        }

        return $presence;
    }

    private function isSet(string $name): bool
    {
        return \is_string($this->realSourceValue($name));
    }

    /**
     * First present value among Symfony's %env()% resolution order
     * ($_ENV, $_SERVER, process environment). Non-string superglobal
     * entries are treated as absent: the contract is about string
     * environment configuration. Values are never exposed.
     */
    private function realSourceValue(string $name): string|false
    {
        $envValue = $_ENV[$name] ?? null;

        if (\is_string($envValue)) {
            return $envValue;
        }

        $serverValue = $_SERVER[$name] ?? null;

        if (\is_string($serverValue)) {
            return $serverValue;
        }

        return getenv($name);
    }
}
