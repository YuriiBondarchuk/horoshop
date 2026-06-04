<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerBaseTest extends WebTestCase
{
    protected const string API_ROUTE = '/v1/api/users';
    protected const string TEST_LOGIN = 'test';
    protected const string TEST_PASSWORD = self::TEST_LOGIN;

    protected KernelBrowser $client;
    private EntityManagerInterface $em;

    private User $user;
    private Role $roleRoot;
    private UserRole $userRole;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->em = static::getContainer()->get('doctrine')->getManager();

        $this->createUser(self::TEST_LOGIN, self::TEST_PASSWORD);
        $this->initRoleRoot();
        $this->createUserRole();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeUserRole();
        $this->removeUser();
    }

    private function createUser(string $login, string $pass): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['login' => $login]);
        if (!$user) {
            $user = (new User())
                ->setLogin($login)
                ->setPass($pass)
                ->setPhone('000000');

            $this->em->persist($user);
            $this->em->flush();
        }
        $this->user = $user;
    }

    private function createUserRole(): void
    {
        $userRole = $this->em->getRepository(UserRole::class)->findOneBy(['user' => $this->user]);
        if (!$userRole) {
            $userRole = (new UserRole())
                ->setUser($this->user)
                ->setRole($this->roleRoot);

            $this->em->persist($userRole);
            $this->em->flush();
        }

        $this->userRole = $userRole;
    }

    private function removeUserRole(): void
    {
        $userRole = $this->em->getReference(UserRole::class, $this->userRole->getId());

        $this->em->remove($userRole);
        $this->em->flush();
        $this->em->clear();
    }

    private function removeUser(): void
    {
        $user = $this->em->getReference(User::class, $this->user->getId());
        $this->em->remove($user);
        $this->em->flush();

        $this->em->clear();
    }

    private function initRoleRoot(): void
    {
        $this->roleRoot = $this->em->getRepository(Role::class)->findOneBy(['name' => Role::ROLE_ROOT]);
    }
}
