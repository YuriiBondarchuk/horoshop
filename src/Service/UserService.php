<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateUserDto;
use App\Dto\UpdateUserDto;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Contract\UserServiceInterface;
use Doctrine\ORM\EntityManagerInterface;

readonly class UserService implements UserServiceInterface
{
    public function __construct(
        private UserRepository $repository,
        private EntityManagerInterface $manager,
    ) {}

    public function delete(User $user): void
    {
        $this->manager->remove($user);
        $this->manager->flush();
    }

    public function find(int $id): ?User
    {
        return $this->repository->find($id);
    }

    public function findByLogin(string $login): ?User
    {
        return $this->repository->findByLogin($login);
    }

    public function createFromDto(CreateUserDto $dto): User
    {
        $user = new User();
        $user->setLogin($dto->login);
        $user->setPhone($dto->phone);
        $user->setPass(password_hash($dto->pass, PASSWORD_ARGON2ID));

        $this->manager->persist($user);
        $this->manager->flush();

        return $user;
    }

    public function updateFromDto(UpdateUserDto $dto): ?User
    {
        $user = $this->repository->find($dto->id);
        if (!$user) {
            return null;
        }

        $user->setLogin($dto->login);
        $user->setPhone($dto->phone);
        $user->setPass(password_hash($dto->pass, PASSWORD_ARGON2ID));

        $this->manager->flush();

        return $user;
    }
}
