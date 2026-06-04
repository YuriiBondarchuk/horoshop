<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\Contract\UserServiceInterface;
use App\Exception\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/v1/api/users')]
class UserController extends AbstractController
{
    public function __construct(
        private UserServiceInterface $userService,
        private SerializerInterface $serializer
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $users = $this->userService->findAll();
        return new JsonResponse($this->serializer->normalize($users), Response::HTTP_OK);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->userService->find($id);
        if (!$user) return new JsonResponse(['error'=>'Not found'], Response::HTTP_NOT_FOUND);

        if ($this->isGranted('ROLE_USER') && !$this->isGranted('ROLE_ROOT')) {
            $current = $this->getUser();
            if ($current->getId() !== $user->getId()) {
                return new JsonResponse(['error'=>'Forbidden'], Response::HTTP_FORBIDDEN);
            }
        }

        return new JsonResponse($this->serializer->normalize($user), Response::HTTP_OK);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $data = json_decode($request->getContent(), true);
        $user = new User();
        $user->setLogin($data['login'] ?? '');
        $user->setPhone($data['phone'] ?? '');
        $user->setPass($data['pass'] ?? '');
        $user->setRoles($data['roles'] ?? ['ROLE_USER']);

        try {
            $this->userService->create($user);
        } catch (ApiException $e) {
            return new JsonResponse(['error'=>$e->getMessage(), 'details'=>$e->getDetails()], $e->getCode() ?: 400);
        }

        return new JsonResponse($this->serializer->normalize($user), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->userService->find($id);
        if (!$user) return new JsonResponse(['error'=>'Not found'], Response::HTTP_NOT_FOUND);

        if ($this->isGranted('ROLE_USER') && !$this->isGranted('ROLE_ROOT')) {
            $current = $this->getUser();
            if ($current->getId() !== $user->getId()) {
                return new JsonResponse(['error'=>'Forbidden'], Response::HTTP_FORBIDDEN);
            }
        }

        $data = json_decode($request->getContent(), true);
        $user->setLogin($data['login'] ?? $user->getLogin());
        $user->setPhone($data['phone'] ?? $user->getPhone());
        $user->setPass($data['pass'] ?? $user->getPass());

        try {
            $this->userService->update($user);
        } catch (ApiException $e) {
            return new JsonResponse(['error'=>$e->getMessage(), 'details'=>$e->getDetails()], $e->getCode() ?: 400);
        }

        return new JsonResponse($this->serializer->normalize($user), Response::HTTP_OK);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->userService->find($id);
        if (!$user) return new JsonResponse(['error'=>'Not found'], Response::HTTP_NOT_FOUND);

        if (!$this->isGranted('ROLE_ROOT')) {
            return new JsonResponse(['error'=>'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $this->userService->delete($user);
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
