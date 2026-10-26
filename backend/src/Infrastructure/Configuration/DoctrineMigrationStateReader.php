<?php

declare(strict_types=1);

namespace AegisThread\Infrastructure\Configuration;

use AegisThread\Application\Configuration\MigrationStateReader;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Migration state through the doctrine-migrations API
 * (DependencyFactory's metadata storage and status calculator) — never
 * hand-rolled SQL diffs.
 *
 * A migrations metadata table that is missing entirely is the natural
 * "nothing executed yet" case: the storage API answers with an empty
 * executed list, so the state reads executed=0 and pending. Failures of the
 * database connection itself are NOT contained here — they propagate to the
 * use case, which reports the 'unknown' state (degraded data).
 */
final class DoctrineMigrationStateReader implements MigrationStateReader
{
    public function __construct(
        #[Autowire(service: 'doctrine.migrations.dependency_factory')]
        private readonly DependencyFactory $dependencyFactory,
    ) {
    }

    public function executedCount(): int
    {
        return $this->executedMigrations()->count();
    }

    public function latestExecuted(): ?string
    {
        $executed = $this->executedMigrations();

        if (0 === $executed->count()) {
            return null;
        }

        return (string) $executed->getLast()->getVersion();
    }

    public function isUpToDate(): bool
    {
        $newMigrations = $this->dependencyFactory
            ->getMigrationStatusCalculator()
            ->getNewMigrations();

        return 0 === \count($newMigrations);
    }

    private function executedMigrations(): ExecutedMigrationsList
    {
        return $this->dependencyFactory->getMetadataStorage()->getExecutedMigrations();
    }
}
