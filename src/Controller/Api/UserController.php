<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Role;
use App\Entity\User;
use App\Exception\ApiException;
use App\Repository\UserRepository;
use App\Service\Contract\UserServiceInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/v1/api/users')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly UserServiceInterface $userService,
        private readonly SerializerInterface $serializer,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly UserRepository $userRepository
    ) {
    }

    #[Route('/{id}', name: 'users_get', methods: ['GET'])]
    public function get(string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->userService->find((int)$id);
        if (!$user) {
            return new JsonResponse(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $this->checkUserAccess($user);

        $normalizedData = $this->serializer->normalize($user, null, ['groups' => 'get']);

        return new JsonResponse($normalizedData, Response::HTTP_OK);
    }

    #[Route('', name: 'users_post', methods: ['POST'])]
    public function post(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $data = json_decode($request->getContent(), true) ?? [];

        $this->validateRequiredFields($data, ['login', 'phone', 'pass']);

        $currentUser = $this->getUser();

        $user = $this->userRepository->findOneBy(['login' => $currentUser->getLogin(), 'pass' => $currentUser->getPass()]);

        $this->checkUserAccess($user);

        $user = new User();
        $user->setLogin($data['login'] ?? '');
        $user->setPhone($data['phone'] ?? '');
        $user->setPass($data['pass']);

        try {
            $this->userService->create($user);
        } catch (ApiException $e) {
            return new JsonResponse(['error' => $e->getMessage(), 'details' => $e->getDetails()],
                $e->getCode() ?: Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($this->normalizeData($user, 'post'), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'users_put', methods: ['PUT'])]
    public function put(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->userService->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $this->checkUserAccess($user);

        $data = json_decode($request->getContent(), true) ?? [];

        $this->validateRequiredFields($data, ['login', 'phone', 'pass', 'id']);

        $user->setLogin((string)$data['login']);
        $user->setPhone((string)$data['phone']);
        $user->setPass((string)$data['pass']);

        try {
            $this->userService->update($user);
        } catch (ApiException $e) {
            return new JsonResponse(
                ['error' => $e->getMessage(), 'details' => $e->getDetails()],
                $e->getCode() ?: Response::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse($this->normalizeData($user, 'put'), Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'users_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->userService->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        // Only root can delete
        if (!$this->isGranted(Role::ROLE_ROOT)) {
            return new JsonResponse(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $this->userService->delete($user);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/login', name: 'users_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR) ?? [];
        $login = $data['login'] ?? null;
        $pass = $data['pass'] ?? null;

        if (!$login || !$pass) {
            return new JsonResponse(['error' => 'login and pass required'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findOneBy(['login' => $login]);
        if (!$user || $pass !== $user->getPassword()) {
            return new JsonResponse(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        $payload = [
            'sub' => (string)$user->getId(),
            'roles' => $user->getRoles(),
        ];

        $token = $this->jwtManager->createFromPayload($user, $payload);

        return new JsonResponse(['token' => $token], Response::HTTP_OK);
    }

    private function normalizeData(User $user, string $normalizeGroup): array
    {
        return $this->serializer->normalize($user, null, ['groups' => $normalizeGroup]);
    }

    private function validateRequiredFields(array $data, array $requiredFields): void
    {
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            throw new ApiException(
                'All required fields must be passed.', Response::HTTP_BAD_REQUEST,
                ['missing_fields' => $missingFields]
            );
        }
    }

    private function checkUserAccess(User $requestedUser): void
    {
        $current = $this->getUser();

        if (!$current instanceof User) {
            throw new ApiException('Unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->isGranted('ROLE_ROOT') && $current->getId() !== $requestedUser->getId()) {
            throw new ApiException('Forbidden', Response::HTTP_FORBIDDEN);
        }
    }
}
