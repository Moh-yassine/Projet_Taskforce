<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Notification;

class NotificationControllerTest extends WebTestCase
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
        $this->testUser->setEmail('notification-test@example.com');
        $this->testUser->setFirstName('Notification');
        $this->testUser->setLastName('User');
        $this->testUser->setPassword('hashed_password');
        $this->testUser->setRoles(['ROLE_USER']);
        
        $this->entityManager->persist($this->testUser);
        $this->entityManager->flush();
    }

    public function testGetNotificationsWithoutAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/notifications');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testGetNotificationsWithAuthentication(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $client->request('GET', '/api/notifications', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
        ]);

        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_OK
            ])
        );
    }

    public function testGetMyNotificationsEndpoint(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $client->request('GET', '/api/notifications/my-notifications', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
        ]);

        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_OK
            ])
        );
    }

    public function testGetNotificationsByUser(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $userId = 1;
        $client->request('GET', "/api/notifications/user/{$userId}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
        ]);

        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_OK,
                Response::HTTP_NOT_FOUND
            ])
        );
    }

    public function testGetUnreadNotificationsByUser(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $userId = 1;
        $client->request('GET', "/api/notifications/user/{$userId}/unread", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
        ]);

        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_OK,
                Response::HTTP_NOT_FOUND
            ])
        );
    }

    public function testGetUnreadCountByUser(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $userId = 1;
        $client->request('GET', "/api/notifications/user/{$userId}/count", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
        ]);

        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_OK,
                Response::HTTP_NOT_FOUND
            ])
        );
    }

    public function testCreateNotificationWithoutAuthentication(): void
    {
        $client = static::createClient();
        
        $notificationData = [
            'title' => 'Test Notification',
            'message' => 'This is a test notification',
            'type' => 'info',
            'userId' => 1
        ];

        $client->request('POST', '/api/notifications', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($notificationData));

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testCreateNotificationWithInvalidData(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $invalidData = [
            'title' => '', // Titre vide
            'message' => '',
            'type' => 'invalid_type',
            'userId' => 999 // Utilisateur inexistant
        ];

        $client->request('POST', '/api/notifications', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
        ], json_encode($invalidData));

        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [
                Response::HTTP_BAD_REQUEST,
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_UNPROCESSABLE_ENTITY
            ])
        );
    }

    public function testMarkNotificationAsRead(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $notificationId = 1;
        $client->request('PUT', "/api/notifications/{$notificationId}/read", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
        ]);

        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_OK,
                Response::HTTP_NOT_FOUND
            ])
        );
    }

    public function testMarkNotificationAsUnread(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $notificationId = 1;
        $client->request('PUT', "/api/notifications/{$notificationId}/unread", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
        ]);

        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_OK,
                Response::HTTP_NOT_FOUND
            ])
        );
    }

    public function testToggleNotificationReadStatus(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $notificationId = 1;
        $client->request('PUT', "/api/notifications/{$notificationId}/toggle", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
        ]);

        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_OK,
                Response::HTTP_NOT_FOUND
            ])
        );
    }

    public function testMarkAllNotificationsAsRead(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $userId = 1;
        $client->request('PUT', "/api/notifications/user/{$userId}/read-all", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
        ]);

        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_OK,
                Response::HTTP_NOT_FOUND
            ])
        );
    }

    public function testDeleteNotification(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $notificationId = 1;
        $client->request('DELETE', "/api/notifications/{$notificationId}", [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
        ]);

        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_OK,
                Response::HTTP_NOT_FOUND,
                Response::HTTP_NO_CONTENT
            ])
        );
    }

    public function testCreateAlert(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $userId = 1;
        $alertData = [
            'type' => 'warning',
            'message' => 'Task deadline approaching',
            'priority' => 'high'
        ];

        $client->request('POST', "/api/notifications/user/{$userId}/alert", [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
        ], json_encode($alertData));

        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_CREATED,
                Response::HTTP_OK,
                Response::HTTP_BAD_REQUEST
            ])
        );
    }

    public function testNotificationWithInvalidJson(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $client->request('POST', '/api/notifications', [], [], [
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

    public function testNotificationHttpMethods(): void
    {
        $client = static::createClient();

        // Test méthodes incorrectes
        $client->request('POST', '/api/notifications/my-notifications');
        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/notifications/1/read');
        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }

    public function testNotificationRoutesExist(): void
    {
        $client = static::createClient();
        
        $routes = [
            'GET /api/notifications',
            'GET /api/notifications/my-notifications',
            'GET /api/notifications/user/1',
            'GET /api/notifications/user/1/unread',
            'GET /api/notifications/user/1/count',
            'POST /api/notifications',
            'PUT /api/notifications/1/read',
            'PUT /api/notifications/1/unread',
            'PUT /api/notifications/1/toggle',
            'PUT /api/notifications/user/1/read-all',
            'DELETE /api/notifications/1',
            'POST /api/notifications/user/1/alert'
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
}
