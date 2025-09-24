<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->userRepository = $this->entityManager->getRepository(User::class);
    }

    public function testFindUserByEmail(): void
    {
        $user = $this->createTestUser('findme@example.com');
        
        $foundUser = $this->userRepository->findOneBy(['email' => 'findme@example.com']);
        
        $this->assertNotNull($foundUser);
        $this->assertEquals('findme@example.com', $foundUser->getEmail());
        $this->assertEquals($user->getId(), $foundUser->getId());
    }

    public function testFindUsersByRole(): void
    {
        $admin = $this->createTestUser('admin@example.com', ['ROLE_ADMIN']);
        $manager = $this->createTestUser('manager@example.com', ['ROLE_PROJECT_MANAGER']);
        $user1 = $this->createTestUser('user1@example.com', ['ROLE_USER']);
        $user2 = $this->createTestUser('user2@example.com', ['ROLE_USER']);

        $admins = $this->userRepository
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getResult();

        $managers = $this->userRepository
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_PROJECT_MANAGER%')
            ->getQuery()
            ->getResult();

        $this->assertCount(1, $admins);
        $this->assertCount(1, $managers);
        $this->assertContains($admin, $admins);
        $this->assertContains($manager, $managers);
    }

    public function testFindActiveUsers(): void
    {
        $activeUser = $this->createTestUser('active@example.com');
        $activeUser->setIsActive(true);
        
        $inactiveUser = $this->createTestUser('inactive@example.com');
        $inactiveUser->setIsActive(false);

        $this->entityManager->flush();

        $activeUsers = $this->userRepository->findBy(['isActive' => true]);
        $inactiveUsers = $this->userRepository->findBy(['isActive' => false]);

        $this->assertContains($activeUser, $activeUsers);
        $this->assertContains($inactiveUser, $inactiveUsers);
        $this->assertNotContains($inactiveUser, $activeUsers);
        $this->assertNotContains($activeUser, $inactiveUsers);
    }

    public function testFindUsersByCompany(): void
    {
        $user1 = $this->createTestUser('user1@company1.com');
        $user1->setCompany('Company A');
        
        $user2 = $this->createTestUser('user2@company1.com');
        $user2->setCompany('Company A');
        
        $user3 = $this->createTestUser('user3@company2.com');
        $user3->setCompany('Company B');

        $this->entityManager->flush();

        $companyAUsers = $this->userRepository->findBy(['company' => 'Company A']);
        $companyBUsers = $this->userRepository->findBy(['company' => 'Company B']);

        $this->assertCount(2, $companyAUsers);
        $this->assertCount(1, $companyBUsers);
        $this->assertContains($user1, $companyAUsers);
        $this->assertContains($user2, $companyAUsers);
        $this->assertContains($user3, $companyBUsers);
    }

    public function testFindRecentlyRegisteredUsers(): void
    {
        $recentUser = $this->createTestUser('recent@example.com');
        $recentUser->setCreatedAt(new \DateTimeImmutable('-1 week'));
        
        $oldUser = $this->createTestUser('old@example.com');
        $oldUser->setCreatedAt(new \DateTimeImmutable('-2 months'));

        $this->entityManager->flush();

        $recentUsers = $this->userRepository
            ->createQueryBuilder('u')
            ->where('u.createdAt >= :lastMonth')
            ->setParameter('lastMonth', new \DateTime('-1 month'))
            ->getQuery()
            ->getResult();

        $this->assertContains($recentUser, $recentUsers);
        $this->assertNotContains($oldUser, $recentUsers);
    }

    public function testFindUsersByNameSearch(): void
    {
        $this->createTestUser('john.doe@example.com', ['ROLE_USER'], 'John', 'Doe');
        $this->createTestUser('jane.smith@example.com', ['ROLE_USER'], 'Jane', 'Smith');
        $this->createTestUser('bob.johnson@example.com', ['ROLE_USER'], 'Bob', 'Johnson');

        $johnResults = $this->userRepository
            ->createQueryBuilder('u')
            ->where('u.firstName LIKE :search OR u.lastName LIKE :search')
            ->setParameter('search', '%John%')
            ->getQuery()
            ->getResult();

        $smithResults = $this->userRepository
            ->createQueryBuilder('u')
            ->where('u.firstName LIKE :search OR u.lastName LIKE :search')
            ->setParameter('search', '%Smith%')
            ->getQuery()
            ->getResult();

        $this->assertCount(2, $johnResults); // John Doe and Bob Johnson
        $this->assertCount(1, $smithResults); // Jane Smith
    }

    public function testFindUsersWithRecentLogin(): void
    {
        $recentUser = $this->createTestUser('recent@example.com');
        $recentUser->setLastLoginAt(new \DateTime('-1 day'));
        
        $oldUser = $this->createTestUser('old@example.com');
        $oldUser->setLastLoginAt(new \DateTime('-1 month'));

        $this->entityManager->flush();

        $recentlyActiveUsers = $this->userRepository
            ->createQueryBuilder('u')
            ->where('u.lastLoginAt >= :lastWeek')
            ->setParameter('lastWeek', new \DateTime('-1 week'))
            ->getQuery()
            ->getResult();

        $this->assertContains($recentUser, $recentlyActiveUsers);
        $this->assertNotContains($oldUser, $recentlyActiveUsers);
    }

    public function testCountUsersByRole(): void
    {
        $this->createTestUser('admin1@example.com', ['ROLE_ADMIN']);
        $this->createTestUser('admin2@example.com', ['ROLE_ADMIN']);
        $this->createTestUser('manager1@example.com', ['ROLE_PROJECT_MANAGER']);
        $this->createTestUser('user1@example.com', ['ROLE_USER']);
        $this->createTestUser('user2@example.com', ['ROLE_USER']);
        $this->createTestUser('user3@example.com', ['ROLE_USER']);

        $adminCount = $this->userRepository
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->getQuery()
            ->getSingleScalarResult();

        $userCount = $this->userRepository
            ->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_USER%')
            ->getQuery()
            ->getSingleScalarResult();

        $this->assertEquals(2, $adminCount);
        $this->assertEquals(6, $userCount); // All users have ROLE_USER
    }

    public function testFindUsersByEmailDomain(): void
    {
        $this->createTestUser('user1@company.com');
        $this->createTestUser('user2@company.com');
        $this->createTestUser('user3@gmail.com');
        $this->createTestUser('user4@yahoo.com');

        $companyUsers = $this->userRepository
            ->createQueryBuilder('u')
            ->where('u.email LIKE :domain')
            ->setParameter('domain', '%@company.com')
            ->getQuery()
            ->getResult();

        $gmailUsers = $this->userRepository
            ->createQueryBuilder('u')
            ->where('u.email LIKE :domain')
            ->setParameter('domain', '%@gmail.com')
            ->getQuery()
            ->getResult();

        $this->assertCount(2, $companyUsers);
        $this->assertCount(1, $gmailUsers);
    }

    public function testFindUsersWithoutLastLogin(): void
    {
        $userWithLogin = $this->createTestUser('withlogin@example.com');
        $userWithLogin->setLastLoginAt(new \DateTime('-1 day'));
        
        $userWithoutLogin = $this->createTestUser('withoutlogin@example.com');
        // lastLoginAt is null by default

        $this->entityManager->flush();

        $usersWithoutLogin = $this->userRepository
            ->createQueryBuilder('u')
            ->where('u.lastLoginAt IS NULL')
            ->getQuery()
            ->getResult();

        $this->assertContains($userWithoutLogin, $usersWithoutLogin);
        $this->assertNotContains($userWithLogin, $usersWithoutLogin);
    }

    public function testFindUsersOrderedByRegistrationDate(): void
    {
        $user1 = $this->createTestUser('first@example.com');
        $user1->setCreatedAt(new \DateTimeImmutable('-3 days'));
        
        $user2 = $this->createTestUser('second@example.com');
        $user2->setCreatedAt(new \DateTimeImmutable('-2 days'));
        
        $user3 = $this->createTestUser('third@example.com');
        $user3->setCreatedAt(new \DateTimeImmutable('-1 day'));

        $this->entityManager->flush();

        $orderedUsers = $this->userRepository
            ->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        $this->assertGreaterThanOrEqual(3, count($orderedUsers));
        
        // Find our test users in the results
        $testUsers = array_filter($orderedUsers, function($user) {
            return in_array($user->getEmail(), ['first@example.com', 'second@example.com', 'third@example.com']);
        });
        
        $testUsers = array_values($testUsers);
        $this->assertEquals('first@example.com', $testUsers[0]->getEmail());
        $this->assertEquals('second@example.com', $testUsers[1]->getEmail());
        $this->assertEquals('third@example.com', $testUsers[2]->getEmail());
    }

    public function testFindAssignableUsers(): void
    {
        $activeUser = $this->createTestUser('active@example.com', ['ROLE_USER']);
        $activeUser->setIsActive(true);
        
        $inactiveUser = $this->createTestUser('inactive@example.com', ['ROLE_USER']);
        $inactiveUser->setIsActive(false);
        
        $adminUser = $this->createTestUser('admin@example.com', ['ROLE_ADMIN']);
        $adminUser->setIsActive(true);

        $this->entityManager->flush();

        $assignableUsers = $this->userRepository
            ->createQueryBuilder('u')
            ->where('u.isActive = :active')
            ->andWhere('u.roles LIKE :userRole OR u.roles LIKE :managerRole')
            ->setParameter('active', true)
            ->setParameter('userRole', '%ROLE_USER%')
            ->setParameter('managerRole', '%ROLE_PROJECT_MANAGER%')
            ->getQuery()
            ->getResult();

        $this->assertContains($activeUser, $assignableUsers);
        $this->assertContains($adminUser, $assignableUsers); // Admin also has ROLE_USER
        $this->assertNotContains($inactiveUser, $assignableUsers);
    }

    private function createTestUser(
        string $email = 'test@example.com', 
        array $roles = ['ROLE_USER'],
        string $firstName = 'Test',
        string $lastName = 'User'
    ): User {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles($roles);
        $user->setPassword('hashedpassword');
        $user->setIsActive(true); // Default to active

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}


