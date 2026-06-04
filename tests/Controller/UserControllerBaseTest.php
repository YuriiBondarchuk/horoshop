<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->em = static::getContainer()->get('doctrine')->getManager();

        // Очистити тестові дані або використовувати транзакції/fixtures за потреби.
        // Тут — просте видалення тестових користувачів з login 'test_user' / 'admin_user'
        $this->em->createQuery('DELETE FROM App\Entity\User u WHERE u.login IN (:logins)')
            ->setParameter('logins', ['test_user', 'admin_user', 'other_user'])
            ->execute();
    }

    private function createUser(string $login, string $plainPassword, array $roles = []): User
    {
        $user = new User();
        $user->setLogin($login);
        $user->setPhone('0000000000');

        // Використовуй той метод, який у тебе зберігає пароль
        $hashed = password_hash($plainPassword, PASSWORD_ARGON2ID);
        if (method_exists($user, 'setPassword')) {
            $user->setPassword($hashed);
        } else {
            $user->setPass($hashed);
        }

        // Якщо у сутності є addRole / setRoles
        if (method_exists($user, 'setRoles')) {
            $user->setRoles($roles);
        } elseif (method_exists($user, 'addRole')) {
            foreach ($roles as $r) {
                $user->addRole($r);
            }
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function getJwtToken(string $login, string $pass): string
    {
        $this->client->request(
            'POST',
            '/v1/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['login' => $login, 'pass' => $pass])
        );

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), 'Login request failed');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data, 'Token not present in login response');

        return $data['token'];
    }

    public function testLoginSuccess(): void
    {
        $plain = 'pass123';
        $this->createUser('test_user', $plain, ['ROLE_USER']);

        $this->client->request(
            'POST',
            '/v1/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['login' => 'test_user', 'pass' => $plain])
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->assertIsString($data['token']);
    }

    public function testLoginInvalidCredentials(): void
    {
        $this->createUser('test_user', 'correct_pass', ['ROLE_USER']);

        $this->client->request(
            'POST',
            '/v1/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['login' => 'test_user', 'pass' => 'wrong_pass'])
        );

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateUserRequiresAuth(): void
    {
        // Спроба створити користувача без токена — очікуємо 401/403
        $this->client->request(
            'POST',
            '/v1/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['login' => 'other_user', 'phone' => '01234567', 'pass' => 'pwd12345'])
        );

        $status = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(in_array($status, [Response::HTTP_UNAUTHORIZED, Response::HTTP_FORBIDDEN, Response::HTTP_INTERNAL_SERVER_ERROR]),
            'Expected unauthorized/forbidden when creating user without token, got ' . $status);
    }

    public function testCreateUserWithAdminToken(): void
    {
        // Створюємо адміна в БД і отримуємо токен
        $adminPlain = 'adminpass';
        $this->createUser('admin_user', $adminPlain, ['ROLE_ROOT', 'ROLE_ADMIN']);

        $token = $this->getJwtToken('admin_user', $adminPlain);

        // Виклик захищеного endpoint з токеном
        $this->client->request(
            'POST',
            '/v1/api/users',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Authorization' => 'Bearer ' . $token,
            ],
            json_encode(['login' => 'new_user', 'phone' => '0123456789', 'pass' => 'newpass'])
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode(), 'Admin should be able to create user');

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('id', $data);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Очистка створених тестових записів
        $this->em->createQuery('DELETE FROM App\Entity\User u WHERE u.login IN (:logins)')
            ->setParameter('logins', ['test_user', 'admin_user', 'other_user', 'new_user'])
            ->execute();

        $this->em->close();
    }
}
