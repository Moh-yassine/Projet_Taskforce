<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserCreation(): void
    {
        $user = new User();
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertNull($user->getId());
    }

    public function testUserEmail(): void
    {
        $user = new User();
        $email = 'test@example.com';
        
        $user->setEmail($email);
        
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($email, $user->getUserIdentifier());
    }

    public function testUserNames(): void
    {
        $user = new User();
        $firstName = 'John';
        $lastName = 'Doe';
        
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        
        $this->assertEquals($firstName, $user->getFirstName());
        $this->assertEquals($lastName, $user->getLastName());
    }

    public function testUserRoles(): void
    {
        $user = new User();
        $roles = ['ROLE_USER', 'ROLE_MANAGER'];
        
        $user->setRoles($roles);
        
        $this->assertEquals($roles, $user->getRoles());
        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testUserPassword(): void
    {
        $user = new User();
        $password = 'hashed_password';
        
        $user->setPassword($password);
        
        $this->assertEquals($password, $user->getPassword());
    }

    public function testUserTimestamps(): void
    {
        $user = new User();
        $now = new \DateTimeImmutable();
        
        $user->setCreatedAt($now);
        $user->setUpdatedAt($now);
        
        $this->assertEquals($now, $user->getCreatedAt());
        $this->assertEquals($now, $user->getUpdatedAt());
    }

    public function testUserEraseCredentials(): void
    {
        $user = new User();
        
        // Cette méthode ne devrait pas lever d'exception
        $user->eraseCredentials();
        
        $this->assertTrue(true);
    }

    public function testUserSubscriptions(): void
    {
        $user = new User();
        
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $user->getSubscriptions());
        $this->assertCount(0, $user->getSubscriptions());
    }

    public function testUserCompany(): void
    {
        $user = new User();
        $company = 'Test Company';
        
        $user->setCompany($company);
        
        $this->assertEquals($company, $user->getCompany());
    }
}
