<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRoleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRoleRepository::class)]
#[ORM\Table(name: 'user_role')]
#[ORM\UniqueConstraint(name: 'uniq_user_role', columns: ['user_id', 'role_id'])]
class UserRole
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Role::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private Role $role;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function setRole(Role $role): self
    {
        $this->role = $role;

        return $this;
    }
}
