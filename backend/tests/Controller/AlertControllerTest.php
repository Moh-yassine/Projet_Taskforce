<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class AlertControllerTest extends WebTestCase
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

    public function testCheckWorkloadAlertsWithoutAuthentication(): void
    {
        $this->client->request('POST', '/api/alerts/check-workload');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testCheckWorkloadAlertsWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $token = $this->jwtManager->create($user);

        $this->client->request('POST', '/api/alerts/check-workload', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('alerts', $responseData);
    }

    public function testCheckDelayAlertsWithoutAuthentication(): void
    {
        $this->client->request('POST', '/api/alerts/check-delays');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testCheckDelayAlertsWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $token = $this->jwtManager->create($user);

        $this->client->request('POST', '/api/alerts/check-delays', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('alerts', $responseData);
    }

    public function testCleanupOldAlertsWithoutAuthentication(): void
    {
        $this->client->request('POST', '/api/alerts/cleanup');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testCleanupOldAlertsWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $token = $this->jwtManager->create($user);

        $this->client->request('POST', '/api/alerts/cleanup', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
    }

    public function testCheckInactiveAlertsWithAuthentication(): void
    {
        $user = $this->createTestUser();
        $token = $this->jwtManager->create($user);

        $this->client->request('POST', '/api/alerts/check-inactive', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('alerts', $responseData);
    }

    public function testAlertHttpMethods(): void
    {
        $this->client->request('GET', '/api/alerts/check-workload');
        $this->assertResponseStatusCodeSame(405);

        $this->client->request('PUT', '/api/alerts/check-delays');
        $this->assertResponseStatusCodeSame(405);

        $this->client->request('DELETE', '/api/alerts/cleanup');
        $this->assertResponseStatusCodeSame(405);
    }

    public function testAlertRoutes(): void
    {
        $router = static::getContainer()->get('router');
        
        $this->assertNotNull($router->generate('check_workload_alerts'));
        $this->assertNotNull($router->generate('check_delay_alerts'));
        $this->assertNotNull($router->generate('cleanup_old_alerts'));
    }

    public function testAlertEndpointsRequireAuthentication(): void
    {
        $endpoints = [
            '/api/alerts/check-workload',
            '/api/alerts/check-delays',
            '/api/alerts/cleanup',
            '/api/alerts/check-inactive'
        ];

        foreach ($endpoints as $endpoint) {
            $this->client->request('POST', $endpoint);
            $this->assertResponseStatusCodeSame(401, "Endpoint {$endpoint} should require authentication");
        }
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

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
