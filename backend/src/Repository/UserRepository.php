<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->save($user, true);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function emailExists(string $email): bool
    {
        return $this->count(['email' => $email]) > 0;
    }

    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('JSON_CONTAINS(u.roles, :role) = 1')
            ->setParameter('role', '"' . $role . '"')
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function searchUsers(string $searchTerm): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search')
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findUsersWithSkills(array $skillIds): array
    {
        if (empty($skillIds)) {
            return [];
        }

        $qb = $this->createQueryBuilder('u')
            ->join('u.skills', 's');

        foreach ($skillIds as $index => $skillId) {
            $qb->andWhere('s.id = :skill' . $index)
               ->setParameter('skill' . $index, $skillId);
        }

        return $qb->orderBy('u.lastName', 'ASC')
                 ->addOrderBy('u.firstName', 'ASC')
                 ->getQuery()
                 ->getResult();
    }

    public function findProjectManagers(): array
    {
        return $this->findByRole('ROLE_PROJECT_MANAGER');
    }

    public function findManagers(): array
    {
        return $this->findByRole('ROLE_MANAGER');
    }

    public function findCollaborators(): array
    {
        return $this->findByRole('ROLE_COLLABORATOR');
    }

    public function findUsersByCompany(string $company): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.company = :company')
            ->setParameter('company', $company)
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findUsersWithLowWorkload(int $maxHours = 20): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.assignedTasks', 't')
            ->andWhere('t.status != :completed OR t.id IS NULL')
            ->setParameter('completed', 'completed')
            ->groupBy('u.id')
            ->having('COALESCE(SUM(t.estimatedHours), 0) <= :maxHours')
            ->setParameter('maxHours', $maxHours)
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findUsersBySkillCategory(string $category): array
    {
        return $this->createQueryBuilder('u')
            ->join('u.skills', 's')
            ->andWhere('s.category = :category')
            ->setParameter('category', $category)
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findUsersWithMultipleSkills(int $minSkills = 3): array
    {
        return $this->createQueryBuilder('u')
            ->join('u.skills', 's')
            ->groupBy('u.id')
            ->having('COUNT(s.id) >= :minSkills')
            ->setParameter('minSkills', $minSkills)
            ->orderBy('COUNT(s.id)', 'DESC')
            ->addOrderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
