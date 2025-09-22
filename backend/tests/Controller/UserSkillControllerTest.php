<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Skill;
use App\Entity\UserSkill;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class UserSkillControllerTest extends WebTestCase
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

    public function testGetUserSkillsWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/user-skills');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetUserSkillsWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $skill = $this->createTestSkill();
        $userSkill = $this->createTestUserSkill($user, $skill);
        $token = $this->jwtManager->create($user);

        $this->client->request('GET', '/api/user-skills', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
    }

    public function testGetUserSkillsByUserWithoutAuthentication(): void
    {
        $user = $this->createTestUser();

        $this->client->request('GET', '/api/user-skills/user/' . $user->getId());
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetUserSkillsByUserWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $skill = $this->createTestSkill();
        $userSkill = $this->createTestUserSkill($user, $skill);
        $token = $this->jwtManager->create($user);

        $this->client->request('GET', '/api/user-skills/user/' . $user->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertNotEmpty($responseData);
    }

    public function testCreateUserSkillWithoutAuthentication(): void
    {
        $this->client->request('POST', '/api/user-skills', [], [], [], json_encode([
            'skillId' => 1,
            'level' => 3
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateUserSkillWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $skill = $this->createTestSkill();
        $token = $this->jwtManager->create($user);

        $this->client->request('POST', '/api/user-skills', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'skillId' => $skill->getId(),
            'level' => 3,
            'experience' => 12,
            'certified' => false
        ]));

        $this->assertResponseStatusCodeSame(201);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('userSkill', $responseData);
        $this->assertEquals(3, $responseData['userSkill']['level']);
    }

    public function testCreateUserSkillWithInvalidData(): void
    {
        $user = $this->createTestUser();
        $token = $this->jwtManager->create($user);

        $this->client->request('POST', '/api/user-skills', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'skillId' => 999999, // Non-existent skill
            'level' => 6 // Invalid level (should be 1-5)
        ]));

        $this->assertResponseStatusCodeSame(400);
    }

    public function testUpdateUserSkillWithoutAuthentication(): void
    {
        $userSkill = $this->createTestUserSkill();

        $this->client->request('PUT', '/api/user-skills/' . $userSkill->getId(), [], [], [], json_encode([
            'level' => 4
        ]));

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateUserSkillWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $skill = $this->createTestSkill();
        $userSkill = $this->createTestUserSkill($user, $skill);
        $token = $this->jwtManager->create($user);

        $this->client->request('PUT', '/api/user-skills/' . $userSkill->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'level' => 4,
            'experience' => 24,
            'certified' => true,
            'certificationDate' => '2023-06-15'
        ]));

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('userSkill', $responseData);
        $this->assertEquals(4, $responseData['userSkill']['level']);
    }

    public function testDeleteUserSkillWithoutAuthentication(): void
    {
        $userSkill = $this->createTestUserSkill();

        $this->client->request('DELETE', '/api/user-skills/' . $userSkill->getId());

        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteUserSkillWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $skill = $this->createTestSkill();
        $userSkill = $this->createTestUserSkill($user, $skill);
        $token = $this->jwtManager->create($user);

        $this->client->request('DELETE', '/api/user-skills/' . $userSkill->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(204);
    }

    public function testGetUserSkillsBySkillWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $skill = $this->createTestSkill();
        $userSkill = $this->createTestUserSkill($user, $skill);
        $token = $this->jwtManager->create($user);

        $this->client->request('GET', '/api/user-skills/skill/' . $skill->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
    }

    public function testUserSkillWithInvalidJson(): void
    {
        $user = $this->createTestUser();
        $token = $this->jwtManager->create($user);

        $this->client->request('POST', '/api/user-skills', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], 'invalid-json');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testUserSkillHttpMethods(): void
    {
        $this->client->request('PATCH', '/api/user-skills');
        $this->assertResponseStatusCodeSame(405);

        $this->client->request('OPTIONS', '/api/user-skills');
        $this->assertResponseStatusCodeSame(405);
    }

    public function testUserSkillEndpointsRequireAuthentication(): void
    {
        $userSkill = $this->createTestUserSkill();
        
        $endpoints = [
            ['GET', '/api/user-skills'],
            ['POST', '/api/user-skills'],
            ['GET', '/api/user-skills/user/1'],
            ['GET', '/api/user-skills/skill/1'],
            ['PUT', '/api/user-skills/' . $userSkill->getId()],
            ['DELETE', '/api/user-skills/' . $userSkill->getId()]
        ];

        foreach ($endpoints as [$method, $endpoint]) {
            $this->client->request($method, $endpoint);
            $this->assertResponseStatusCodeSame(401, "Endpoint {$method} {$endpoint} should require authentication");
        }
    }

    public function testUserSkillOwnershipValidation(): void
    {
        $user1 = $this->createTestUser('user1@example.com');
        $user2 = $this->createTestUser('user2@example.com');
        $skill = $this->createTestSkill();
        $userSkill = $this->createTestUserSkill($user1, $skill);
        
        // User2 tries to modify User1's skill
        $token = $this->jwtManager->create($user2);

        $this->client->request('PUT', '/api/user-skills/' . $userSkill->getId(), [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'level' => 5
        ]));

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUserSkillLevelValidation(): void
    {
        $user = $this->createTestUser();
        $skill = $this->createTestSkill();
        $token = $this->jwtManager->create($user);

        // Test invalid level values
        $invalidLevels = [0, 6, -1, 'invalid'];

        foreach ($invalidLevels as $level) {
            $this->client->request('POST', '/api/user-skills', [], [], [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json'
            ], json_encode([
                'skillId' => $skill->getId(),
                'level' => $level
            ]));

            $this->assertResponseStatusCodeSame(400, "Level {$level} should be invalid");
        }
    }

    public function testUserSkillDuplicatePrevention(): void
    {
        $user = $this->createTestUser();
        $skill = $this->createTestSkill();
        $token = $this->jwtManager->create($user);

        // Create first user skill
        $this->client->request('POST', '/api/user-skills', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'skillId' => $skill->getId(),
            'level' => 3
        ]));

        $this->assertResponseStatusCodeSame(201);

        // Try to create duplicate
        $this->client->request('POST', '/api/user-skills', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'skillId' => $skill->getId(),
            'level' => 4
        ]));

        $this->assertResponseStatusCodeSame(409); // Conflict
    }

    private function createTestUser(string $email = 'test@example.com'): User
    {
        $user = new User();
        $user->setEmail($email . uniqid());
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('hashedpassword');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
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

    private function createTestUserSkill(?User $user = null, ?Skill $skill = null): UserSkill
    {
        if (!$user) {
            $user = $this->createTestUser();
        }
        if (!$skill) {
            $skill = $this->createTestSkill();
        }

        $userSkill = new UserSkill();
        $userSkill->setUser($user);
        $userSkill->setSkill($skill);
        $userSkill->setLevel(3);
        $userSkill->setExperience(12);
        $userSkill->setCertified(false);

        $this->entityManager->persist($userSkill);
        $this->entityManager->flush();

        return $userSkill;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
