<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Contract\UserServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Exception\ApiException;

class UserService implements UserServiceInterface
{
    public function __construct(
        private UserRepository $repo,
        private EntityManagerInterface $em,
        private ValidatorInterface $validator
    ) {}

    public function create(User $user): User
    {
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $e) { $messages[] = $e->getPropertyPath().': '.$e->getMessage(); }
            throw new ApiException('Validation failed', 400, $messages);
        }

        $this->em->persist($user);
        try {
            $this->em->flush();
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            throw new ApiException('Duplicate login+pass', 409);
        }

        return $user;
    }

    public function update(User $user): User
    {
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $messages = [];
            foreach ($errors as $e) { $messages[] = $e->getPropertyPath().': '.$e->getMessage(); }
            throw new ApiException('Validation failed', 400, $messages);
        }

        try {
            $this->em->flush();
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            throw new ApiException('Duplicate login+pass', 409);
        }

        return $user;
    }

    public function delete(User $user): void
    {
        $this->em->remove($user);
        $this->em->flush();
    }

    public function find(int $id): ?User
    {
        return $this->repo->find($id);
    }

    public function findAll(): array
    {
        return $this->repo->findAll();
    }
}
