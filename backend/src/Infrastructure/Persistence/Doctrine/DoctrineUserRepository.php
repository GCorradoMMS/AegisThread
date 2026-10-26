<?php

declare(strict_types=1);

namespace AegisThread\Infrastructure\Persistence\Doctrine;

use AegisThread\Domain\User\User;
use AegisThread\Domain\User\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Doctrine ORM adapter for the UserRepository port.
 */
final class DoctrineUserRepository implements UserRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(User $user): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function findByEmail(string $email): ?User
    {
        /** @var User|null $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        return $user;
    }
}
