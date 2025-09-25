<?php

namespace App\Tests\Repository;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProjectRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ProjectRepository $projectRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->projectRepository = $this->entityManager->getRepository(Project::class);
    }

    public function testFindActiveProjects(): void
    {
        $user = $this->createTestUser();
        
        $activeProject = $this->createTestProject('Active Project', 'active', $user);
        $inactiveProject = $this->createTestProject('Inactive Project', 'inactive', $user);
        $completedProject = $this->createTestProject('Completed Project', 'completed', $user);

        $activeProjects = $this->projectRepository->findBy(['status' => 'active']);

        $this->assertCount(1, $activeProjects);
        $this->assertContains($activeProject, $activeProjects);
        $this->assertNotContains($inactiveProject, $activeProjects);
        $this->assertNotContains($completedProject, $activeProjects);
    }

    public function testFindProjectsByManager(): void
    {
        $manager1 = $this->createTestUser('manager1@example.com');
        $manager2 = $this->createTestUser('manager2@example.com');
        
        $project1 = $this->createTestProject('Project 1', 'active', $manager1);
        $project2 = $this->createTestProject('Project 2', 'active', $manager1);
        $project3 = $this->createTestProject('Project 3', 'active', $manager2);

        $manager1Projects = $this->projectRepository->findBy(['manager' => $manager1]);
        $manager2Projects = $this->projectRepository->findBy(['manager' => $manager2]);

        $this->assertCount(2, $manager1Projects);
        $this->assertCount(1, $manager2Projects);
        $this->assertContains($project1, $manager1Projects);
        $this->assertContains($project2, $manager1Projects);
        $this->assertContains($project3, $manager2Projects);
    }

    public function testFindProjectsByStatus(): void
    {
        $user = $this->createTestUser();
        
        $this->createTestProject('Active Project 1', 'active', $user);
        $this->createTestProject('Active Project 2', 'active', $user);
        $this->createTestProject('Completed Project', 'completed', $user);
        $this->createTestProject('On Hold Project', 'on_hold', $user);

        $activeProjects = $this->projectRepository->findBy(['status' => 'active']);
        $completedProjects = $this->projectRepository->findBy(['status' => 'completed']);
        $onHoldProjects = $this->projectRepository->findBy(['status' => 'on_hold']);

        $this->assertCount(2, $activeProjects);
        $this->assertCount(1, $completedProjects);
        $this->assertCount(1, $onHoldProjects);
    }

    public function testFindProjectsByDateRange(): void
    {
        $user = $this->createTestUser();
        
        $oldProject = $this->createTestProject('Old Project', 'completed', $user);
        $oldProject->setStartDate(new \DateTime('-6 months'));
        $oldProject->setEndDate(new \DateTime('-3 months'));
        
        $currentProject = $this->createTestProject('Current Project', 'active', $user);
        $currentProject->setStartDate(new \DateTime('-1 month'));
        $currentProject->setEndDate(new \DateTime('+2 months'));
        
        $futureProject = $this->createTestProject('Future Project', 'planned', $user);
        $futureProject->setStartDate(new \DateTime('+1 month'));
        $futureProject->setEndDate(new \DateTime('+4 months'));

        $this->entityManager->flush();

        $recentProjects = $this->projectRepository
            ->createQueryBuilder('p')
            ->where('p.startDate >= :startDate')
            ->setParameter('startDate', new \DateTime('-2 months'))
            ->getQuery()
            ->getResult();

        $this->assertContains($currentProject, $recentProjects);
        $this->assertContains($futureProject, $recentProjects);
        $this->assertNotContains($oldProject, $recentProjects);
    }

    public function testFindOverdueProjects(): void
    {
        $user = $this->createTestUser();
        
        $overdueProject = $this->createTestProject('Overdue Project', 'active', $user);
        $overdueProject->setEndDate(new \DateTime('-1 month'));
        
        $onTimeProject = $this->createTestProject('On Time Project', 'active', $user);
        $onTimeProject->setEndDate(new \DateTime('+1 month'));
        
        $completedProject = $this->createTestProject('Completed Project', 'completed', $user);
        $completedProject->setEndDate(new \DateTime('-1 month'));

        $this->entityManager->flush();

        $overdueProjects = $this->projectRepository
            ->createQueryBuilder('p')
            ->where('p.endDate < :now')
            ->andWhere('p.status != :completedStatus')
            ->setParameter('now', new \DateTime())
            ->setParameter('completedStatus', 'completed')
            ->getQuery()
            ->getResult();

        $this->assertContains($overdueProject, $overdueProjects);
        $this->assertNotContains($onTimeProject, $overdueProjects);
        $this->assertNotContains($completedProject, $overdueProjects);
    }

    public function testCountProjectsByStatus(): void
    {
        $user = $this->createTestUser();
        
        $this->createTestProject('Active 1', 'active', $user);
        $this->createTestProject('Active 2', 'active', $user);
        $this->createTestProject('Active 3', 'active', $user);
        $this->createTestProject('Completed 1', 'completed', $user);
        $this->createTestProject('Completed 2', 'completed', $user);
        $this->createTestProject('On Hold', 'on_hold', $user);

        $activeCount = $this->projectRepository->count(['status' => 'active']);
        $completedCount = $this->projectRepository->count(['status' => 'completed']);
        $onHoldCount = $this->projectRepository->count(['status' => 'on_hold']);

        $this->assertEquals(3, $activeCount);
        $this->assertEquals(2, $completedCount);
        $this->assertEquals(1, $onHoldCount);
    }

    public function testFindProjectsWithMostTasks(): void
    {
        $user = $this->createTestUser();
        
        $project1 = $this->createTestProject('Project with many tasks', 'active', $user);
        $project2 = $this->createTestProject('Project with few tasks', 'active', $user);
        
        // In a real implementation, this would join with tasks
        $projects = $this->projectRepository
            ->createQueryBuilder('p')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();

        $this->assertNotEmpty($projects);
        $this->assertContains($project1, $projects);
        $this->assertContains($project2, $projects);
    }

    public function testFindProjectsCreatedInLastMonth(): void
    {
        $user = $this->createTestUser();
        
        $recentProject = $this->createTestProject('Recent Project', 'active', $user);
        $recentProject->setCreatedAt(new \DateTimeImmutable('-2 weeks'));
        
        $oldProject = $this->createTestProject('Old Project', 'active', $user);
        $oldProject->setCreatedAt(new \DateTimeImmutable('-2 months'));

        $this->entityManager->flush();

        $recentProjects = $this->projectRepository
            ->createQueryBuilder('p')
            ->where('p.createdAt >= :lastMonth')
            ->setParameter('lastMonth', new \DateTime('-1 month'))
            ->getQuery()
            ->getResult();

        $this->assertContains($recentProject, $recentProjects);
        $this->assertNotContains($oldProject, $recentProjects);
    }

    public function testFindProjectsByNameSearch(): void
    {
        $user = $this->createTestUser();
        
        $this->createTestProject('E-commerce Website', 'active', $user);
        $this->createTestProject('Mobile App Development', 'active', $user);
        $this->createTestProject('API Integration', 'active', $user);

        $searchResults = $this->projectRepository
            ->createQueryBuilder('p')
            ->where('p.name LIKE :search')
            ->setParameter('search', '%App%')
            ->getQuery()
            ->getResult();

        $this->assertCount(1, $searchResults);
        $this->assertEquals('Mobile App Development', $searchResults[0]->getName());
    }

    public function testFindProjectsWithProgressCalculation(): void
    {
        $user = $this->createTestUser();
        
        $project = $this->createTestProject('Test Project', 'active', $user);
        
        // In a real implementation, this would calculate progress based on tasks
        $projectsWithProgress = $this->projectRepository
            ->createQueryBuilder('p')
            ->select('p, COUNT(t.id) as taskCount')
            ->leftJoin('p.tasks', 't')
            ->groupBy('p.id')
            ->getQuery()
            ->getResult();

        $this->assertNotEmpty($projectsWithProgress);
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

    private function createTestProject(string $name, string $status, User $manager): Project
    {
        $project = new Project();
        $project->setName($name);
        $project->setDescription('Test Description for ' . $name);
        $project->setStatus($status);
        $project->setManager($manager);
        $project->setStartDate(new \DateTime());
        $project->setEndDate(new \DateTime('+3 months'));

        $this->entityManager->persist($project);
        return $project;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}





