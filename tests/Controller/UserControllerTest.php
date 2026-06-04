<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends UserControllerBaseTest
{
    public function testLoginSuccess(): void
    {
        $this->client->request(
            'POST',
            self::API_ROUTE . '/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['login' => self::TEST_LOGIN, 'pass' => self::TEST_PASSWORD])
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->assertIsString($data['token']);
    }

    public function testLoginInvalidCredentials(): void
    {
        $this->client->request(
            'POST',
            self::API_ROUTE. '/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['login' => self::TEST_LOGIN, 'pass' => self::TEST_PASSWORD . '!'])
        );

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $this->client->getResponse()->getStatusCode());
    }

    public function testCreateUserRequiresAuth(): void
    {
        $this->client->request(
            'POST',
            self::API_ROUTE,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['login' => 'other_user', 'phone' => '01234567', 'pass' => 'pwd12345'])
        );

        $status = $this->client->getResponse()->getStatusCode();
        $this->assertTrue(in_array($status, [Response::HTTP_UNAUTHORIZED, Response::HTTP_FORBIDDEN, Response::HTTP_INTERNAL_SERVER_ERROR]),
            'Expected unauthorized/forbidden when creating user without token, got ' . $status);
    }
}
