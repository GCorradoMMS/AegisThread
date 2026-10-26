<?php

declare(strict_types=1);

namespace AegisThread\Tests\Unit\Application\Configuration;

use AegisThread\Application\Configuration\GetConfigurationReport;
use AegisThread\Application\Configuration\MigrationsSection;
use AegisThread\Application\Configuration\RequiredVariablesReader;
use AegisThread\Application\Configuration\RequiredVariableStatus;
use AegisThread\Tests\Support\Fakes\FakeDatabaseHealthProbe;
use AegisThread\Tests\Support\Fakes\FakeMigrationStateReader;
use AegisThread\Tests\Support\Fakes\FakePgvectorAvailabilityProbe;
use AegisThread\Tests\Support\Fakes\FakeRequiredVariablesReader;
use AegisThread\Tests\Support\Fakes\FakeRuntimeInfoProvider;
use AegisThread\Tests\Support\Fakes\FakeServerVersionReader;
use AegisThread\Tests\Support\JsonBody;
use PHPUnit\Framework\TestCase;

use function getenv;

/**
 * GetConfigurationReport composition: all sections populated from the
 * ports, migration-reader failures mapped to the 'unknown' state, missing
 * pgvector mapped to available=false — and, critically, the serialized
 * report never leaks the values of required environment variables.
 */
final class GetConfigurationReportTest extends TestCase
{
    public function testAllSectionsArePopulatedFromThePorts(): void
    {
        $report = $this->useCase()();

        self::assertSame('test', $report->environment->name);
        self::assertEquals(
            [
                'DATABASE_URL' => new RequiredVariableStatus(set: true),
                'APP_SECRET' => new RequiredVariableStatus(set: false),
            ],
            $report->environment->requiredVariables,
        );
        self::assertSame('8.5.12', $report->runtime->php);
        self::assertSame('8.1.9', $report->runtime->framework);
        self::assertTrue($report->database->reachable);
        self::assertSame('16.9', $report->database->serverVersion);
        self::assertSame(MigrationsSection::STATE_UP_TO_DATE, $report->migrations->state);
        self::assertSame(7, $report->migrations->executed);
        self::assertSame('20260201000000', $report->migrations->latest);
        self::assertTrue($report->extensions->pgvector->available);
        self::assertSame('0.8.0', $report->extensions->pgvector->version);
    }

    public function testPendingMigrationsAreReportedAsPending(): void
    {
        $report = $this->useCase(migrationStateReader: new FakeMigrationStateReader(upToDate: false, executed: 2))();

        self::assertSame(MigrationsSection::STATE_PENDING, $report->migrations->state);
        self::assertSame(2, $report->migrations->executed);
    }

    public function testThrowingMigrationReaderYieldsUnknownStateWithDegradedDefaults(): void
    {
        $report = $this->useCase(
            migrationStateReader: new FakeMigrationStateReader(
                throwable: new \RuntimeException('database unreachable'),
            ),
            databaseHealthProbe: new FakeDatabaseHealthProbe(available: false),
        )();

        self::assertSame(MigrationsSection::STATE_UNKNOWN, $report->migrations->state);
        self::assertSame(0, $report->migrations->executed);
        self::assertNull($report->migrations->latest);
    }

    public function testNullPgvectorVersionYieldsUnavailable(): void
    {
        $report = $this->useCase(pgvectorAvailabilityProbe: new FakePgvectorAvailabilityProbe(version: null))();

        self::assertFalse($report->extensions->pgvector->available);
        self::assertNull($report->extensions->pgvector->version);
    }

    public function testSerializedReportNeverContainsRequiredEnvironmentVariableValues(): void
    {
        // Distinctive dummy values, actually set in the environment and read
        // back via getenv — the leak source under test.
        $secretMarker = 'TOP-SECRET-VALUE-do-not-emit-9f2c';
        $dsnMarker = 'pgsql://leaked-user:leaked-pass@leaked-host/leaked-db';
        \putenv("APP_SECRET=$secretMarker");
        \putenv("DATABASE_URL=$dsnMarker");

        try {
            // Rogue reader double: returns the ENV VALUES instead of
            // presence booleans — the exact shape a regression that turns
            // the visibility surface into a disclosure surface would
            // produce. The use case must contain it (degraded data), and
            // the serialized report must carry neither value.
            //
            // Containment currently rides on the RequiredVariableStatus
            // (bool $set) type rejecting string values. Widening that DTO
            // to carry anything value-shaped requires an explicit value
            // filter — passing this test today does not make widening safe.
            $rogueReader = new class implements RequiredVariablesReader {
                public function requiredVariables(): array
                {
                    $leaked = [
                        'DATABASE_URL' => \getenv('DATABASE_URL'),
                        'APP_SECRET' => \getenv('APP_SECRET'),
                    ];

                    // Deliberate type lie: simulates the leaked-value regression this secret-leak test guards against.
                    return $leaked; // @phpstan-ignore return.type (rogue double must return env VALUES to prove the use case contains them)
                }
            };

            $report = $this->useCase(requiredVariablesReader: $rogueReader)();

            $json = \json_encode($report, \JSON_THROW_ON_ERROR);

            self::assertStringNotContainsString($secretMarker, $json, 'The report leaked APP_SECRET.');
            self::assertStringNotContainsString($dsnMarker, $json, 'The report leaked DATABASE_URL.');

            // Degraded, not broken: the rest of the report still answers.
            self::assertSame('up-to-date', $report->migrations->state);
            self::assertTrue($report->database->reachable);
        } finally {
            \putenv('APP_SECRET');
            \putenv('DATABASE_URL');
        }
    }

    public function testSerializedReportCarriesExactlyTheAllowlistKeys(): void
    {
        $decoded = JsonBody::decode(\json_encode($this->useCase()(), \JSON_THROW_ON_ERROR));

        self::assertSame(
            ['environment', 'runtime', 'database', 'migrations', 'extensions', 'generatedAt'],
            \array_keys($decoded),
        );
        self::assertSame(['name', 'requiredVariables'], \array_keys(JsonBody::section($decoded, 'environment')));
        self::assertSame(['php', 'framework'], \array_keys(JsonBody::section($decoded, 'runtime')));
        self::assertSame(['reachable', 'serverVersion'], \array_keys(JsonBody::section($decoded, 'database')));
        self::assertSame(['state', 'executed', 'latest'], \array_keys(JsonBody::section($decoded, 'migrations')));
        self::assertSame(['pgvector'], \array_keys(JsonBody::section($decoded, 'extensions')));
        self::assertSame(
            ['available', 'version'],
            \array_keys(JsonBody::section(JsonBody::section($decoded, 'extensions'), 'pgvector')),
        );
        self::assertSame(
            ['set'],
            \array_keys(JsonBody::section(
                JsonBody::section(JsonBody::section($decoded, 'environment'), 'requiredVariables'),
                'DATABASE_URL',
            )),
        );
    }

    private function useCase(
        ?FakeRuntimeInfoProvider $runtimeInfoProvider = null,
        ?FakeMigrationStateReader $migrationStateReader = null,
        ?FakePgvectorAvailabilityProbe $pgvectorAvailabilityProbe = null,
        ?FakeDatabaseHealthProbe $databaseHealthProbe = null,
        ?RequiredVariablesReader $requiredVariablesReader = null,
    ): GetConfigurationReport {
        return new GetConfigurationReport(
            databaseHealthProbe: $databaseHealthProbe ?? new FakeDatabaseHealthProbe(available: true),
            runtimeInfoProvider: $runtimeInfoProvider
                ?? new FakeRuntimeInfoProvider(environment: 'test', phpVersion: '8.5.12', frameworkVersion: '8.1.9'),
            migrationStateReader: $migrationStateReader
                ?? new FakeMigrationStateReader(upToDate: true, executed: 7, latest: '20260201000000'),
            pgvectorAvailabilityProbe: $pgvectorAvailabilityProbe ?? new FakePgvectorAvailabilityProbe(version: '0.8.0'),
            serverVersionReader: new FakeServerVersionReader('16.9'),
            requiredVariablesReader: $requiredVariablesReader
                ?? new FakeRequiredVariablesReader([
                    'DATABASE_URL' => true,
                    'APP_SECRET' => false,
                ]),
        );
    }
}
