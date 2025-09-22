<?php

namespace App\Tests\Entity;

use App\Entity\Task;
use App\Entity\Project;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class TaskTest extends TestCase
{
    public function testTaskCreation(): void
    {
        $task = new Task();
        
        $this->assertInstanceOf(Task::class, $task);
        $this->assertNull($task->getId());
    }

    public function testTaskTitle(): void
    {
        $task = new Task();
        $title = 'Test Task';
        
        $task->setTitle($title);
        
        $this->assertEquals($title, $task->getTitle());
    }

    public function testTaskDescription(): void
    {
        $task = new Task();
        $description = 'This is a test task description';
        
        $task->setDescription($description);
        
        $this->assertEquals($description, $task->getDescription());
    }

    public function testTaskPriority(): void
    {
        $task = new Task();
        $priority = 'high';
        
        $task->setPriority($priority);
        
        $this->assertEquals($priority, $task->getPriority());
    }

    public function testTaskStatus(): void
    {
        $task = new Task();
        $status = 'in_progress';
        
        $task->setStatus($status);
        
        $this->assertEquals($status, $task->getStatus());
    }

    public function testTaskEstimatedHours(): void
    {
        $task = new Task();
        $hours = 8;
        
        $task->setEstimatedHours($hours);
        
        $this->assertEquals($hours, $task->getEstimatedHours());
    }

    public function testTaskActualHours(): void
    {
        $task = new Task();
        $hours = 10;
        
        $task->setActualHours($hours);
        
        $this->assertEquals($hours, $task->getActualHours());
    }

    public function testTaskTimestamps(): void
    {
        $task = new Task();
        $now = new \DateTimeImmutable();
        
        $task->setCreatedAt($now);
        $task->setUpdatedAt($now);
        
        $this->assertEquals($now, $task->getCreatedAt());
        $this->assertEquals($now, $task->getUpdatedAt());
    }

    public function testTaskProject(): void
    {
        $task = new Task();
        $project = new Project();
        $project->setName('Test Project');
        
        $task->setProject($project);
        
        $this->assertEquals($project, $task->getProject());
    }

    public function testTaskAssignee(): void
    {
        $task = new Task();
        $assignee = new User();
        $assignee->setEmail('assignee@example.com');
        
        $task->setAssignee($assignee);
        
        $this->assertEquals($assignee, $task->getAssignee());
    }

    public function testTaskDueDate(): void
    {
        $task = new Task();
        $dueDate = new \DateTime('2024-12-31');
        
        $task->setDueDate($dueDate);
        
        $this->assertEquals($dueDate, $task->getDueDate());
    }
}
