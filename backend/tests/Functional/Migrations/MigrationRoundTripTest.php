<?php

declare(strict_types=1);

namespace AegisThread\Tests\Functional\Migrations;

use AegisThread\Tests\Support\ConsoleResult;
use AegisThread\Tests\Support\DatabaseMigrations;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Full migration lifecycle against the live PostgreSQL database
 * (group requires-db; excluded from the default suite).
 *
 * Verifies the M1 schema contract end to end: pgvector enabled, ZERO vector
 * columns anywhere, users table with exactly the three approved columns —
 * plus reversibility, re-apply idempotence, and the no-op second run.
 *
 * Fixture state: the suffixed test database (<db>_test, see
 * DatabaseMigrations::assertDatabaseExists) must exist; this test manages
 * all schema itself (down to 0 → up → no-op → down → up).
 */
#[Group('requires-db')]
final class MigrationRoundTripTest extends KernelTestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        self::bootKernel();

        // ConnectionRegistry::getConnection() is typed object (storage
        // agnostic); the assert narrows to the DBAL connection.
        $connection = self::getContainer()->get('doctrine')->getConnection();
        \assert($connection instanceof Connection);
        $this->connection = $connection;

        DatabaseMigrations::assertDatabaseExists($this->connection);
    }

    public function testFullMigrationLifecycle(): void
    {
        // Start from a known-clean state: everything down.
        $reset = DatabaseMigrations::migrate('first');
        $this->assertMigrateSucceeded($reset);

        // Up: both migrations apply.
        $up = DatabaseMigrations::migrate();
        $this->assertMigrateSucceeded($up);
        self::assertStringContainsString('Version20261025000001', $up->output);
        self::assertStringContainsString('Version20261025000002', $up->output);

        $this->assertPgvectorEnabled();
        $this->assertNoVectorColumnsAnywhere();
        $this->assertUsersColumns(['created_at', 'email', 'id']);
        $this->assertAppliedVersions([
            'DoctrineMigrations\Version20261025000001',
            'DoctrineMigrations\Version20261025000002',
        ]);

        // A second run right after up is a no-op.
        $noop = DatabaseMigrations::migrate();
        $this->assertMigrateSucceeded($noop);
        self::assertStringContainsString('Already at the latest version', $noop->output);

        // Down to version 0: extension and table are gone.
        $down = DatabaseMigrations::migrate('first');
        $this->assertMigrateSucceeded($down);

        self::assertFalse(
            $this->connection->fetchOne(
                "SELECT extversion FROM pg_extension WHERE extname = 'vector'",
            ),
            'The vector extension must be gone after down().',
        );
        self::assertNull(
            $this->connection->fetchOne('SELECT to_regclass(\'users\')'),
            'The users table must be gone after down().',
        );
        $this->assertAppliedVersions([]);

        // Up again: idempotent re-apply from a fully migrated-down state.
        $reup = DatabaseMigrations::migrate();
        $this->assertMigrateSucceeded($reup);

        $this->assertPgvectorEnabled();
        $this->assertUsersColumns(['created_at', 'email', 'id']);
    }

    private function assertMigrateSucceeded(ConsoleResult $result): void
    {
        self::assertSame(0, $result->exitCode, "Migration command failed:\n{$result->output}");
    }

    /**
     * @param list<string> $expected sorted applied version names
     */
    private function assertAppliedVersions(array $expected): void
    {
        $versions = $this->connection->fetchFirstColumn(
            'SELECT version FROM doctrine_migration_versions ORDER BY version',
        );

        self::assertSame($expected, $versions, 'The migration metadata table must reflect the applied versions exactly.');
    }

    private function assertPgvectorEnabled(): void
    {
        $version = $this->connection->fetchOne(
            'SELECT extversion FROM pg_extension WHERE extname = \'vector\'',
        );

        self::assertIsString($version, 'The vector extension must be listed in pg_extension.');
        self::assertNotSame('', $version);
    }

    private function assertNoVectorColumnsAnywhere(): void
    {
        $vectorColumns = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.columns WHERE udt_name = \'vector\'',
        );

        self::assertIsNumeric($vectorColumns);
        self::assertSame(0, (int) $vectorColumns, 'No column of type vector may exist yet (R-4).');
    }

    /**
     * @param list<string> $expected sorted column names
     */
    private function assertUsersColumns(array $expected): void
    {
        $columns = $this->connection->fetchFirstColumn(
            'SELECT column_name FROM information_schema.columns'
            ." WHERE table_name = 'users' ORDER BY column_name",
        );

        self::assertSame($expected, $columns, 'users must have exactly the approved columns.');
    }
}
