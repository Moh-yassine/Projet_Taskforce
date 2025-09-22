<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class SkillControllerTest extends WebTestCase
{
    public function testGetSkillsWithoutAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/skills');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testGetSkillWithoutAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/skills/1');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testCreateSkillWithoutAuthentication(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/skills', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Test Skill',
            'description' => 'Test Description'
        ]));

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testUpdateSkillWithoutAuthentication(): void
    {
        $client = static::createClient();
        $client->request('PUT', '/api/skills/1', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'name' => 'Updated Skill'
        ]));

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }

    public function testDeleteSkillWithoutAuthentication(): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/api/skills/1');

        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $client->getResponse()->getStatusCode());
    }
}
