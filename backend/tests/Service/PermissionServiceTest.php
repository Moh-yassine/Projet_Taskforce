<?php

namespace App\Tests\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Service\PermissionService;
use App\Entity\User;
use App\Entity\Project;
use App\Entity\Task;

class PermissionServiceTest extends KernelTestCase
{
    private PermissionService $permissionService;
    private User $projectManager;
    private User $manager;
    private User $collaborator;
    private Project $project;
    private Task $task;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        
        $this->permissionService = $container->get(PermissionService::class);
        
        // Pas besoin de persister les données pour les tests de service

        // Créer des utilisateurs de test avec différents rôles
        $this->projectManager = new User();
        $this->projectManager->setEmail('pm@example.com');
        $this->projectManager->setFirstName('Project');
        $this->projectManager->setLastName('Manager');
        $this->projectManager->setPassword('Password123!');
        $this->projectManager->setRoles(['ROLE_PROJECT_MANAGER']);

        $this->manager = new User();
        $this->manager->setEmail('manager@example.com');
        $this->manager->setFirstName('Manager');
        $this->manager->setLastName('User');
        $this->manager->setPassword('Password123!');
        $this->manager->setRoles(['ROLE_MANAGER']);

        $this->collaborator = new User();
        $this->collaborator->setEmail('collaborator@example.com');
        $this->collaborator->setFirstName('Collaborator');
        $this->collaborator->setLastName('User');
        $this->collaborator->setPassword('Password123!');
        $this->collaborator->setRoles(['ROLE_COLLABORATOR']);

        // Créer un projet de test
        $this->project = new Project();
        $this->project->setName('Test Project');
        $this->project->setDescription('Test Description');
        $this->project->setStatus('in_progress');
        $this->project->setProjectManager($this->projectManager);
        $this->project->setStartDate(new \DateTime('2024-01-01'));
        $this->project->setEndDate(new \DateTime('2024-12-31'));

        // Créer une tâche de test
        $this->task = new Task();
        $this->task->setTitle('Test Task');
        $this->task->setDescription('Test Description');
        $this->task->setPriority('high');
        $this->task->setStatus('pending');
        $this->task->setProject($this->project);
        $this->task->setAssignee($this->collaborator);
    }

    public function testCanManageProjects(): void
    {
        $this->assertTrue($this->permissionService->canManageProjects($this->projectManager));
        $this->assertFalse($this->permissionService->canManageProjects($this->manager));
        $this->assertFalse($this->permissionService->canManageProjects($this->collaborator));
    }

    public function testCanSuperviseTasks(): void
    {
        $this->assertTrue($this->permissionService->canSuperviseTasks($this->projectManager));
        $this->assertTrue($this->permissionService->canSuperviseTasks($this->manager));
        $this->assertFalse($this->permissionService->canSuperviseTasks($this->collaborator));
    }

    public function testCanAssignTasks(): void
    {
        $this->assertTrue($this->permissionService->canAssignTasks($this->projectManager));
        $this->assertFalse($this->permissionService->canAssignTasks($this->manager));
        $this->assertFalse($this->permissionService->canAssignTasks($this->collaborator));
    }

    public function testCanViewAllTasks(): void
    {
        $this->assertTrue($this->permissionService->canViewAllTasks($this->projectManager));
        $this->assertTrue($this->permissionService->canViewAllTasks($this->manager));
        $this->assertFalse($this->permissionService->canViewAllTasks($this->collaborator));
    }

    public function testCanEditTask(): void
    {
        // Le project manager peut éditer toutes les tâches de ses projets
        $this->assertTrue($this->permissionService->canEditTask($this->projectManager, $this->task));

        // Le manager ne peut pas éditer les tâches
        $this->assertFalse($this->permissionService->canEditTask($this->manager, $this->task));

        // Le collaborateur assigné peut éditer sa tâche
        $this->assertTrue($this->permissionService->canEditTask($this->collaborator, $this->task));

        // Un autre collaborateur ne peut pas éditer la tâche
        $otherCollaborator = new User();
        $otherCollaborator->setEmail('other@example.com');
        $otherCollaborator->setRoles(['ROLE_COLLABORATOR']);
        $this->assertFalse($this->permissionService->canEditTask($otherCollaborator, $this->task));
    }

    public function testCanViewProject(): void
    {
        // Le project manager peut voir son projet
        $this->assertTrue($this->permissionService->canViewProject($this->projectManager, $this->project));

        // Un utilisateur non assigné ne peut pas voir le projet
        $otherUser = new User();
        $otherUser->setEmail('other@example.com');
        $otherUser->setRoles(['ROLE_COLLABORATOR']);
        $this->assertFalse($this->permissionService->canViewProject($otherUser, $this->project));
    }

    public function testCanManageUsers(): void
    {
        $this->assertTrue($this->permissionService->canManageUsers($this->projectManager));
        $this->assertFalse($this->permissionService->canManageUsers($this->manager));
        $this->assertFalse($this->permissionService->canManageUsers($this->collaborator));
    }

    public function testCanManageSkills(): void
    {
        $this->assertTrue($this->permissionService->canManageSkills($this->projectManager));
        $this->assertFalse($this->permissionService->canManageSkills($this->manager));
        $this->assertFalse($this->permissionService->canManageSkills($this->collaborator));
    }

    public function testCanViewReports(): void
    {
        $this->assertTrue($this->permissionService->canViewReports($this->projectManager));
        $this->assertTrue($this->permissionService->canViewReports($this->manager));
        $this->assertFalse($this->permissionService->canViewReports($this->collaborator));
    }

    public function testCanAccessAdmin(): void
    {
        $this->assertTrue($this->permissionService->canAccessAdmin($this->projectManager));
        $this->assertTrue($this->permissionService->canAccessAdmin($this->manager));
        $this->assertFalse($this->permissionService->canAccessAdmin($this->collaborator));
    }

    public function testCanManageRoles(): void
    {
        $this->assertTrue($this->permissionService->canManageRoles($this->projectManager));
        $this->assertFalse($this->permissionService->canManageRoles($this->manager));
        $this->assertFalse($this->permissionService->canManageRoles($this->collaborator));
    }

    public function testCanViewNotifications(): void
    {
        $this->assertTrue($this->permissionService->canViewNotifications($this->projectManager));
        $this->assertTrue($this->permissionService->canViewNotifications($this->manager));
        $this->assertTrue($this->permissionService->canViewNotifications($this->collaborator));
    }

    public function testCanManageNotifications(): void
    {
        $this->assertTrue($this->permissionService->canManageNotifications($this->projectManager));
        $this->assertTrue($this->permissionService->canManageNotifications($this->manager));
        $this->assertFalse($this->permissionService->canManageNotifications($this->collaborator));
    }

    public function testGetUserPermissions(): void
    {
        $permissions = $this->permissionService->getUserPermissions($this->projectManager);

        $this->assertIsArray($permissions);
        $this->assertArrayHasKey('canManageProjects', $permissions);
        $this->assertArrayHasKey('canSuperviseTasks', $permissions);
        $this->assertArrayHasKey('canAssignTasks', $permissions);
        $this->assertArrayHasKey('canViewAllTasks', $permissions);
        $this->assertArrayHasKey('canManageUsers', $permissions);
        $this->assertArrayHasKey('canManageSkills', $permissions);
        $this->assertArrayHasKey('canViewReports', $permissions);
        $this->assertArrayHasKey('canAccessAdmin', $permissions);
        $this->assertArrayHasKey('canManageRoles', $permissions);
        $this->assertArrayHasKey('canViewNotifications', $permissions);
        $this->assertArrayHasKey('canManageNotifications', $permissions);
        $this->assertArrayHasKey('primaryRole', $permissions);
        $this->assertArrayHasKey('roles', $permissions);

        $this->assertTrue($permissions['canManageProjects']);
        $this->assertEquals('ROLE_PROJECT_MANAGER', $permissions['primaryRole']);
        $this->assertContains('ROLE_PROJECT_MANAGER', $permissions['roles']);
    }

    public function testPermissionConstants(): void
    {
        $this->assertEquals('ROLE_PROJECT_MANAGER', PermissionService::ROLE_PROJECT_MANAGER);
        $this->assertEquals('ROLE_MANAGER', PermissionService::ROLE_MANAGER);
        $this->assertEquals('ROLE_COLLABORATOR', PermissionService::ROLE_COLLABORATOR);
    }
}
