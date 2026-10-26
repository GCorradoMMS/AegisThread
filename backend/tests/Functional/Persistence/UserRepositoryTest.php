<?php

declare(strict_types=1);

namespace AegisThread\Tests\Functional\Persistence;

use AegisThread\Domain\User\UserRepository;
use AegisThread\Infrastructure\Persistence\Doctrine\DoctrineUserRepository;
use AegisThread\Infrastructure\Persistence\Doctrine\UserFactory;
use AegisThread\Tests\Support\DatabaseMigrations;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * DoctrineUserRepository against the live PostgreSQL database
 * (group requires-db; excluded from the default suite).
 *
 * Fixture state (documented on purpose): the suffixed test database
 * (<db>_test, see DatabaseMigrations::assertDatabaseExists) must exist;
 * migrations are applied in setUp (a no-op when already current) and the
 * users table is emptied so each test starts deterministic despite the
 * unique email constraint.
 */
#[Group('requires-db')]
final class UserRepositoryTest extends KernelTestCase
{
    private Connection $connection;

    private UserRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        // ConnectionRegistry::getConnection() is typed object (storage
        // agnostic); the assert narrows to the DBAL connection.
        $connection = self::getContainer()->get('doctrine')->getConnection();
        \assert($connection instanceof Connection);
        $this->connection = $connection;

        DatabaseMigrations::assertDatabaseExists($this->connection);
        DatabaseMigrations::migrate();

        $this->connection->executeStatement('DELETE FROM users');

        // The container resolves the UserRepository port lazily through the
        // autowired single-implementation alias, which stays private (and is
        // pruned until a production consumer lands). The adapter is therefore
        // assembled here around the container's entity manager — the real
        // ORM wiring, real mapping, real database.
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->repository = new DoctrineUserRepository($entityManager);
    }

    public function testSavedUserIsFoundByEmailWithEqualIdentity(): void
    {
        // Whole-second timestamp: the datetimetz mapping persists seconds.
        $createdAt = new \DateTimeImmutable('2026-01-15T10:30:00+00:00');

        $user = (new UserFactory())->create('ada@example.com', $createdAt);
        $this->repository->save($user);

        $found = $this->repository->findByEmail('ada@example.com');

        self::assertNotNull($found);
        self::assertSame($user->id(), $found->id());
        self::assertSame($user->email(), $found->email());
        self::assertSame(
            $createdAt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            $found->createdAt()->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
        );
    }

    public function testUnknownEmailYieldsNull(): void
    {
        self::assertNull($this->repository->findByEmail('nobody@aegisthread.invalid'));
    }
}
