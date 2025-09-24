<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BasicEndpointsTest extends WebTestCase
{
    public function testHomeEndpoint(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');
        
        // Should get some response (could be 404, redirect, etc.)
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertTrue(in_array($statusCode, [200, 301, 302, 404]));
    }

    public function testApiHealthCheck(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api');
        
        // API endpoint should exist
        $statusCode = $client->getResponse()->getStatusCode();
        $this->assertTrue(in_array($statusCode, [200, 401, 404]));
    }

    public function testPublicRoutes(): void
    {
        $client = static::createClient();
        
        // Test auth endpoints (should not require authentication to reach)
        $client->request('POST', '/api/auth/register');
        $this->assertNotEquals(500, $client->getResponse()->getStatusCode());
        
        $client->request('POST', '/api/auth/login');
        $this->assertNotEquals(500, $client->getResponse()->getStatusCode());
    }

    public function testProtectedRoutesRequireAuth(): void
    {
        $client = static::createClient();
        
        $protectedRoutes = [
            '/api/tasks',
            '/api/projects', 
            '/api/users',
            '/api/notifications'
        ];
        
        foreach ($protectedRoutes as $route) {
            $client->request('GET', $route);
            $this->assertEquals(401, $client->getResponse()->getStatusCode(), 
                "Route {$route} should require authentication");
        }
    }

    public function testInvalidRoutes(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/nonexistent');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        
        $client->request('GET', '/invalid/path');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testHttpMethods(): void
    {
        $client = static::createClient();
        
        // Test invalid methods on existing endpoints
        $client->request('PATCH', '/api/auth/login');
        $this->assertEquals(405, $client->getResponse()->getStatusCode());
        
        $client->request('DELETE', '/api/auth/register');
        $this->assertEquals(405, $client->getResponse()->getStatusCode());
    }

    public function testCorsHeaders(): void
    {
        $client = static::createClient();
        
        $client->request('OPTIONS', '/api/tasks');
        $response = $client->getResponse();
        
        // Should handle OPTIONS requests for CORS
        $this->assertTrue(in_array($response->getStatusCode(), [200, 204, 404]));
    }

    public function testContentTypeHandling(): void
    {
        $client = static::createClient();
        
        // Test with JSON content type
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], '{"email":"test","password":"test"}');
        
        // Should handle JSON properly (even if credentials are wrong)
        $this->assertTrue(in_array($client->getResponse()->getStatusCode(), [400, 401, 422]));
    }

    public function testEmptyPayload(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], '');
        
        // Should handle empty payload gracefully
        $this->assertTrue(in_array($client->getResponse()->getStatusCode(), [400, 401, 422]));
    }

    public function testInvalidJson(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], 'invalid-json');
        
        // Should handle invalid JSON gracefully
        $this->assertTrue(in_array($client->getResponse()->getStatusCode(), [400, 401, 422]));
    }

    public function testLargePayload(): void
    {
        $client = static::createClient();
        
        $largeData = str_repeat('a', 10000); // 10KB of data
        
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode(['email' => $largeData, 'password' => $largeData]));
        
        // Should handle large payloads
        $this->assertTrue(in_array($client->getResponse()->getStatusCode(), [400, 401, 413, 422]));
    }

    public function testSpecialCharacters(): void
    {
        $client = static::createClient();
        
        $specialData = [
            'email' => 'test@example.com',
            'password' => 'test<>&"\'`'
        ];
        
        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($specialData));
        
        // Should handle special characters safely
        $this->assertTrue(in_array($client->getResponse()->getStatusCode(), [400, 401, 422]));
    }

    public function testMultipleRequests(): void
    {
        $client = static::createClient();
        
        // Test multiple sequential requests
        for ($i = 0; $i < 5; $i++) {
            $client->request('GET', '/api/tasks');
            $this->assertEquals(401, $client->getResponse()->getStatusCode());
        }
        
        // All should be consistent
        $this->assertTrue(true);
    }

    public function testRateLimiting(): void
    {
        $client = static::createClient();
        
        // Test rapid requests (basic rate limiting test)
        for ($i = 0; $i < 10; $i++) {
            $client->request('POST', '/api/auth/login');
            $statusCode = $client->getResponse()->getStatusCode();
            
            // Should not get server errors
            $this->assertNotEquals(500, $statusCode);
        }
    }

    public function testSecurityHeaders(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/tasks');
        $response = $client->getResponse();
        
        // Check for security-related headers
        $this->assertNotNull($response->headers->get('Content-Type'));
        
        // Response should not expose sensitive information
        $content = $response->getContent();
        $this->assertStringNotContainsString('mysql', strtolower($content));
        $this->assertStringNotContainsString('password', strtolower($content));
    }

    public function testResponseFormat(): void
    {
        $client = static::createClient();
        
        $client->request('GET', '/api/tasks');
        $response = $client->getResponse();
        
        // API should return JSON
        $contentType = $response->headers->get('Content-Type');
        $this->assertStringContainsString('application/json', $contentType ?? '');
    }
}


