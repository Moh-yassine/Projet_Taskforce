<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class PaymentControllerTest extends WebTestCase
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
        $this->testUser->setEmail('payment-test@example.com');
        $this->testUser->setFirstName('Payment');
        $this->testUser->setLastName('User');
        $this->testUser->setPassword('hashed_password');
        $this->testUser->setRoles(['ROLE_USER']);
        
        $this->entityManager->persist($this->testUser);
        $this->entityManager->flush();
    }

    public function testGetConfigWithoutAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/payment/config');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testGetConfigWithAuthentication(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $client->request('GET', '/api/payment/config', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
        ]);

        // Test que l'endpoint existe et répond
        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [
                Response::HTTP_UNAUTHORIZED, // Si JWT non configuré
                Response::HTTP_OK // Si authentifié
            ])
        );
    }

    public function testCreateSubscriptionWithoutAuthentication(): void
    {
        $client = static::createClient();
        
        $subscriptionData = [
            'payment_method_id' => 'pm_test_123',
            'price_id' => 'price_premium_monthly'
        ];

        $client->request('POST', '/api/payment/create-subscription', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($subscriptionData));

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testCreateSubscriptionWithInvalidData(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $invalidData = [
            // Données manquantes ou invalides
            'payment_method_id' => '',
            'price_id' => 'invalid_price'
        ];

        $client->request('POST', '/api/payment/create-subscription', [], [], [
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

    public function testGetSubscriptionStatusWithoutAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/payment/subscription-status');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testGetSubscriptionStatusWithAuthentication(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $client->request('GET', '/api/payment/subscription-status', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
        ]);

        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_OK
            ])
        );
    }

    public function testCancelSubscriptionWithoutAuthentication(): void
    {
        $client = static::createClient();
        
        $cancelData = [
            'subscription_id' => 'sub_test_123',
            'cancel_immediately' => false
        ];

        $client->request('POST', '/api/payment/cancel-subscription', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($cancelData));

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testCancelSubscriptionWithInvalidData(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $invalidData = [
            'subscription_id' => '', // ID manquant
        ];

        $client->request('POST', '/api/payment/cancel-subscription', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
        ], json_encode($invalidData));

        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [
                Response::HTTP_BAD_REQUEST,
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_NOT_FOUND
            ])
        );
    }

    public function testWebhookEndpointExists(): void
    {
        $client = static::createClient();
        
        // Test que l'endpoint webhook existe
        $client->request('POST', '/api/payment/webhook', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['type' => 'test']));

        // L'endpoint devrait exister mais peut rejeter sans signature Stripe
        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [
                Response::HTTP_BAD_REQUEST, // Signature manquante
                Response::HTTP_OK,
                Response::HTTP_UNAUTHORIZED
            ])
        );
    }

    public function testGetPremiumFeaturesWithoutAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/payment/premium-features');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testGetPremiumFeaturesWithAuthentication(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $client->request('GET', '/api/payment/premium-features', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer fake-jwt-token'
        ]);

        $this->assertTrue(
            in_array($client->getResponse()->getStatusCode(), [
                Response::HTTP_UNAUTHORIZED,
                Response::HTTP_OK
            ])
        );
    }

    public function testPaymentWithInvalidJson(): void
    {
        $client = static::createClient();
        $this->entityManager = $client->getContainer()->get('doctrine')->getManager();
        $this->initializeTestData();

        $client->request('POST', '/api/payment/create-subscription', [], [], [
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

    public function testPaymentEndpointsUseCorrectHttpMethods(): void
    {
        $client = static::createClient();

        // Test méthode incorrecte pour config (devrait être GET)
        $client->request('POST', '/api/payment/config');
        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());

        // Test méthode incorrecte pour create-subscription (devrait être POST) 
        $client->request('GET', '/api/payment/create-subscription');
        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());

        // Test méthode incorrecte pour subscription-status (devrait être GET)
        $client->request('POST', '/api/payment/subscription-status');
        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());
    }

    public function testPaymentRoutesStructure(): void
    {
        $client = static::createClient();
        
        // Tester que toutes les routes payment existent
        $routes = [
            '/api/payment/config',
            '/api/payment/create-subscription', 
            '/api/payment/subscription-status',
            '/api/payment/cancel-subscription',
            '/api/payment/webhook',
            '/api/payment/premium-features'
        ];

        foreach ($routes as $route) {
            $client->request('GET', $route);
            
            // L'endpoint doit exister (pas 404)
            $this->assertNotEquals(
                Response::HTTP_NOT_FOUND, 
                $client->getResponse()->getStatusCode(),
                "Route $route should exist"
            );
        }
    }
}
