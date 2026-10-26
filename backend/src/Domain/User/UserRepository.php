<?php

declare(strict_types=1);

namespace AegisThread\Domain\User;

/**
 * Persistence port for User aggregates.
 *
 * Implemented by the infrastructure layer (Doctrine ORM adapter); the
 * domain depends on this interface only.
 */
interface UserRepository
{
    public function save(User $user): void;

    public function findByEmail(string $email): ?User;
}
