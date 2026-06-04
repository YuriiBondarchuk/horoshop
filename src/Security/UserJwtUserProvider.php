<?php

declare(strict_types=1);

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use App\Entity\User;

class UserJwtUserProvider implements UserProviderInterface
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->find((int)$identifier);
        if (!$user) {
            throw new UserNotFoundException(sprintf('User with id %s not found', $identifier));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        // stateless JWT: no refresh
        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class;
    }
}
