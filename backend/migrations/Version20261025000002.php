<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates the users table: identity, unique email, creation timestamp.
 *
 * Deliberately minimal (OQ-16 open, R-4 forbids presumptions): no tenant
 * column, no credential/session columns. `created_at` is app-assigned —
 * no database default. Reversible by a plain drop.
 */
final class Version20261025000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the users table (id, unique email, created_at).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE users (
                id UUID NOT NULL,
                email VARCHAR(255) NOT NULL,
                created_at TIMESTAMPTZ NOT NULL,
                CONSTRAINT users_email_unique UNIQUE (email)
            )
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE users');
    }
}
