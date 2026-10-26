<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Enables the pgvector extension (ADR-0002: PostgreSQL is the source of
 * truth and vector storage is a platform capability, not an ORM concern).
 *
 * The PLAIN `CREATE EXTENSION vector` is deliberate: on a database without
 * the extension available, the migration must fail loudly (SQLSTATE 42704)
 * instead of silently continuing without vector support. Idempotence comes
 * from Doctrine's migration version tracking, not from IF NOT EXISTS.
 *
 * down() reverses safely today because no vector columns exist yet — once
 * the first vector column lands, dropping the extension requires removing
 * dependent columns first.
 */
final class Version20261025000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enables the pgvector extension.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION vector');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP EXTENSION IF EXISTS vector');
    }
}
