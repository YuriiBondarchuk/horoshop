<?php

declare(strict_types=1);

namespace App\Service\Contract;

use App\Dto\CreateUserDto;
use App\Dto\UpdateUserDto;
use App\Entity\User;

interface UserServiceInterface
{
    public function delete(User $user): void;

    public function find(int $id): ?User;

    public function findByLogin(string $login): ?User;

    public function createFromDto(CreateUserDto $dto): User;

    public function updateFromDto(UpdateUserDto $dto): ?User;
}
