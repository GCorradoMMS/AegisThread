<?php

declare(strict_types=1);

namespace AegisThread\Domain\User;

/**
 * A registered user.
 *
 * Deliberately NOT readonly: Doctrine hydrates this aggregate through
 * reflection, which cannot initialize readonly promoted properties.
 *
 * Identity is assigned outside the domain — the constructor keeps it
 * required so an id-less User cannot exist (see the UserFactory in the
 * Infrastructure\Persistence\Doctrine namespace for UUIDv7 generation).
 * No behavior beyond identity lives here.
 */
final class User
{
    public function __construct(
        private string $id,
        private string $email,
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
