<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\Skill;
use App\Entity\UserSkill;
use App\Entity\UserProjectRole;
use App\Entity\Notification;
use App\Entity\Workload;
use App\Entity\Subscription;
use PHPUnit\Framework\TestCase;

class CompleteEntityTest extends TestCase
{
    public function testAllEntitiesCreation(): void
    {
        // Test User
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setPassword('password123');
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);
        $user->setCompany('Test Company');
        $user->setPosition('Developer');
        $user->setPhone('+1234567890');
        $user->setLastLoginAt(new \DateTime());

        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('John', $user->getFirstName());
        $this->assertEquals('Doe', $user->getLastName());
        $this->assertEquals('John Doe', $user->getFullName());
        $this->assertTrue($user->isActive());
        $this->assertContains('ROLE_USER', $user->getRoles());

        // Test Project
        $project = new Project();
        $project->setName('Test Project');
        $project->setDescription('A test project');
        $project->setStatus('active');
        $project->setManager($user);
        $project->setStartDate(new \DateTime());
        $project->setEndDate(new \DateTime('+3 months'));
        $project->setBudget(50000);
        $project->setPriority('high');

        $this->assertEquals('Test Project', $project->getName());
        $this->assertEquals('active', $project->getStatus());
        $this->assertEquals($user, $project->getManager());
        $this->assertEquals(50000, $project->getBudget());
        $this->assertTrue($project->isActive());

        // Test Task
        $task = new Task();
        $task->setTitle('Test Task');
        $task->setDescription('A test task');
        $task->setStatus('todo');
        $task->setPriority('medium');
        $task->setProject($project);
        $task->setAssignee($user);
        $task->setEstimatedHours(8);
        $task->setActualHours(0);
        $task->setDueDate(new \DateTime('+1 week'));

        $this->assertEquals('Test Task', $task->getTitle());
        $this->assertEquals('todo', $task->getStatus());
        $this->assertEquals($project, $task->getProject());
        $this->assertEquals($user, $task->getAssignee());
        $this->assertEquals(8, $task->getEstimatedHours());
        $this->assertFalse($task->isCompleted());
        $this->assertFalse($task->isOverdue());

        // Test Skill
        $skill = new Skill();
        $skill->setName('PHP');
        $skill->setDescription('PHP Programming Language');
        $skill->setCategory('Programming');
        $skill->setLevel('intermediate');

        $this->assertEquals('PHP', $skill->getName());
        $this->assertEquals('Programming', $skill->getCategory());
        $this->assertEquals('intermediate', $skill->getLevel());

        // Test UserSkill
        $userSkill = new UserSkill();
        $userSkill->setUser($user);
        $userSkill->setSkill($skill);
        $userSkill->setLevel(4);
        $userSkill->setExperience(24);
        $userSkill->setCertified(true);
        $userSkill->setCertificationDate(new \DateTime('-6 months'));
        $userSkill->setLastUsed(new \DateTime('-1 month'));
        $userSkill->setNotes('Excellent PHP developer');

        $this->assertEquals($user, $userSkill->getUser());
        $this->assertEquals($skill, $userSkill->getSkill());
        $this->assertEquals(4, $userSkill->getLevel());
        $this->assertTrue($userSkill->isCertified());
        $this->assertTrue($userSkill->isAdvanced());
        $this->assertFalse($userSkill->isBeginner());

        // Test UserProjectRole
        $userProjectRole = new UserProjectRole();
        $userProjectRole->setUser($user);
        $userProjectRole->setProject($project);
        $userProjectRole->setRole('developer');
        $userProjectRole->setStartDate(new \DateTime('-1 month'));
        $userProjectRole->setEndDate(new \DateTime('+2 months'));
        $userProjectRole->setIsActive(true);
        $userProjectRole->setPermissions(['read', 'write']);

        $this->assertEquals($user, $userProjectRole->getUser());
        $this->assertEquals($project, $userProjectRole->getProject());
        $this->assertEquals('developer', $userProjectRole->getRole());
        $this->assertTrue($userProjectRole->isActive());
        $this->assertTrue($userProjectRole->isCurrent());
        $this->assertTrue($userProjectRole->hasPermission('read'));

        // Test Notification
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setTitle('Test Notification');
        $notification->setMessage('This is a test notification');
        $notification->setType('info');
        $notification->setRead(false);
        $notification->setPriority('normal');
        $notification->setChannel('email');
        $notification->setMetadata(['source' => 'system']);

        $this->assertEquals($user, $notification->getUser());
        $this->assertEquals('Test Notification', $notification->getTitle());
        $this->assertEquals('info', $notification->getType());
        $this->assertFalse($notification->isRead());
        $this->assertEquals('normal', $notification->getPriority());

        // Test Workload
        $workload = new Workload();
        $workload->setUser($user);
        $workload->setProject($project);
        $workload->setHours(40);
        $workload->setWeek('2023-W15');
        $workload->setYear(2023);
        $workload->setWeekNumber(15);
        $workload->setStatus('confirmed');
        $workload->setNotes('Regular work week');

        $this->assertEquals($user, $workload->getUser());
        $this->assertEquals($project, $workload->getProject());
        $this->assertEquals(40, $workload->getHours());
        $this->assertEquals('2023-W15', $workload->getWeek());
        $this->assertFalse($workload->isOverload());
        $this->assertEquals(100.0, $workload->getUtilizationPercentage());

        // Test Subscription
        $subscription = new Subscription();
        $subscription->setUser($user);
        $subscription->setStripeCustomerId('cus_test123');
        $subscription->setStripeSubscriptionId('sub_test123');
        $subscription->setStatus('active');
        $subscription->setPlan('premium');
        $subscription->setCurrentPeriodStart(new \DateTime());
        $subscription->setCurrentPeriodEnd(new \DateTime('+1 month'));
        $subscription->setTrialEnd(new \DateTime('+7 days'));

        $this->assertEquals($user, $subscription->getUser());
        $this->assertEquals('active', $subscription->getStatus());
        $this->assertEquals('premium', $subscription->getPlan());
        $this->assertTrue($subscription->isActive());
        $this->assertFalse($subscription->isCancelled());
        $this->assertTrue($subscription->isInTrial());
    }

    public function testEntityRelationships(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $project = new Project();
        $project->setName('Test Project');
        $project->setManager($user);

        $task = new Task();
        $task->setTitle('Test Task');
        $task->setProject($project);
        $task->setAssignee($user);

        // Test relationships
        $this->assertEquals($user, $project->getManager());
        $this->assertEquals($project, $task->getProject());
        $this->assertEquals($user, $task->getAssignee());
    }

    public function testEntityStates(): void
    {
        // Test Task states
        $task = new Task();
        $task->setStatus('todo');
        $this->assertFalse($task->isCompleted());
        $this->assertFalse($task->isInProgress());

        $task->setStatus('in_progress');
        $this->assertTrue($task->isInProgress());

        $task->setStatus('done');
        $this->assertTrue($task->isCompleted());

        // Test Project states
        $project = new Project();
        $project->setStatus('active');
        $this->assertTrue($project->isActive());

        $project->setStatus('completed');
        $this->assertFalse($project->isActive());
        $this->assertTrue($project->isCompleted());

        // Test User states
        $user = new User();
        $user->setIsActive(true);
        $this->assertTrue($user->isActive());

        $user->setIsActive(false);
        $this->assertFalse($user->isActive());
    }

    public function testEntityValidation(): void
    {
        // Test User validation
        $user = new User();
        $user->setEmail('invalid-email');
        // In a real application, this would be validated
        $this->assertEquals('invalid-email', $user->getEmail());

        // Test valid email
        $user->setEmail('valid@example.com');
        $this->assertEquals('valid@example.com', $user->getEmail());

        // Test password requirements
        $user->setPassword('short');
        $this->assertEquals('short', $user->getPassword());

        // Test role validation
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $this->assertContains('ROLE_USER', $user->getRoles());
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testEntityDefaults(): void
    {
        // Test User defaults
        $user = new User();
        $this->assertTrue($user->isActive()); // Should default to active
        $this->assertContains('ROLE_USER', $user->getRoles());

        // Test Task defaults
        $task = new Task();
        $this->assertEquals('todo', $task->getStatus());
        $this->assertEquals('medium', $task->getPriority());

        // Test Project defaults
        $project = new Project();
        $this->assertEquals('planning', $project->getStatus());

        // Test UserSkill defaults
        $userSkill = new UserSkill();
        $this->assertEquals(1, $userSkill->getLevel());
        $this->assertFalse($userSkill->isCertified());

        // Test Notification defaults
        $notification = new Notification();
        $this->assertEquals('info', $notification->getType());
        $this->assertFalse($notification->isRead());

        // Test Workload defaults
        $workload = new Workload();
        $this->assertEquals(0, $workload->getHours());
        $this->assertEquals('planned', $workload->getStatus());

        // Test Subscription defaults
        $subscription = new Subscription();
        $this->assertEquals('inactive', $subscription->getStatus());
    }

    public function testEntityMethods(): void
    {
        // Test User methods
        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $this->assertEquals('John Doe', $user->getFullName());

        $user->setLastLoginAt(new \DateTime());
        $this->assertInstanceOf(\DateTime::class, $user->getLastLoginAt());

        // Test Task methods
        $task = new Task();
        $task->setDueDate(new \DateTime('-1 day'));
        $this->assertTrue($task->isOverdue());

        $task->setDueDate(new \DateTime('+1 day'));
        $this->assertFalse($task->isOverdue());

        $task->setEstimatedHours(8);
        $task->setActualHours(10);
        $this->assertEquals(125, $task->getProgressPercentage());

        // Test Project methods
        $project = new Project();
        $project->setStartDate(new \DateTime('-1 month'));
        $project->setEndDate(new \DateTime('+2 months'));
        $this->assertTrue($project->isInProgress());

        // Test UserSkill methods
        $userSkill = new UserSkill();
        $userSkill->setLevel(5);
        $this->assertTrue($userSkill->isExpert());
        $this->assertEquals('Expert', $userSkill->getLevelName());

        $userSkill->setExperience(24);
        $this->assertEquals(2.0, $userSkill->getExperienceInYears());

        // Test Notification methods
        $notification = new Notification();
        $notification->setRead(false);
        $notification->markAsRead();
        $this->assertTrue($notification->isRead());

        $notification->markAsUnread();
        $this->assertFalse($notification->isRead());

        // Test Workload methods
        $workload = new Workload();
        $workload->setHours(50);
        $this->assertTrue($workload->isOverload());
        $this->assertEquals(125.0, $workload->getUtilizationPercentage());

        $workload->setHours(30);
        $this->assertTrue($workload->isUnderload());

        // Test Subscription methods
        $subscription = new Subscription();
        $subscription->setStatus('active');
        $this->assertTrue($subscription->isActive());

        $subscription->cancel();
        $this->assertTrue($subscription->isCancelled());
        $this->assertInstanceOf(\DateTime::class, $subscription->getCancelledAt());
    }
}





