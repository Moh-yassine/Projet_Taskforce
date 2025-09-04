<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function save(Project $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Project $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByProjectManager(User $projectManager): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.projectManager = :projectManager')
            ->setParameter('projectManager', $projectManager)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByTeamMember(User $teamMember): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.teamMembers', 'tm')
            ->andWhere('tm = :teamMember')
            ->setParameter('teamMember', $teamMember)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', $status)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOverdueProjects(): array
    {
        $now = new \DateTimeImmutable();
        
        return $this->createQueryBuilder('p')
            ->andWhere('p.endDate < :now')
            ->andWhere('p.status != :completed')
            ->setParameter('now', $now)
            ->setParameter('completed', 'completed')
            ->orderBy('p.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findProjectsDueSoon(int $days = 7): array
    {
        $now = new \DateTimeImmutable();
        $deadline = $now->add(new \DateInterval("P{$days}D"));
        
        return $this->createQueryBuilder('p')
            ->andWhere('p.endDate BETWEEN :now AND :deadline')
            ->andWhere('p.status != :completed')
            ->setParameter('now', $now)
            ->setParameter('deadline', $deadline)
            ->setParameter('completed', 'completed')
            ->orderBy('p.endDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
