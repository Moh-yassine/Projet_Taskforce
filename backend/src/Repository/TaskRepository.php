<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function findByProject(int $projectId): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.project', 'p')
            ->leftJoin('t.assignee', 'u')
            ->addSelect('p', 'u')
            ->where('p.id = :projectId')
            ->setParameter('projectId', $projectId)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOverdueTasksByUser($user): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('t')
            ->where('t.assignee = :user')
            ->andWhere('t.dueDate < :now')
            ->andWhere('t.status != :completed')
            ->setParameter('user', $user)
            ->setParameter('now', $now)
            ->setParameter('completed', 'completed')
            ->getQuery()
            ->getResult();
    }

    public function findTasksByUser($user): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.project', 'p')
            ->addSelect('p')
            ->where('t.assignee = :user')
            ->setParameter('user', $user)
            ->orderBy('t.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
