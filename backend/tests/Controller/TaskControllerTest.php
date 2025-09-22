<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\Skill;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class TaskControllerTest extends WebTestCase
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

    public function testGetTasksWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/tasks');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetTasksWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $token = $this->jwtManager->create($user);

        $this->client->request('GET', '/api/tasks', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
    }

    public function testCreateTaskWithoutAuthentication(): void
    {
        $this->client->request('POST', '/api/tasks', [], [], [], json_encode([
            'title' => 'Test Task',
            'description' => 'Test Description',
            'priority' => 'high'
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateTaskWithInvalidData(): void
    {
        $user = $this->createTestUser(['ROLE_PROJECT_MANAGER']);
        $token = $this->jwtManager->create($user);

        $this->client->request('POST', '/api/tasks', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => '',
            'priority' => 'invalid'
        ]));

        $this->assertResponseStatusCodeSame(400);
    }

    public function testUpdateTaskWithoutAuthentication(): void
    {
        $task = $this->createTestTask();

        $this->client->request('PUT', '/api/tasks/' . $task->getId(), [], [], [], json_encode([
            'title' => 'Updated Task'
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteTaskWithoutAuthentication(): void
    {
        $task = $this->createTestTask();

        $this->client->request('DELETE', '/api/tasks/' . $task->getId());

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetTasksByProject(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject();
        $task = $this->createTestTask($user, $project);
        $token = $this->jwtManager->create($user);

        $this->client->request('GET', '/api/tasks?project_id=' . $project->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testTaskWithInvalidJson(): void
    {
        $this->client->request('POST', '/api/tasks', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], 'invalid-json');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateTaskWithAuthentication(): void
    {
        $user = $this->createTestUser(['ROLE_PROJECT_MANAGER']);
        $project = $this->createTestProject();
        $token = $this->jwtManager->create($user);

        $this->client->request('POST', '/api/tasks', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'New Task',
            'description' => 'Task Description',
            'priority' => 'high',
            'status' => 'todo',
            'projectId' => $project->getId(),
            'assigneeId' => $user->getId(),
            'estimatedHours' => 8,
            'dueDate' => '2023-12-31'
        ]));

        $this->assertResponseStatusCodeSame(201);
    }

    public function testShowTaskWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $task = $this->createTestTask($user);
        $token = $this->jwtManager->create($user);

        $this->client->request('GET', '/api/tasks/' . $task->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testUpdateTaskWithAuthentication(): void
    {
        $user = $this->createTestUser(['ROLE_PROJECT_MANAGER']);
        $task = $this->createTestTask($user);
        $token = $this->jwtManager->create($user);

        $this->client->request('PUT', '/api/tasks/' . $task->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'Updated Task',
            'description' => 'Updated Description',
            'status' => 'in_progress',
            'actualHours' => 5
        ]));

        $this->assertResponseIsSuccessful();
    }

    public function testDeleteTaskWithAuthentication(): void
    {
        $user = $this->createTestUser(['ROLE_PROJECT_MANAGER']);
        $task = $this->createTestTask($user);
        $token = $this->jwtManager->create($user);

        $this->client->request('DELETE', '/api/tasks/' . $task->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(204);
    }

    public function testTriggerAlertsWithAuthentication(): void
    {
        $user = $this->createTestUser(['ROLE_PROJECT_MANAGER']);
        $token = $this->jwtManager->create($user);

        $this->client->request('POST', '/api/tasks/trigger-alerts', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('alerts', $responseData);
    }

    public function testTriggerAlertsWithoutAuthentication(): void
    {
        $this->client->request('POST', '/api/tasks/trigger-alerts');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateTaskWithSkills(): void
    {
        $user = $this->createTestUser(['ROLE_PROJECT_MANAGER']);
        $project = $this->createTestProject();
        $skill = $this->createTestSkill();
        $token = $this->jwtManager->create($user);

        $this->client->request('POST', '/api/tasks', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'Task with Skills',
            'description' => 'Task Description',
            'priority' => 'medium',
            'projectId' => $project->getId(),
            'skillIds' => [$skill->getId()]
        ]));

        $this->assertResponseStatusCodeSame(201);
    }

    public function testTaskEndpointsRequireAuthentication(): void
    {
        $task = $this->createTestTask();
        
        $endpoints = [
            ['GET', '/api/tasks'],
            ['POST', '/api/tasks'],
            ['GET', '/api/tasks/' . $task->getId()],
            ['PUT', '/api/tasks/' . $task->getId()],
            ['DELETE', '/api/tasks/' . $task->getId()],
            ['POST', '/api/tasks/trigger-alerts']
        ];

        foreach ($endpoints as [$method, $endpoint]) {
            $this->client->request($method, $endpoint);
            $this->assertResponseStatusCodeSame(401, "Endpoint {$method} {$endpoint} should require authentication");
        }
    }

    public function testTaskHttpMethods(): void
    {
        $this->client->request('PATCH', '/api/tasks');
        $this->assertResponseStatusCodeSame(405);

        $this->client->request('OPTIONS', '/api/tasks');
        $this->assertResponseStatusCodeSame(405);
    }

    private function createTestUser(array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setEmail('test' . uniqid() . '@example.com');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRoles($roles);
        $user->setPassword('hashedpassword');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createTestTask($user = null, $project = null): Task
    {
        if (!$user) {
            $user = $this->createTestUser();
        }
        if (!$project) {
            $project = $this->createTestProject();
        }

        $task = new Task();
        $task->setTitle('Test Task');
        $task->setDescription('Test Description');
        $task->setStatus('todo');
        $task->setPriority('medium');
        $task->setProject($project);
        $task->setAssignee($user);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }

    private function createTestProject(): Project
    {
        $project = new Project();
        $project->setName('Test Project ' . uniqid());
        $project->setDescription('Test Description');
        $project->setStatus('active');

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return $project;
    }

    private function createTestSkill(): Skill
    {
        $skill = new Skill();
        $skill->setName('Test Skill ' . uniqid());
        $skill->setDescription('Test Skill Description');

        $this->entityManager->persist($skill);
        $this->entityManager->flush();

        return $skill;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}