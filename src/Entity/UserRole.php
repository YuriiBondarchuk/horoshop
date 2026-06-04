<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'uniq_login_pass', columns: ['login', 'pass'])]
class UserRole implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['post','put'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Length(max: 8)]
    #[Groups(['post','get'])]
    private string $login;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Length(max: 8)]
    #[Groups(['post','get'])]
    private string $phone;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Length(max: 8)]
    #[Groups(['post','get'])]
    private string $pass;

    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_role')]
    private Collection $roles;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function setLogin(string $login): self
    {
        $this->login = $login;
        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getPass(): string
    {
        return $this->pass;
    }

    public function setPass(string $pass): self
    {
        $this->pass = $pass;
        return $this;
    }

    public function getRolesCollection(): Collection
    {
        return $this->roles;
    }

    public function addRole(Role $role): self
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
        }
        return $this;
    }

    public function removeRole(Role $role): self
    {
        $this->roles->removeElement($role);
        return $this;
    }

    public function getRoleNames(): array
    {
        return array_map(fn(Role $r) => $r->getName(), $this->roles->toArray());
    }


    // Unique identifier for the user (used by security)
    public function getUserIdentifier(): string
    {
        return (string) $this->login;
    }

    // Return roles array for Symfony security. Ensure at least ROLE_USER if none.
    public function getRoles(): array
    {
        $names = $this->getRoleNames();
        if (empty($names)) {
            $names[] = 'ROLE_USER';
        }
        return array_values(array_unique($names));
    }

    // Return hashed password for authentication
    public function getPassword(): string
    {
        return $this->pass;
    }

    // If you store temporary sensitive data, clear it here
    public function eraseCredentials(): void
    {
        // e.g. $this->plainPassword = null;
    }

    public function getSub(): ?int
    {
        return $this->id;
    }
}
