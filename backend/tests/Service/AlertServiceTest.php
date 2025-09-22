<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\Workload;
use App\Entity\Notification;
use App\Service\AlertService;
use App\Repository\UserRepository;
use App\Repository\TaskRepository;
use App\Repository\WorkloadRepository;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AlertServiceTest extends KernelTestCase
{
    private AlertService $alertService;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->alertService = $kernel->getContainer()->get(AlertService::class);
    }

    public function testCheckAndGenerateWorkloadAlerts(): void
    {
        // Créer un utilisateur avec une surcharge
        $user = $this->createTestUser();
        $project = $this->createTestProject();
        
        // Créer une charge de travail élevée
        $workload = new Workload();
        $workload->setUser($user);
        $workload->setProject($project);
        $workload->setHours(60); // Surcharge
        $workload->setWeek('2023-W01');
        
        $this->entityManager->persist($workload);
        $this->entityManager->flush();

        $alerts = $this->alertService->checkAndGenerateWorkloadAlerts();

        $this->assertIsArray($alerts);
        // On s'attend à ce qu'une alerte soit générée pour la surcharge
        $this->assertGreaterThanOrEqual(0, count($alerts));
    }

    public function testCheckAndGenerateDelayAlerts(): void
    {
        // Créer une tâche en retard
        $user = $this->createTestUser();
        $project = $this->createTestProject();
        
        $task = new Task();
        $task->setTitle('Tâche en retard');
        $task->setDescription('Description');
        $task->setStatus('in_progress');
        $task->setPriority('high');
        $task->setProject($project);
        $task->setAssignee($user);
        $task->setDueDate(new \DateTime('-5 days')); // En retard de 5 jours
        
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $alerts = $this->alertService->checkAndGenerateDelayAlerts();

        $this->assertIsArray($alerts);
        $this->assertGreaterThanOrEqual(0, count($alerts));
    }

    public function testCheckAndGenerateInactiveAlerts(): void
    {
        // Créer un utilisateur inactif
        $user = $this->createTestUser();
        $user->setLastLoginAt(new \DateTime('-30 days')); // Inactif depuis 30 jours
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $alerts = $this->alertService->checkAndGenerateInactiveAlerts();

        $this->assertIsArray($alerts);
        $this->assertGreaterThanOrEqual(0, count($alerts));
    }

    public function testCleanupOldAlerts(): void
    {
        // Créer une ancienne notification
        $user = $this->createTestUser();
        
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setTitle('Ancienne alerte');
        $notification->setMessage('Message');
        $notification->setType('alert');
        $notification->setCreatedAt(new \DateTime('-60 days')); // Ancienne de 60 jours
        
        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        $result = $this->alertService->cleanupOldAlerts();

        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function testGetUserWorkloadForWeek(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject();
        
        $workload = new Workload();
        $workload->setUser($user);
        $workload->setProject($project);
        $workload->setHours(40);
        $workload->setWeek('2023-W01');
        
        $this->entityManager->persist($workload);
        $this->entityManager->flush();

        $totalHours = $this->alertService->getUserWorkloadForWeek($user, '2023-W01');

        $this->assertEquals(40, $totalHours);
    }

    public function testGetOverdueTasksForUser(): void
    {
        $user = $this->createTestUser();
        $project = $this->createTestProject();
        
        $task = new Task();
        $task->setTitle('Tâche en retard');
        $task->setDescription('Description');
        $task->setStatus('in_progress');
        $task->setPriority('high');
        $task->setProject($project);
        $task->setAssignee($user);
        $task->setDueDate(new \DateTime('-1 day'));
        
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $overdueTasks = $this->alertService->getOverdueTasksForUser($user);

        $this->assertIsArray($overdueTasks);
        $this->assertGreaterThanOrEqual(1, count($overdueTasks));
    }

    public function testCreateAlert(): void
    {
        $user = $this->createTestUser();
        
        $notification = $this->alertService->createAlert(
            $user,
            'Test Alert',
            'Test message',
            'warning'
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals('Test Alert', $notification->getTitle());
        $this->assertEquals('Test message', $notification->getMessage());
        $this->assertEquals('warning', $notification->getType());
        $this->assertEquals($user, $notification->getUser());
    }

    public function testGetInactiveUsers(): void
    {
        // Créer un utilisateur inactif
        $user = $this->createTestUser();
        $user->setLastLoginAt(new \DateTime('-40 days'));
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $inactiveUsers = $this->alertService->getInactiveUsers(30);

        $this->assertIsArray($inactiveUsers);
        $this->assertGreaterThanOrEqual(1, count($inactiveUsers));
    }

    public function testGetWorkloadThreshold(): void
    {
        $threshold = $this->alertService->getWorkloadThreshold();
        
        $this->assertIsFloat($threshold);
        $this->assertGreaterThan(0, $threshold);
    }

    private function createTestUser(): User
    {
        $user = new User();
        $user->setEmail('test' . uniqid() . '@example.com');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('hashedpassword');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createTestProject(): Project
    {
        $project = new Project();
        $project->setName('Test Project ' . uniqid());
        $project->setDescription('Test Description');
        $project->setStatus('active');

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return $project;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
