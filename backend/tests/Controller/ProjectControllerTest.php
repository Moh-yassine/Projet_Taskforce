<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Project;
use App\Repository\UserRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class ProjectControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $userRepository;
    private $projectRepository;
    private $jwtManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->projectRepository = static::getContainer()->get(ProjectRepository::class);
        $this->jwtManager = static::getContainer()->get(JWTTokenManagerInterface::class);
    }

    public function testGetProjectsWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/projects');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetProjectsWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $token = $this->jwtManager->create($user);

        $this->client->request('GET', '/api/projects', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
    }

    public function testCreateProjectWithoutAuthentication(): void
    {
        $this->client->request('POST', '/api/projects', [], [], [], json_encode([
            'name' => 'Test Project',
            'description' => 'Test Description'
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateProjectWithAuthentication(): void
    {
        $user = $this->createTestUser(['ROLE_PROJECT_MANAGER']);
        $token = $this->jwtManager->create($user);

        $this->client->request('POST', '/api/projects', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'name' => 'Test Project',
            'description' => 'Test Description',
            'status' => 'active'
        ]));

        $this->assertResponseStatusCodeSame(201);
        
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('project', $responseData);
        $this->assertEquals('Test Project', $responseData['project']['name']);
    }

    public function testCreateProjectWithInvalidData(): void
    {
        $user = $this->createTestUser(['ROLE_PROJECT_MANAGER']);
        $token = $this->jwtManager->create($user);

        $this->client->request('POST', '/api/projects', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'name' => '', // Invalid empty name
            'description' => 'Test Description'
        ]));

        $this->assertResponseStatusCodeSame(400);
    }

    public function testUpdateProjectWithoutAuthentication(): void
    {
        $project = $this->createTestProject();

        $this->client->request('PUT', '/api/projects/' . $project->getId(), [], [], [], json_encode([
            'name' => 'Updated Project'
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateProjectWithAuthentication(): void
    {
        $user = $this->createTestUser(['ROLE_PROJECT_MANAGER']);
        $project = $this->createTestProject($user);
        $token = $this->jwtManager->create($user);

        $this->client->request('PUT', '/api/projects/' . $project->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'name' => 'Updated Project',
            'description' => 'Updated Description'
        ]));

        $this->assertResponseIsSuccessful();
    }

    public function testGetProjectByIdWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject($user);
        $token = $this->jwtManager->create($user);

        $this->client->request('GET', '/api/projects/' . $project->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($project->getName(), $responseData['name']);
    }

    public function testDeleteProjectWithoutAuthentication(): void
    {
        $project = $this->createTestProject();

        $this->client->request('DELETE', '/api/projects/' . $project->getId());

        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteProjectWithAuthentication(): void
    {
        $user = $this->createTestUser(['ROLE_ADMIN']);
        $project = $this->createTestProject($user);
        $token = $this->jwtManager->create($user);

        $this->client->request('DELETE', '/api/projects/' . $project->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(204);
    }

    public function testProjectWithInvalidJson(): void
    {
        $user = $this->createTestUser(['ROLE_PROJECT_MANAGER']);
        $token = $this->jwtManager->create($user);

        $this->client->request('POST', '/api/projects', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], 'invalid-json');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testProjectHttpMethods(): void
    {
        $this->client->request('PATCH', '/api/projects');
        $this->assertResponseStatusCodeSame(405);

        $this->client->request('OPTIONS', '/api/projects');
        $this->assertResponseStatusCodeSame(405);
    }

    public function testProjectRoutesExist(): void
    {
        $router = static::getContainer()->get('router');
        
        $this->assertNotNull($router->generate('api_projects_index'));
    }

    private function createTestUser(array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setEmail('test@example.com');
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
        $project->setName('Test Project');
        $project->setDescription('Test Description');
        $project->setStatus('active');

        if ($manager) {
            $project->setManager($manager);
        }

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
