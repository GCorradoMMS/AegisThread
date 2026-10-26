<?php

declare(strict_types=1);

namespace AegisThread\Infrastructure\Persistence\Doctrine;

use AegisThread\Domain\User\User;
use Symfony\Component\Uid\Uuid;

/**
 * Assigns identity outside the domain and constructs User aggregates.
 *
 * The Symfony import lives here, in Infrastructure, on purpose: the domain
 * stays framework-free (purity boundary).
 */
final class UserFactory
{
    public function create(string $email, ?\DateTimeImmutable $createdAt = null): User
    {
        return new User(
            id: Uuid::v7()->toRfc4122(),
            email: $email,
            createdAt: $createdAt ?? new \DateTimeImmutable(),
        );
    }
}
