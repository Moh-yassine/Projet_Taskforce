<?php

namespace App\Tests\Repository;

use App\Entity\Task;
use App\Entity\User;
use App\Entity\Project;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TaskRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private TaskRepository $taskRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->taskRepository = $this->entityManager->getRepository(Task::class);
    }

    public function testFindByProject(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject();
        $task1 = $this->createTestTask($user, $project, 'Task 1');
        $task2 = $this->createTestTask($user, $project, 'Task 2');
        $task3 = $this->createTestTask($user, $this->createTestProject(), 'Task 3'); // Different project

        $tasks = $this->taskRepository->findByProject($project->getId());

        $this->assertCount(2, $tasks);
        $this->assertContains($task1, $tasks);
        $this->assertContains($task2, $tasks);
        $this->assertNotContains($task3, $tasks);
    }

    public function testFindByUser(): void
    {
        $user1 = $this->createTestUser('user1@example.com');
        $user2 = $this->createTestUser('user2@example.com');
        $project = $this->createTestProject();
        
        $task1 = $this->createTestTask($user1, $project, 'Task 1');
        $task2 = $this->createTestTask($user1, $project, 'Task 2');
        $task3 = $this->createTestTask($user2, $project, 'Task 3');

        $user1Tasks = $this->taskRepository->findBy(['assignee' => $user1]);
        $user2Tasks = $this->taskRepository->findBy(['assignee' => $user2]);

        $this->assertCount(2, $user1Tasks);
        $this->assertCount(1, $user2Tasks);
        $this->assertContains($task1, $user1Tasks);
        $this->assertContains($task2, $user1Tasks);
        $this->assertContains($task3, $user2Tasks);
    }

    public function testFindByStatus(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject();
        
        $task1 = $this->createTestTask($user, $project, 'Task 1', 'todo');
        $task2 = $this->createTestTask($user, $project, 'Task 2', 'in_progress');
        $task3 = $this->createTestTask($user, $project, 'Task 3', 'todo');

        $todoTasks = $this->taskRepository->findBy(['status' => 'todo']);
        $inProgressTasks = $this->taskRepository->findBy(['status' => 'in_progress']);

        $this->assertCount(2, $todoTasks);
        $this->assertCount(1, $inProgressTasks);
        $this->assertContains($task1, $todoTasks);
        $this->assertContains($task3, $todoTasks);
        $this->assertContains($task2, $inProgressTasks);
    }

    public function testFindByPriority(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject();
        
        $task1 = $this->createTestTask($user, $project, 'High Priority Task', 'todo', 'high');
        $task2 = $this->createTestTask($user, $project, 'Medium Priority Task', 'todo', 'medium');
        $task3 = $this->createTestTask($user, $project, 'Low Priority Task', 'todo', 'low');

        $highPriorityTasks = $this->taskRepository->findBy(['priority' => 'high']);
        $mediumPriorityTasks = $this->taskRepository->findBy(['priority' => 'medium']);
        $lowPriorityTasks = $this->taskRepository->findBy(['priority' => 'low']);

        $this->assertCount(1, $highPriorityTasks);
        $this->assertCount(1, $mediumPriorityTasks);
        $this->assertCount(1, $lowPriorityTasks);
        $this->assertContains($task1, $highPriorityTasks);
        $this->assertContains($task2, $mediumPriorityTasks);
        $this->assertContains($task3, $lowPriorityTasks);
    }

    public function testFindOverdueTasks(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject();
        
        $overdueTask = $this->createTestTask($user, $project, 'Overdue Task');
        $overdueTask->setDueDate(new \DateTime('-5 days'));
        $overdueTask->setStatus('in_progress');
        
        $futureTask = $this->createTestTask($user, $project, 'Future Task');
        $futureTask->setDueDate(new \DateTime('+5 days'));
        $futureTask->setStatus('in_progress');
        
        $completedTask = $this->createTestTask($user, $project, 'Completed Task');
        $completedTask->setDueDate(new \DateTime('-5 days'));
        $completedTask->setStatus('done');

        $this->entityManager->flush();

        $qb = $this->taskRepository->createQueryBuilder('t')
            ->where('t.dueDate < :now')
            ->andWhere('t.status != :doneStatus')
            ->setParameter('now', new \DateTime())
            ->setParameter('doneStatus', 'done');

        $overdueTasks = $qb->getQuery()->getResult();

        $this->assertCount(1, $overdueTasks);
        $this->assertContains($overdueTask, $overdueTasks);
        $this->assertNotContains($futureTask, $overdueTasks);
        $this->assertNotContains($completedTask, $overdueTasks);
    }

    public function testFindTasksWithEstimatedHours(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject();
        
        $taskWithEstimate = $this->createTestTask($user, $project, 'Task with estimate');
        $taskWithEstimate->setEstimatedHours(8);
        
        $taskWithoutEstimate = $this->createTestTask($user, $project, 'Task without estimate');

        $this->entityManager->flush();

        $tasksWithEstimate = $this->taskRepository->findBy(['estimatedHours' => 8]);
        $tasksWithoutEstimate = $this->taskRepository
            ->createQueryBuilder('t')
            ->where('t.estimatedHours IS NULL')
            ->getQuery()
            ->getResult();

        $this->assertContains($taskWithEstimate, $tasksWithEstimate);
        $this->assertContains($taskWithoutEstimate, $tasksWithoutEstimate);
        $this->assertNotContains($taskWithoutEstimate, $tasksWithEstimate);
    }

    public function testFindTasksByDateRange(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject();
        
        $oldTask = $this->createTestTask($user, $project, 'Old Task');
        $oldTask->setCreatedAt(new \DateTimeImmutable('-30 days'));
        
        $recentTask = $this->createTestTask($user, $project, 'Recent Task');
        $recentTask->setCreatedAt(new \DateTimeImmutable('-5 days'));

        $this->entityManager->flush();

        $recentTasks = $this->taskRepository
            ->createQueryBuilder('t')
            ->where('t.createdAt >= :startDate')
            ->setParameter('startDate', new \DateTime('-10 days'))
            ->getQuery()
            ->getResult();

        $this->assertContains($recentTask, $recentTasks);
        $this->assertNotContains($oldTask, $recentTasks);
    }

    public function testCountTasksByStatus(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject();
        
        $this->createTestTask($user, $project, 'Task 1', 'todo');
        $this->createTestTask($user, $project, 'Task 2', 'todo');
        $this->createTestTask($user, $project, 'Task 3', 'in_progress');
        $this->createTestTask($user, $project, 'Task 4', 'done');

        $todoCount = $this->taskRepository->count(['status' => 'todo']);
        $inProgressCount = $this->taskRepository->count(['status' => 'in_progress']);
        $doneCount = $this->taskRepository->count(['status' => 'done']);

        $this->assertEquals(2, $todoCount);
        $this->assertEquals(1, $inProgressCount);
        $this->assertEquals(1, $doneCount);
    }

    private function createTestUser(string $email = 'test@example.com'): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('hashedpassword');

        $this->entityManager->persist($user);
        return $user;
    }

    private function createTestProject(): Project
    {
        $project = new Project();
        $project->setName('Test Project ' . uniqid());
        $project->setDescription('Test Description');
        $project->setStatus('active');

        $this->entityManager->persist($project);
        return $project;
    }

    private function createTestTask(User $user, Project $project, string $title, string $status = 'todo', string $priority = 'medium'): Task
    {
        $task = new Task();
        $task->setTitle($title);
        $task->setDescription('Test Description');
        $task->setStatus($status);
        $task->setPriority($priority);
        $task->setProject($project);
        $task->setAssignee($user);

        $this->entityManager->persist($task);
        return $task;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
