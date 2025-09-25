<?php

namespace App\Repository;

use App\Entity\Column;
use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Column>
 */
class ColumnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Column::class);
    }

    /**
     * @return Column[] Returns an array of Column objects
     */
    public function findByProjectOrderedByPosition(Project $project): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.tasks', 't')
            ->addSelect('t')
            ->andWhere('c.project = :project')
            ->setParameter('project', $project)
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('t.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findMaxPositionForProject(Project $project): ?int
    {
        $result = $this->createQueryBuilder('c')
            ->select('MAX(c.position)')
            ->andWhere('c.project = :project')
            ->setParameter('project', $project)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : 0;
    }

    public function createDefaultColumns(Project $project): void
    {
        $defaultColumns = [
            ['name' => 'À faire', 'color' => '#6B7280', 'position' => 1],
            ['name' => 'En cours', 'color' => '#3B82F6', 'position' => 2],
            ['name' => 'Terminé', 'color' => '#10B981', 'position' => 3],
        ];

        foreach ($defaultColumns as $columnData) {
            $column = new Column();
            $column->setName($columnData['name']);
            $column->setColor($columnData['color']);
            $column->setPosition($columnData['position']);
            $column->setProject($project);
            $column->setIsDefault(true);

            $this->getEntityManager()->persist($column);
        }

        $this->getEntityManager()->flush();
    }
}
