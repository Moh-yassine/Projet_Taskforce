<?php

namespace App\Tests\Entity;

use App\Entity\Project;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ProjectTest extends TestCase
{
    public function testProjectCreation(): void
    {
        $project = new Project();
        
        $this->assertInstanceOf(Project::class, $project);
        $this->assertNull($project->getId());
    }

    public function testProjectName(): void
    {
        $project = new Project();
        $name = 'Test Project';
        
        $project->setName($name);
        
        $this->assertEquals($name, $project->getName());
    }

    public function testProjectDescription(): void
    {
        $project = new Project();
        $description = 'This is a test project description';
        
        $project->setDescription($description);
        
        $this->assertEquals($description, $project->getDescription());
    }

    public function testProjectStatus(): void
    {
        $project = new Project();
        $status = 'in_progress';
        
        $project->setStatus($status);
        
        $this->assertEquals($status, $project->getStatus());
    }

    public function testProjectDates(): void
    {
        $project = new Project();
        $startDate = new \DateTime('2024-01-01');
        $endDate = new \DateTime('2024-12-31');
        
        $project->setStartDate($startDate);
        $project->setEndDate($endDate);
        
        $this->assertEquals($startDate, $project->getStartDate());
        $this->assertEquals($endDate, $project->getEndDate());
    }

    public function testProjectTimestamps(): void
    {
        $project = new Project();
        $now = new \DateTimeImmutable();
        
        $project->setCreatedAt($now);
        $project->setUpdatedAt($now);
        
        $this->assertEquals($now, $project->getCreatedAt());
        $this->assertEquals($now, $project->getUpdatedAt());
    }

    public function testProjectManager(): void
    {
        $project = new Project();
        $manager = new User();
        $manager->setEmail('manager@example.com');
        
        $project->setProjectManager($manager);
        
        $this->assertEquals($manager, $project->getProjectManager());
    }

    public function testProjectTeamMembers(): void
    {
        $project = new Project();
        
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $project->getTeamMembers());
        $this->assertCount(0, $project->getTeamMembers());
    }

    public function testProjectTasks(): void
    {
        $project = new Project();
        
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $project->getTasks());
        $this->assertCount(0, $project->getTasks());
    }
}
