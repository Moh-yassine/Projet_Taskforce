<?php

namespace App\Tests\Integration;

use App\Entity\User;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\Skill;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class ControllerIntegrationTest extends WebTestCase
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

    public function testBasicAuthenticationFlow(): void
    {
        // Test registration
        $this->client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => 'integration@example.com',
            'password' => 'password123',
            'firstName' => 'Integration',
            'lastName' => 'Test'
        ]));

        $this->assertResponseIsSuccessful();
        
        // Test login
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => 'integration@example.com',
            'password' => 'password123'
        ]));

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $responseData);
    }

    public function testSkillManagement(): void
    {
        $user = $this->createTestUser(['ROLE_ADMIN']);
        $token = $this->jwtManager->create($user);

        // Create skill
        $this->client->request('POST', '/api/skills', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'name' => 'PHP Integration',
            'description' => 'PHP programming skill'
        ]));

        $this->assertResponseStatusCodeSame(201);
        
        // Get skills
        $this->client->request('GET', '/api/skills', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testProjectWorkflow(): void
    {
        $manager = $this->createTestUser(['ROLE_PROJECT_MANAGER']);
        $token = $this->jwtManager->create($manager);

        // Create project
        $this->client->request('POST', '/api/projects', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'name' => 'Integration Test Project',
            'description' => 'Test project for integration',
            'status' => 'active'
        ]));

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $projectId = $responseData['project']['id'];

        // Get projects
        $this->client->request('GET', '/api/projects', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();

        // Update project
        $this->client->request('PUT', '/api/projects/' . $projectId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'name' => 'Updated Integration Project',
            'status' => 'in_progress'
        ]));

        $this->assertResponseIsSuccessful();
    }

    public function testTaskManagement(): void
    {
        $manager = $this->createTestUser(['ROLE_PROJECT_MANAGER']);
        $user = $this->createTestUser(['ROLE_USER'], 'worker@example.com');
        $project = $this->createTestProject($manager);
        $token = $this->jwtManager->create($manager);

        // Create task
        $this->client->request('POST', '/api/tasks', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'Integration Test Task',
            'description' => 'Test task for integration',
            'priority' => 'high',
            'status' => 'todo',
            'projectId' => $project->getId(),
            'assigneeId' => $user->getId()
        ]));

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $taskId = $responseData['id'];

        // Get tasks
        $this->client->request('GET', '/api/tasks', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();

        // Update task
        $this->client->request('PUT', '/api/tasks/' . $taskId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'Updated Integration Task',
            'status' => 'in_progress'
        ]));

        $this->assertResponseIsSuccessful();
    }

    public function testUserManagement(): void
    {
        $admin = $this->createTestUser(['ROLE_ADMIN']);
        $token = $this->jwtManager->create($admin);

        // Get users
        $this->client->request('GET', '/api/users', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();

        // Get assignable users
        $this->client->request('GET', '/api/users/assignable', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testNotificationFlow(): void
    {
        $user = $this->createTestUser();
        $token = $this->jwtManager->create($user);

        // Get notifications
        $this->client->request('GET', '/api/notifications', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();

        // Get my notifications
        $this->client->request('GET', '/api/notifications/my-notifications', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testPaymentEndpoints(): void
    {
        $user = $this->createTestUser();
        $token = $this->jwtManager->create($user);

        // Get payment config
        $this->client->request('GET', '/api/payment/config', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();

        // Get subscription status
        $this->client->request('GET', '/api/payment/subscription-status', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testRoleManagement(): void
    {
        $admin = $this->createTestUser(['ROLE_ADMIN']);
        $token = $this->jwtManager->create($admin);

        // Get roles
        $this->client->request('GET', '/api/roles', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();

        // Get role permissions
        $this->client->request('GET', '/api/roles/ROLE_USER/permissions', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testWorkloadEndpoints(): void
    {
        $manager = $this->createTestUser(['ROLE_PROJECT_MANAGER']);
        $token = $this->jwtManager->create($manager);

        // Get workload
        $this->client->request('GET', '/api/workload', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testAlertEndpoints(): void
    {
        $user = $this->createTestUser();
        $token = $this->jwtManager->create($user);

        // Check workload alerts
        $this->client->request('POST', '/api/alerts/check-workload', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();

        // Check delay alerts
        $this->client->request('POST', '/api/alerts/check-delays', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testManagerDashboard(): void
    {
        $manager = $this->createTestUser(['ROLE_MANAGER']);
        $token = $this->jwtManager->create($manager);

        // Get manager dashboard
        $this->client->request('GET', '/api/manager/dashboard', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();

        // Get tasks progress
        $this->client->request('GET', '/api/manager/tasks/progress', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testAllEndpointsRequireAuthentication(): void
    {
        $protectedEndpoints = [
            ['GET', '/api/tasks'],
            ['GET', '/api/projects'],
            ['GET', '/api/users'],
            ['GET', '/api/notifications'],
            ['GET', '/api/payment/config'],
            ['GET', '/api/roles'],
            ['GET', '/api/workload'],
            ['GET', '/api/manager/dashboard']
        ];

        foreach ($protectedEndpoints as [$method, $endpoint]) {
            $this->client->request($method, $endpoint);
            $this->assertResponseStatusCodeSame(401, "Endpoint {$method} {$endpoint} should require authentication");
        }
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

    private function createTestProject(?User $manager = null): Project
    {
        if (!$manager) {
            $manager = $this->createTestUser(['ROLE_PROJECT_MANAGER']);
        }

        $project = new Project();
        $project->setName('Test Project ' . uniqid());
        $project->setDescription('Test Description');
        $project->setStatus('active');
        $project->setManager($manager);

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return $project;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}





