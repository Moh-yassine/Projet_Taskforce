<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Project;
use App\Entity\Workload;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class WorkloadControllerTest extends WebTestCase
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

    public function testGetWorkloadWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/workload');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetWorkloadWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $token = $this->jwtManager->create($user);

        $this->client->request('GET', '/api/workload', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
    }

    public function testGetUserWorkloadWithoutAuthentication(): void
    {
        $user = $this->createTestUser();

        $this->client->request('GET', '/api/workload/user/' . $user->getId());
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetUserWorkloadWithAuthentication(): void
    {
        $user = $this->createTestUser(['ROLE_PROJECT_MANAGER']);
        $targetUser = $this->createTestUser();
        $token = $this->jwtManager->create($user);

        $this->client->request('GET', '/api/workload/user/' . $targetUser->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('workload', $responseData);
    }

    public function testCreateWorkloadWithoutAuthentication(): void
    {
        $this->client->request('POST', '/api/workload', [], [], [], json_encode([
            'hours' => 40,
            'week' => '2023-W01'
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateWorkloadWithAuthentication(): void
    {
        $user = $this->createTestUser(['ROLE_PROJECT_MANAGER']);
        $project = $this->createTestProject();
        $token = $this->jwtManager->create($user);

        $this->client->request('POST', '/api/workload', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'userId' => $user->getId(),
            'projectId' => $project->getId(),
            'hours' => 40,
            'week' => '2023-W01'
        ]));

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('workload', $responseData);
    }

    public function testCreateWorkloadWithInvalidData(): void
    {
        $user = $this->createTestUser(['ROLE_PROJECT_MANAGER']);
        $token = $this->jwtManager->create($user);

        $this->client->request('POST', '/api/workload', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'hours' => -5, // Invalid negative hours
            'week' => 'invalid-week'
        ]));

        $this->assertResponseStatusCodeSame(400);
    }

    public function testUpdateWorkloadWithoutAuthentication(): void
    {
        $workload = $this->createTestWorkload();

        $this->client->request('PUT', '/api/workload/' . $workload->getId(), [], [], [], json_encode([
            'hours' => 35
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateWorkloadWithAuthentication(): void
    {
        $user = $this->createTestUser(['ROLE_PROJECT_MANAGER']);
        $workload = $this->createTestWorkload($user);
        $token = $this->jwtManager->create($user);

        $this->client->request('PUT', '/api/workload/' . $workload->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'hours' => 35
        ]));

        $this->assertResponseIsSuccessful();
    }

    public function testDeleteWorkloadWithoutAuthentication(): void
    {
        $workload = $this->createTestWorkload();

        $this->client->request('DELETE', '/api/workload/' . $workload->getId());

        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteWorkloadWithAuthentication(): void
    {
        $user = $this->createTestUser(['ROLE_PROJECT_MANAGER']);
        $workload = $this->createTestWorkload($user);
        $token = $this->jwtManager->create($user);

        $this->client->request('DELETE', '/api/workload/' . $workload->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(204);
    }

    public function testWorkloadWithInvalidJson(): void
    {
        $user = $this->createTestUser(['ROLE_PROJECT_MANAGER']);
        $token = $this->jwtManager->create($user);

        $this->client->request('POST', '/api/workload', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], 'invalid-json');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testWorkloadHttpMethods(): void
    {
        $this->client->request('PATCH', '/api/workload');
        $this->assertResponseStatusCodeSame(405);

        $this->client->request('OPTIONS', '/api/workload');
        $this->assertResponseStatusCodeSame(405);
    }

    public function testWorkloadEndpointsRequireAuthentication(): void
    {
        $endpoints = [
            ['GET', '/api/workload'],
            ['POST', '/api/workload'],
            ['GET', '/api/workload/user/1'],
            ['GET', '/api/workload/team/1']
        ];

        foreach ($endpoints as [$method, $endpoint]) {
            $this->client->request($method, $endpoint);
            $this->assertResponseStatusCodeSame(401, "Endpoint {$method} {$endpoint} should require authentication");
        }
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

    private function createTestWorkload(?User $user = null): Workload
    {
        if (!$user) {
            $user = $this->createTestUser();
        }

        $project = $this->createTestProject();
        
        $workload = new Workload();
        $workload->setUser($user);
        $workload->setProject($project);
        $workload->setHours(40);
        $workload->setWeek('2023-W01');

        $this->entityManager->persist($workload);
        $this->entityManager->flush();

        return $workload;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
