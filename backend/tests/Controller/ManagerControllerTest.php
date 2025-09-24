<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\Notification;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class ManagerControllerTest extends WebTestCase
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

    public function testGetDashboardWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/manager/dashboard');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetDashboardWithWrongRole(): void
    {
        $user = $this->createTestUser(['ROLE_USER']);
        $token = $this->jwtManager->create($user);

        $this->client->request('GET', '/api/manager/dashboard', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetDashboardWithManagerRole(): void
    {
        $manager = $this->createTestUser(['ROLE_MANAGER']);
        $collaborator = $this->createTestUser(['ROLE_USER'], 'collaborator@example.com');
        $project = $this->createTestProject($manager);
        $task = $this->createTestTask($collaborator, $project);
        
        $token = $this->jwtManager->create($manager);

        $this->client->request('GET', '/api/manager/dashboard', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('stats', $responseData);
        $this->assertArrayHasKey('collaborators', $responseData);
        $this->assertArrayHasKey('tasksInProgress', $responseData);
        $this->assertArrayHasKey('recentAlerts', $responseData);
        $this->assertArrayHasKey('supervisedProjects', $responseData);
    }

    public function testGetTasksProgressWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/manager/tasks/progress');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetTasksProgressWithManagerRole(): void
    {
        $manager = $this->createTestUser(['ROLE_MANAGER']);
        $collaborator = $this->createTestUser(['ROLE_USER'], 'collaborator@example.com');
        $project = $this->createTestProject($manager);
        $task = $this->createTestTask($collaborator, $project);
        
        $token = $this->jwtManager->create($manager);

        $this->client->request('GET', '/api/manager/tasks/progress', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertIsArray($responseData);
    }

    public function testGetTaskDistributionReportWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/manager/reports/tasks');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetTaskDistributionReportWithManagerRole(): void
    {
        $manager = $this->createTestUser(['ROLE_MANAGER']);
        $collaborator = $this->createTestUser(['ROLE_USER'], 'collaborator@example.com');
        $project = $this->createTestProject($manager);
        $task = $this->createTestTask($collaborator, $project);
        
        $token = $this->jwtManager->create($manager);

        $this->client->request('GET', '/api/manager/reports/tasks', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('totalTasks', $responseData);
        $this->assertArrayHasKey('tasksByStatus', $responseData);
        $this->assertArrayHasKey('tasksByPriority', $responseData);
        $this->assertArrayHasKey('tasksByCollaborator', $responseData);
        $this->assertArrayHasKey('overdueTasks', $responseData);
        $this->assertArrayHasKey('averageCompletionTime', $responseData);
    }

    public function testGetProductivityReportWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/manager/reports/productivity');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetProductivityReportWithManagerRole(): void
    {
        $manager = $this->createTestUser(['ROLE_MANAGER']);
        $collaborator = $this->createTestUser(['ROLE_USER'], 'collaborator@example.com');
        $project = $this->createTestProject($manager);
        $task = $this->createTestTask($collaborator, $project);
        
        $token = $this->jwtManager->create($manager);

        $this->client->request('GET', '/api/manager/reports/productivity', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertIsArray($responseData);
    }

    public function testGetWorkloadReportWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/manager/reports/workload');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetWorkloadReportWithManagerRole(): void
    {
        $manager = $this->createTestUser(['ROLE_MANAGER']);
        $collaborator = $this->createTestUser(['ROLE_USER'], 'collaborator@example.com');
        $project = $this->createTestProject($manager);
        $task = $this->createTestTask($collaborator, $project);
        
        $token = $this->jwtManager->create($manager);

        $this->client->request('GET', '/api/manager/reports/workload', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertIsArray($responseData);
    }

    public function testAssignTaskWithoutAuthentication(): void
    {
        $this->client->request('POST', '/api/manager/tasks/assign', [], [], [], json_encode([
            'taskId' => 1,
            'userId' => 1
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testAssignTaskWithManagerRole(): void
    {
        $manager = $this->createTestUser(['ROLE_MANAGER']);
        $collaborator = $this->createTestUser(['ROLE_USER'], 'collaborator@example.com');
        $project = $this->createTestProject($manager);
        $task = $this->createTestTask($collaborator, $project);
        
        $token = $this->jwtManager->create($manager);

        $this->client->request('POST', '/api/manager/tasks/assign', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'taskId' => $task->getId(),
            'userId' => $collaborator->getId()
        ]));

        $this->assertResponseIsSuccessful();
    }

    public function testReassignTaskWithoutAuthentication(): void
    {
        $this->client->request('POST', '/api/manager/tasks/reassign', [], [], [], json_encode([
            'taskId' => 1,
            'newUserId' => 1
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testReassignTaskWithManagerRole(): void
    {
        $manager = $this->createTestUser(['ROLE_MANAGER']);
        $collaborator1 = $this->createTestUser(['ROLE_USER'], 'collaborator1@example.com');
        $collaborator2 = $this->createTestUser(['ROLE_USER'], 'collaborator2@example.com');
        $project = $this->createTestProject($manager);
        $task = $this->createTestTask($collaborator1, $project);
        
        $token = $this->jwtManager->create($manager);

        $this->client->request('POST', '/api/manager/tasks/reassign', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'taskId' => $task->getId(),
            'newUserId' => $collaborator2->getId()
        ]));

        $this->assertResponseIsSuccessful();
    }

    public function testUpdateTaskPriorityWithoutAuthentication(): void
    {
        $this->client->request('PUT', '/api/manager/tasks/1/priority', [], [], [], json_encode([
            'priority' => 'high'
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateTaskPriorityWithManagerRole(): void
    {
        $manager = $this->createTestUser(['ROLE_MANAGER']);
        $collaborator = $this->createTestUser(['ROLE_USER'], 'collaborator@example.com');
        $project = $this->createTestProject($manager);
        $task = $this->createTestTask($collaborator, $project);
        
        $token = $this->jwtManager->create($manager);

        $this->client->request('PUT', '/api/manager/tasks/' . $task->getId() . '/priority', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'priority' => 'high'
        ]));

        $this->assertResponseIsSuccessful();
    }

    public function testSetTaskDeadlineWithoutAuthentication(): void
    {
        $this->client->request('PUT', '/api/manager/tasks/1/deadline', [], [], [], json_encode([
            'dueDate' => '2023-12-31'
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testSetTaskDeadlineWithManagerRole(): void
    {
        $manager = $this->createTestUser(['ROLE_MANAGER']);
        $collaborator = $this->createTestUser(['ROLE_USER'], 'collaborator@example.com');
        $project = $this->createTestProject($manager);
        $task = $this->createTestTask($collaborator, $project);
        
        $token = $this->jwtManager->create($manager);

        $this->client->request('PUT', '/api/manager/tasks/' . $task->getId() . '/deadline', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'dueDate' => '2023-12-31'
        ]));

        $this->assertResponseIsSuccessful();
    }

    public function testGetCollaboratorDetailsWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/manager/collaborators/1');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetCollaboratorDetailsWithManagerRole(): void
    {
        $manager = $this->createTestUser(['ROLE_MANAGER']);
        $collaborator = $this->createTestUser(['ROLE_USER'], 'collaborator@example.com');
        $project = $this->createTestProject($manager);
        $task = $this->createTestTask($collaborator, $project);
        
        $token = $this->jwtManager->create($manager);

        $this->client->request('GET', '/api/manager/collaborators/' . $collaborator->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('user', $responseData);
        $this->assertArrayHasKey('tasks', $responseData);
        $this->assertArrayHasKey('productivity', $responseData);
        $this->assertArrayHasKey('workload', $responseData);
    }

    public function testManagerEndpointsRequireAuthentication(): void
    {
        $endpoints = [
            ['GET', '/api/manager/dashboard'],
            ['GET', '/api/manager/tasks/progress'],
            ['GET', '/api/manager/reports/tasks'],
            ['GET', '/api/manager/reports/productivity'],
            ['GET', '/api/manager/reports/workload'],
            ['POST', '/api/manager/tasks/assign'],
            ['POST', '/api/manager/tasks/reassign'],
            ['PUT', '/api/manager/tasks/1/priority'],
            ['PUT', '/api/manager/tasks/1/deadline'],
            ['GET', '/api/manager/collaborators/1']
        ];

        foreach ($endpoints as [$method, $endpoint]) {
            $this->client->request($method, $endpoint);
            $this->assertResponseStatusCodeSame(401, "Endpoint {$method} {$endpoint} should require authentication");
        }
    }

    public function testManagerWithInvalidJson(): void
    {
        $manager = $this->createTestUser(['ROLE_MANAGER']);
        $token = $this->jwtManager->create($manager);

        $this->client->request('POST', '/api/manager/tasks/assign', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], 'invalid-json');

        $this->assertResponseStatusCodeSame(400);
    }

    private function createTestUser(array $roles = ['ROLE_USER'], string $email = 'test@example.com'): User
    {
        $user = new User();
        $user->setEmail($email);
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
        $project = new Project();
        $project->setName('Test Project ' . uniqid());
        $project->setDescription('Test Description');
        $project->setStatus('active');

        if ($manager) {
            $project->setManager($manager);
        }

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return $project;
    }

    private function createTestTask(?User $assignee = null, ?Project $project = null): Task
    {
        if (!$assignee) {
            $assignee = $this->createTestUser();
        }
        if (!$project) {
            $project = $this->createTestProject();
        }

        $task = new Task();
        $task->setTitle('Test Task ' . uniqid());
        $task->setDescription('Test Description');
        $task->setStatus('todo');
        $task->setPriority('medium');
        $task->setProject($project);
        $task->setAssignee($assignee);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}


