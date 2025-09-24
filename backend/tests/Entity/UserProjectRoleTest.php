<?php

namespace App\Tests\Entity;

use App\Entity\UserProjectRole;
use App\Entity\User;
use App\Entity\Project;
use PHPUnit\Framework\TestCase;

class UserProjectRoleTest extends TestCase
{
    public function testUserProjectRoleCreation(): void
    {
        $userProjectRole = new UserProjectRole();
        
        $this->assertInstanceOf(UserProjectRole::class, $userProjectRole);
        $this->assertNull($userProjectRole->getId());
        $this->assertNull($userProjectRole->getUser());
        $this->assertNull($userProjectRole->getProject());
        $this->assertNull($userProjectRole->getRole());
        $this->assertInstanceOf(\DateTimeInterface::class, $userProjectRole->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $userProjectRole->getUpdatedAt());
    }

    public function testUserProjectRoleUser(): void
    {
        $userProjectRole = new UserProjectRole();
        $user = new User();
        $user->setEmail('test@example.com');

        $userProjectRole->setUser($user);
        $this->assertEquals($user, $userProjectRole->getUser());
    }

    public function testUserProjectRoleProject(): void
    {
        $userProjectRole = new UserProjectRole();
        $project = new Project();
        $project->setName('Test Project');

        $userProjectRole->setProject($project);
        $this->assertEquals($project, $userProjectRole->getProject());
    }

    public function testUserProjectRoleRole(): void
    {
        $userProjectRole = new UserProjectRole();
        $role = 'project_manager';

        $userProjectRole->setRole($role);
        $this->assertEquals($role, $userProjectRole->getRole());
    }

    public function testUserProjectRoleValidRoles(): void
    {
        $userProjectRole = new UserProjectRole();
        $validRoles = ['project_manager', 'team_lead', 'collaborator', 'observer'];

        foreach ($validRoles as $role) {
            $userProjectRole->setRole($role);
            $this->assertEquals($role, $userProjectRole->getRole());
        }
    }

    public function testUserProjectRoleCreatedAt(): void
    {
        $userProjectRole = new UserProjectRole();
        $createdAt = new \DateTime('2023-01-01 10:00:00');

        $userProjectRole->setCreatedAt($createdAt);
        $this->assertEquals($createdAt, $userProjectRole->getCreatedAt());
    }

    public function testUserProjectRoleUpdatedAt(): void
    {
        $userProjectRole = new UserProjectRole();
        $updatedAt = new \DateTime('2023-01-02 15:30:00');

        $userProjectRole->setUpdatedAt($updatedAt);
        $this->assertEquals($updatedAt, $userProjectRole->getUpdatedAt());
    }

    public function testUserProjectRoleIsActive(): void
    {
        $userProjectRole = new UserProjectRole();
        
        $this->assertTrue($userProjectRole->isActive()); // Default should be true
        
        $userProjectRole->setIsActive(false);
        $this->assertFalse($userProjectRole->isActive());
        
        $userProjectRole->setIsActive(true);
        $this->assertTrue($userProjectRole->isActive());
    }

    public function testUserProjectRoleActivate(): void
    {
        $userProjectRole = new UserProjectRole();
        $userProjectRole->setIsActive(false);
        
        $userProjectRole->activate();
        $this->assertTrue($userProjectRole->isActive());
    }

    public function testUserProjectRoleDeactivate(): void
    {
        $userProjectRole = new UserProjectRole();
        $userProjectRole->setIsActive(true);
        
        $userProjectRole->deactivate();
        $this->assertFalse($userProjectRole->isActive());
    }

    public function testUserProjectRoleStartDate(): void
    {
        $userProjectRole = new UserProjectRole();
        $startDate = new \DateTime('2023-01-01');

        $userProjectRole->setStartDate($startDate);
        $this->assertEquals($startDate, $userProjectRole->getStartDate());
    }

    public function testUserProjectRoleEndDate(): void
    {
        $userProjectRole = new UserProjectRole();
        $endDate = new \DateTime('2023-12-31');

        $userProjectRole->setEndDate($endDate);
        $this->assertEquals($endDate, $userProjectRole->getEndDate());
    }

    public function testUserProjectRoleIsCurrent(): void
    {
        $userProjectRole = new UserProjectRole();
        $userProjectRole->setStartDate(new \DateTime('-1 month'));
        $userProjectRole->setEndDate(new \DateTime('+1 month'));
        
        $this->assertTrue($userProjectRole->isCurrent());
        
        // Test past role
        $userProjectRole->setEndDate(new \DateTime('-1 day'));
        $this->assertFalse($userProjectRole->isCurrent());
        
        // Test future role
        $userProjectRole->setStartDate(new \DateTime('+1 day'));
        $userProjectRole->setEndDate(new \DateTime('+1 month'));
        $this->assertFalse($userProjectRole->isCurrent());
    }

    public function testUserProjectRolePermissions(): void
    {
        $userProjectRole = new UserProjectRole();
        $permissions = ['edit_tasks', 'view_reports', 'manage_team'];

        $userProjectRole->setPermissions($permissions);
        $this->assertEquals($permissions, $userProjectRole->getPermissions());
    }

    public function testUserProjectRoleHasPermission(): void
    {
        $userProjectRole = new UserProjectRole();
        $permissions = ['edit_tasks', 'view_reports'];
        $userProjectRole->setPermissions($permissions);

        $this->assertTrue($userProjectRole->hasPermission('edit_tasks'));
        $this->assertTrue($userProjectRole->hasPermission('view_reports'));
        $this->assertFalse($userProjectRole->hasPermission('manage_team'));
    }

    public function testUserProjectRoleAddPermission(): void
    {
        $userProjectRole = new UserProjectRole();
        $userProjectRole->setPermissions(['edit_tasks']);

        $userProjectRole->addPermission('view_reports');
        
        $this->assertTrue($userProjectRole->hasPermission('edit_tasks'));
        $this->assertTrue($userProjectRole->hasPermission('view_reports'));
    }

    public function testUserProjectRoleRemovePermission(): void
    {
        $userProjectRole = new UserProjectRole();
        $userProjectRole->setPermissions(['edit_tasks', 'view_reports']);

        $userProjectRole->removePermission('edit_tasks');
        
        $this->assertFalse($userProjectRole->hasPermission('edit_tasks'));
        $this->assertTrue($userProjectRole->hasPermission('view_reports'));
    }

    public function testUserProjectRoleToArray(): void
    {
        $userProjectRole = new UserProjectRole();
        $user = new User();
        $user->setEmail('test@example.com');
        $project = new Project();
        $project->setName('Test Project');
        
        $userProjectRole->setUser($user);
        $userProjectRole->setProject($project);
        $userProjectRole->setRole('project_manager');
        $userProjectRole->setPermissions(['edit_tasks', 'view_reports']);

        $array = $userProjectRole->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals('project_manager', $array['role']);
        $this->assertEquals(['edit_tasks', 'view_reports'], $array['permissions']);
    }
}


