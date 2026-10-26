<?php

declare(strict_types=1);

namespace AegisThread\Application\Configuration;

/**
 * Immutable outcome of the GetConfigurationReport use case.
 *
 * The shape is an ALLOWLIST: the JSON representation (JsonSerializable,
 * rendered verbatim by GET /api/configuration) contains exactly the sections
 * constructed here — environment, runtime, database, migrations, extensions,
 * generatedAt. There is no field through which a secret value could be
 * emitted: the environment section carries a set/unset boolean per required
 * variable, never its value.
 */
final readonly class ConfigurationReport implements \JsonSerializable
{
    public function __construct(
        public readonly EnvironmentSection $environment,
        public readonly RuntimeSection $runtime,
        public readonly DatabaseSection $database,
        public readonly MigrationsSection $migrations,
        public readonly ExtensionsSection $extensions,
        public readonly \DateTimeImmutable $generatedAt,
    ) {
    }

    /**
     * @return array{
     *     environment: EnvironmentSection,
     *     runtime: RuntimeSection,
     *     database: DatabaseSection,
     *     migrations: MigrationsSection,
     *     extensions: ExtensionsSection,
     *     generatedAt: string
     * }
     */
    public function jsonSerialize(): array
    {
        return [
            'environment' => $this->environment,
            'runtime' => $this->runtime,
            'database' => $this->database,
            'migrations' => $this->migrations,
            'extensions' => $this->extensions,
            'generatedAt' => $this->generatedAt->format(\DATE_RFC3339),
        ];
    }
}
