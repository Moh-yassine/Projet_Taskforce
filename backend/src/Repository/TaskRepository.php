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
}
