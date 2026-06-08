<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Dto\CreateUserDto;
use App\Dto\LoginDto;
use App\Dto\UpdateUserDto;
use App\Security\UserVoter;
use App\Service\Contract\UserServiceInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/v1/api/users')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly UserServiceInterface $userService,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly ValidatorInterface $validator
    ) {
    }

    #[Route('/{id}', name: 'users_get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $user = $this->userService->find((int)$id);
        if (!$user) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(UserVoter::VIEW, $user);

        return $this->json($user, Response::HTTP_OK, [], ['groups' => 'get']);
    }

    #[Route('', name: 'users_post', methods: ['POST'])]
    public function post(#[MapRequestPayload] CreateUserDto $dto): JsonResponse
    {
        $this->denyAccessUnlessGranted(UserVoter::CREATE, null);

        $user = $this->userService->createFromDto($dto);

        return $this->json($user, Response::HTTP_CREATED, [], ['groups' => 'post']);
    }

    #[Route('/{id}', name: 'users_put', methods: ['PUT'])]
    public function put(#[MapRequestPayload()] UpdateUserDto $dto): JsonResponse
    {
        $user = $this->userService->updateFromDto($dto);
        if (!$user) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(UserVoter::EDIT, $user);

        return $this->json($user, Response::HTTP_OK, [], ['groups' => 'put']);
    }

    #[Route('/{id}', name: 'users_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->userService->find($id);
        if (!$user) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(UserVoter::DELETE, $user);

        $this->userService->delete($user);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/login', name: 'users_login', methods: ['POST'])]
    public function login(#[MapRequestPayload] LoginDto $dto): JsonResponse
    {
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userService->findByLogin($dto->login);
        if (!$user) {
            return $this->json(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        $payload = [
            'sub' => (string)$user->getId(),
            'roles' => $user->getRoles(),
        ];

        $token = $this->jwtManager->createFromPayload($user, $payload);

        return $this->json(['token' => $token], Response::HTTP_OK);
    }
}
