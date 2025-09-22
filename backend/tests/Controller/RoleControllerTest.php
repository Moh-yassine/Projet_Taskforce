<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class RoleControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $userRepository;
    private $jwtManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->jwtManager = static::getContainer()->get(JWTTokenManagerInterface::class);
    }

    public function testGetRolesWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/roles');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetRolesWithAuthentication(): void
    {
        $user = $this->createTestUser(['ROLE_ADMIN']);
        $token = $this->jwtManager->create($user);

        $this->client->request('GET', '/api/roles', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
    }

    public function testGetRolePermissionsWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/roles/ROLE_USER/permissions');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetRolePermissionsWithAuthentication(): void
    {
        $user = $this->createTestUser(['ROLE_ADMIN']);
        $token = $this->jwtManager->create($user);

        $this->client->request('GET', '/api/roles/ROLE_USER/permissions', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('role', $responseData);
        $this->assertArrayHasKey('permissions', $responseData);
    }

    public function testCreateRoleWithoutAuthentication(): void
    {
        $this->client->request('POST', '/api/roles', [], [], [], json_encode([
            'name' => 'ROLE_TEST',
            'permissions' => ['view_projects']
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateRoleWithAuthentication(): void
    {
        $user = $this->createTestUser(['ROLE_ADMIN']);
        $token = $this->jwtManager->create($user);

        $this->client->request('POST', '/api/roles', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'name' => 'ROLE_TEST',
            'permissions' => ['view_projects', 'edit_tasks']
        ]));

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('role', $responseData);
        $this->assertEquals('ROLE_TEST', $responseData['role']['name']);
    }

    public function testCreateRoleWithInvalidData(): void
    {
        $user = $this->createTestUser(['ROLE_ADMIN']);
        $token = $this->jwtManager->create($user);

        $this->client->request('POST', '/api/roles', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'name' => '', // Invalid empty name
            'permissions' => []
        ]));

        $this->assertResponseStatusCodeSame(400);
    }

    public function testUpdateRoleWithoutAuthentication(): void
    {
        $this->client->request('PUT', '/api/roles/ROLE_USER', [], [], [], json_encode([
            'permissions' => ['view_projects']
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateRoleWithAuthentication(): void
    {
        $user = $this->createTestUser(['ROLE_ADMIN']);
        $token = $this->jwtManager->create($user);

        $this->client->request('PUT', '/api/roles/ROLE_USER', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'permissions' => ['view_projects', 'view_tasks']
        ]));

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('role', $responseData);
        $this->assertArrayHasKey('permissions', $responseData);
    }

    public function testDeleteRoleWithoutAuthentication(): void
    {
        $this->client->request('DELETE', '/api/roles/ROLE_TEST');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteRoleWithAuthentication(): void
    {
        $user = $this->createTestUser(['ROLE_ADMIN']);
        $token = $this->jwtManager->create($user);

        // First create a role to delete
        $this->client->request('POST', '/api/roles', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'name' => 'ROLE_TO_DELETE',
            'permissions' => ['view_projects']
        ]));

        $this->assertResponseStatusCodeSame(201);

        // Then delete it
        $this->client->request('DELETE', '/api/roles/ROLE_TO_DELETE', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(204);
    }

    public function testDeleteProtectedRole(): void
    {
        $user = $this->createTestUser(['ROLE_ADMIN']);
        $token = $this->jwtManager->create($user);

        $this->client->request('DELETE', '/api/roles/ROLE_ADMIN', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(400);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testRoleWithInvalidJson(): void
    {
        $user = $this->createTestUser(['ROLE_ADMIN']);
        $token = $this->jwtManager->create($user);

        $this->client->request('POST', '/api/roles', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], 'invalid-json');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testRoleHttpMethods(): void
    {
        $this->client->request('PATCH', '/api/roles');
        $this->assertResponseStatusCodeSame(405);

        $this->client->request('OPTIONS', '/api/roles');
        $this->assertResponseStatusCodeSame(405);
    }

    public function testRoleEndpointsRequireAuthentication(): void
    {
        $endpoints = [
            ['GET', '/api/roles'],
            ['POST', '/api/roles'],
            ['GET', '/api/roles/ROLE_USER/permissions'],
            ['PUT', '/api/roles/ROLE_USER'],
            ['DELETE', '/api/roles/ROLE_TEST']
        ];

        foreach ($endpoints as [$method, $endpoint]) {
            $this->client->request($method, $endpoint);
            $this->assertResponseStatusCodeSame(401, "Endpoint {$method} {$endpoint} should require authentication");
        }
    }

    public function testGetAvailablePermissions(): void
    {
        $user = $this->createTestUser(['ROLE_ADMIN']);
        $token = $this->jwtManager->create($user);

        $this->client->request('GET', '/api/roles/permissions', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('permissions', $responseData);
    }

    public function testRoleValidation(): void
    {
        $user = $this->createTestUser(['ROLE_ADMIN']);
        $token = $this->jwtManager->create($user);

        // Test with invalid role name format
        $this->client->request('POST', '/api/roles', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'name' => 'invalid-role-name', // Should start with ROLE_
            'permissions' => ['view_projects']
        ]));

        $this->assertResponseStatusCodeSame(400);
    }

    public function testRoleAccessControl(): void
    {
        // Test that only ADMIN can manage roles
        $user = $this->createTestUser(['ROLE_PROJECT_MANAGER']);
        $token = $this->jwtManager->create($user);

        $this->client->request('POST', '/api/roles', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'name' => 'ROLE_TEST',
            'permissions' => ['view_projects']
        ]));

        $this->assertResponseStatusCodeSame(403);
    }

    private function createTestUser(array $roles = ['ROLE_USER'], string $email = 'test@example.com'): User
    {
        $user = new User();
        $user->setEmail($email . uniqid());
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRoles($roles);
        $user->setPassword('hashedpassword');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
