<?php

declare(strict_types=1);

namespace AegisThread\Application\Configuration;

use AegisThread\Application\Health\DatabaseHealthProbe;

/**
 * Use case: compose the configuration report from infrastructure ports.
 *
 * Every port call is individually contained: a report endpoint answers with
 * degraded DATA, never an error page. In particular, a throwing migration
 * reader yields the 'unknown' state with executed=0 and latest=null, and an
 * unreachable database yields reachable=false rather than an exception.
 */
final class GetConfigurationReport
{
    public function __construct(
        private readonly DatabaseHealthProbe $databaseHealthProbe,
        private readonly RuntimeInfoProvider $runtimeInfoProvider,
        private readonly MigrationStateReader $migrationStateReader,
        private readonly PgvectorAvailabilityProbe $pgvectorAvailabilityProbe,
        private readonly ServerVersionReader $serverVersionReader,
        private readonly RequiredVariablesReader $requiredVariablesReader,
    ) {
    }

    public function __invoke(): ConfigurationReport
    {
        $requiredVariables = [];
        $environmentName = 'unknown';
        $phpVersion = 'unknown';
        $frameworkVersion = 'unknown';
        $databaseReachable = false;
        $serverVersion = null;
        $migrationState = MigrationsSection::STATE_UNKNOWN;
        $executed = 0;
        $latest = null;
        $pgvectorVersion = null;

        try {
            foreach ($this->requiredVariablesReader->requiredVariables() as $name => $set) {
                $requiredVariables[$name] = new RequiredVariableStatus(set: $set);
            }
        } catch (\Throwable) {
            $requiredVariables = [];
        }

        try {
            $environmentName = $this->runtimeInfoProvider->environment();
            $phpVersion = $this->runtimeInfoProvider->phpVersion();
            $frameworkVersion = $this->runtimeInfoProvider->frameworkVersion();
        } catch (\Throwable) {
        }

        try {
            $databaseReachable = $this->databaseHealthProbe->isAvailable();
        } catch (\Throwable) {
            $databaseReachable = false;
        }

        try {
            $serverVersion = $this->serverVersionReader->serverVersion();
        } catch (\Throwable) {
            $serverVersion = null;
        }

        try {
            $migrationState = $this->migrationStateReader->isUpToDate()
                ? MigrationsSection::STATE_UP_TO_DATE
                : MigrationsSection::STATE_PENDING;
            $executed = $this->migrationStateReader->executedCount();
            $latest = $this->migrationStateReader->latestExecuted();
        } catch (\Throwable) {
            $migrationState = MigrationsSection::STATE_UNKNOWN;
            $executed = 0;
            $latest = null;
        }

        try {
            $pgvectorVersion = $this->pgvectorAvailabilityProbe->version();
        } catch (\Throwable) {
            $pgvectorVersion = null;
        }

        return new ConfigurationReport(
            environment: new EnvironmentSection(
                name: $environmentName,
                requiredVariables: $requiredVariables,
            ),
            runtime: new RuntimeSection(
                php: $phpVersion,
                framework: $frameworkVersion,
            ),
            database: new DatabaseSection(
                reachable: $databaseReachable,
                serverVersion: $serverVersion,
            ),
            migrations: new MigrationsSection(
                state: $migrationState,
                executed: $executed,
                latest: $latest,
            ),
            extensions: new ExtensionsSection(
                pgvector: new PgvectorSection(
                    available: null !== $pgvectorVersion,
                    version: $pgvectorVersion,
                ),
            ),
            generatedAt: new \DateTimeImmutable(),
        );
    }
}
