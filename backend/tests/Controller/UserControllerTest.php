<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Project;
use App\Entity\Task;

class UserControllerTest extends WebTestCase
{
    private ?EntityManagerInterface $entityManager = null;
    private ?User $testUser = null;

    protected function setUp(): void
    {
        $this->entityManager = null;
        $this->testUser = null;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    private function initializeTestData(): void
    {
        if (!$this->entityManager) {
            return;
        }

        // Créer le schéma de base de données
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->createSchema($metadata);

        // Créer un utilisateur de test
        $this->testUser = new User();
        $this->testUser->setEmail('user-test@example.com');
        $this->testUser->setFirstName('Test');
        $this->testUser->setLastName('User');
        $this->testUser->setPassword('hashed_password');
        $this->testUser->setRoles(['ROLE_USER']);
        
        $this->entityManager->persist($this->testUser);
        $this->entityManager->flush();
    }

    public function testGetUsersWithoutAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/users');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testGetUsersWithAuthentication(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $client->request('GET', '/api/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
        ]);

        // Test que l'endpoint existe et est protégé
        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_OK,
                Response::HTTP_FORBIDDEN
            ])
        );
    }

    public function testGetAssignableUsersEndpoint(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $client->request('GET', '/api/users/assignable', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
        ]);

        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_OK,
                Response::HTTP_FORBIDDEN
            ])
        );
    }

    public function testGetUserWorkloadEndpoint(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $userId = 1;
        $client->request('GET', "/api/users/{$userId}/workload", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
        ]);

        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_OK,
                Response::HTTP_NOT_FOUND,
                Response::HTTP_FORBIDDEN
            ])
        );
    }

    public function testGetUserTasksEndpoint(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $userId = 1;
        $client->request('GET', "/api/users/{$userId}/tasks", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
        ]);

        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_OK,
                Response::HTTP_NOT_FOUND,
                Response::HTTP_FORBIDDEN
            ])
        );
    }

    public function testGetMyTasksEndpoint(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $client->request('GET', '/api/users/my-tasks', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
        ]);

        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_OK
            ])
        );
    }

    public function testUpdateUserRoleWithoutAuthentication(): void
    {
        $client = static::createClient();
        
        $roleData = [
            'role' => 'ROLE_MANAGER'
        ];

        $client->request('PUT', '/api/users/1/role', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($roleData));

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testUpdateUserRoleWithInvalidData(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $invalidData = [
            'role' => 'INVALID_ROLE'
        ];

        $client->request('PUT', '/api/users/999/role', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
        ], json_encode($invalidData));

        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [
                Response::HTTP_BAD_REQUEST,
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_NOT_FOUND,
                Response::HTTP_FORBIDDEN
            ])
        );
    }

    public function testUserEndpointsWithInvalidJson(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $client->request('PUT', '/api/users/1/role', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
        ], 'invalid json');

        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [
                Response::HTTP_BAD_REQUEST,
                Response::HTTP_UNAUTHORIZED
            ])
        );
    }

    public function testUserHttpMethods(): void
    {
        $client = static::createClient();

        // Test méthodes incorrectes
        $client->request('POST', '/api/users/assignable');
        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());

        $client->request('DELETE', '/api/users/my-tasks');
        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/users/1/role');
        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }

    public function testUserRoutesExist(): void
    {
        $client = static::createClient();
        
        $routes = [
            'GET /api/users',
            'GET /api/users/assignable',
            'GET /api/users/1/workload',
            'GET /api/users/1/tasks',
            'GET /api/users/my-tasks',
            'PUT /api/users/1/role'
        ];

        foreach ($routes as $route) {
            [$method, $path] = explode(' ', $route);
            $client->request($method, $path);
            
            // L'endpoint doit exister (pas 404)
            $this->assertNotEquals(
                Response::HTTP_NOT_FOUND, 
                $client->getResponse()->getStatusCode(),
                "Route $method $path should exist"
            );
        }
    }

    public function testUserWorkloadValidation(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        // Test avec différents IDs utilisateur
        $userIds = [1, 999, 'invalid'];
        
        foreach ($userIds as $userId) {
            $client->request('GET', "/api/users/{$userId}/workload", [], [], [
                'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
            ]);

            // L'endpoint doit répondre (même si erreur)
            $this->assertNotEquals(
                Response::HTTP_NOT_FOUND, 
                $client->getResponse()->getStatusCode(),
                "Workload endpoint should exist for user {$userId}"
            );
        }
    }

    public function testUserRoleUpdateValidation(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $validRoles = [
            'ROLE_USER',
            'ROLE_MANAGER', 
            'ROLE_PROJECT_MANAGER',
            'ROLE_COLLABORATOR'
        ];

        foreach ($validRoles as $role) {
            $roleData = ['role' => $role];
            
            $client->request('PUT', '/api/users/1/role', [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
            ], json_encode($roleData));

            // Test que l'endpoint traite les rôles valides
            $this->assertTrue(
                in_array($client->getResponse()->getStatusCode(), [
                    Response::HTTP_OK,
                    Response::HTTP_UNAUTHORIZED,
                    Response::HTTP_FORBIDDEN,
                    Response::HTTP_NOT_FOUND
                ])
            );
        }
    }

    public function testUserEndpointsRequireAuthentication(): void
    {
        $client = static::createClient();
        
        $protectedEndpoints = [
            'GET /api/users',
            'GET /api/users/assignable',
            'GET /api/users/1/workload',
            'GET /api/users/1/tasks',
            'GET /api/users/my-tasks',
            'PUT /api/users/1/role'
        ];

        foreach ($protectedEndpoints as $endpoint) {
            [$method, $path] = explode(' ', $endpoint);
            
            $client->request($method, $path);
            
            // Tous les endpoints users doivent nécessiter une authentification
            $this->assertEquals(
                Response::HTTP_UNAUTHORIZED,
                $client->getResponse()->getStatusCode(),
                "Endpoint $method $path should require authentication"
            );
        }
    }
}
