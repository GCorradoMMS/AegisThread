<?php

declare(strict_types=1);

namespace AegisThread\Tests\Support;

use AegisThread\Kernel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DatabaseDoesNotExist;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Drives Doctrine migrations for requires-db functional tests, through the
 * real console command (same code path as `php bin/console
 * doctrine:migrations:migrate`) inside a freshly booted test kernel.
 */
final class DatabaseMigrations
{
    /**
     * Runs `doctrine:migrations:migrate --no-interaction --all-or-nothing`,
     * optionally to a specific version alias (e.g. 'first' for version 0).
     *
     * Each call boots its own kernel: doctrine:migrations freezes every
     * AbstractMigration instance after a single execution, so a second
     * in-process run needs a fresh DependencyFactory (fresh container).
     */
    public static function migrate(?string $version = null): ConsoleResult
    {
        $kernel = new Kernel('test', true);
        $kernel->boot();

        try {
            $application = new Application($kernel);
            $application->setAutoExit(false);

            $arguments = ['--no-interaction' => true, '--all-or-nothing' => true];

            if (null !== $version) {
                $arguments['version'] = $version;
            }

            $tester = new CommandTester($application->find('doctrine:migrations:migrate'));
            // capture_stderr_separately: doctrine:migrations logs its
            // per-version progress (notices) to STDERR; stdout alone only
            // carries the final [OK]/prompt lines. VERBOSE exposes the
            // per-version execution lines ("✔ Version...").
            $exitCode = $tester->execute($arguments, [
                'capture_stderr_separately' => true,
                'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
            ]);

            return new ConsoleResult(
                $tester->getDisplay().$tester->getErrorOutput(),
                $exitCode,
            );
        } finally {
            $kernel->shutdown();
        }
    }

    /**
     * Fails with a precise message when the configured database does not
     * exist (the migrations themselves must never run against the wrong
     * database).
     *
     * Fixture state: in the test environment the connection's database name
     * is suffixed (`<db>_test`); create it once, e.g.:
     *   docker exec <pg-container> psql -U aegisthread -d aegisthread \
     *     -c 'CREATE DATABASE aegisthread_test'
     */
    public static function assertDatabaseExists(Connection $connection): void
    {
        try {
            $connection->executeQuery('SELECT 1');
        } catch (DatabaseDoesNotExist) {
            $dbname = $connection->getParams()['dbname'] ?? null;
            \assert(\is_string($dbname));

            Assert::fail(
                'The test database does not exist. Create it first (see'
                .' DatabaseMigrations::assertDatabaseExists docblock);'
                ." expected database name: {$dbname}",
            );
        }
    }
}
