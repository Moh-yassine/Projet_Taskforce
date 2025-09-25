<?php

namespace App\Controller;

use App\Entity\Column;
use App\Entity\Project;
use App\Repository\ColumnRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/columns', name: 'columns_')]
#[IsGranted('ROLE_USER')]
class ColumnController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ColumnRepository $columnRepository,
        private ProjectRepository $projectRepository
    ) {}

    #[Route('/project/{projectId}', name: 'get_by_project', methods: ['GET'])]
    public function getColumnsByProject(int $projectId): JsonResponse
    {
        $project = $this->projectRepository->find($projectId);
        
        if (!$project) {
            return new JsonResponse(['error' => 'Projet non trouvé'], 404);
        }

        // Vérifier les permissions
        $user = $this->getUser();
        if (!$this->canAccessProject($project, $user)) {
            return new JsonResponse(['error' => 'Accès non autorisé'], 403);
        }

        $columns = $this->columnRepository->findByProjectOrderedByPosition($project);
        
        $columnsData = array_map(function (Column $column) {
            return [
                'id' => $column->getId(),
                'name' => $column->getName(),
                'description' => $column->getDescription(),
                'color' => $column->getColor(),
                'position' => $column->getPosition(),
                'isDefault' => $column->isIsDefault(),
                'tasksCount' => $column->getTasksCount(),
                'tasks' => array_map(function ($task) {
                    return [
                        'id' => $task->getId(),
                        'title' => $task->getTitle(),
                        'description' => $task->getDescription(),
                        'priority' => $task->getPriority(),
                        'position' => $task->getPosition(),
                        'assignee' => $task->getAssignee() ? [
                            'id' => $task->getAssignee()->getId(),
                            'firstName' => $task->getAssignee()->getFirstName(),
                            'lastName' => $task->getAssignee()->getLastName(),
                        ] : null,
                        'dueDate' => $task->getDueDate()?->format('Y-m-d'),
                        'createdAt' => $task->getCreatedAt()->format('Y-m-d H:i:s'),
                    ];
                }, $column->getTasks()->toArray())
            ];
        }, $columns);

        return new JsonResponse($columnsData);
    }

    #[Route('/project/{projectId}', name: 'create', methods: ['POST'])]
    public function createColumn(int $projectId, Request $request): JsonResponse
    {
        $project = $this->projectRepository->find($projectId);
        
        if (!$project) {
            return new JsonResponse(['error' => 'Projet non trouvé'], 404);
        }

        // Vérifier les permissions (seul le project manager peut créer des colonnes)
        $user = $this->getUser();
        if (!$this->canManageProject($project, $user)) {
            return new JsonResponse(['error' => 'Accès non autorisé'], 403);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['name']) || empty($data['name'])) {
            return new JsonResponse(['error' => 'Le nom de la colonne est requis'], 400);
        }

        $maxPosition = $this->columnRepository->findMaxPositionForProject($project);
        $newPosition = $maxPosition ? $maxPosition + 1 : 1;

        $column = new Column();
        $column->setName($data['name']);
        $column->setDescription($data['description'] ?? null);
        $column->setColor($data['color'] ?? '#6B7280');
        $column->setPosition($newPosition);
        $column->setProject($project);
        $column->setIsDefault(false);

        $this->entityManager->persist($column);
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $column->getId(),
            'name' => $column->getName(),
            'description' => $column->getDescription(),
            'color' => $column->getColor(),
            'position' => $column->getPosition(),
            'isDefault' => $column->isIsDefault(),
            'tasksCount' => 0,
            'tasks' => []
        ], 201);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function updateColumn(int $id, Request $request): JsonResponse
    {
        $column = $this->columnRepository->find($id);
        
        if (!$column) {
            return new JsonResponse(['error' => 'Colonne non trouvée'], 404);
        }

        // Vérifier les permissions
        $user = $this->getUser();
        if (!$this->canManageProject($column->getProject(), $user)) {
            return new JsonResponse(['error' => 'Accès non autorisé'], 403);
        }

        $data = json_decode($request->getContent(), true);
        
        if (isset($data['name'])) {
            $column->setName($data['name']);
        }
        
        if (isset($data['description'])) {
            $column->setDescription($data['description']);
        }
        
        if (isset($data['color'])) {
            $column->setColor($data['color']);
        }

        $column->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return new JsonResponse([
            'id' => $column->getId(),
            'name' => $column->getName(),
            'description' => $column->getDescription(),
            'color' => $column->getColor(),
            'position' => $column->getPosition(),
            'isDefault' => $column->isIsDefault(),
            'tasksCount' => $column->getTasksCount(),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function deleteColumn(int $id): JsonResponse
    {
        $column = $this->columnRepository->find($id);
        
        if (!$column) {
            return new JsonResponse(['error' => 'Colonne non trouvée'], 404);
        }

        // Vérifier les permissions
        $user = $this->getUser();
        if (!$this->canManageProject($column->getProject(), $user)) {
            return new JsonResponse(['error' => 'Accès non autorisé'], 403);
        }

        // Ne pas permettre la suppression des colonnes par défaut
        if ($column->isIsDefault()) {
            return new JsonResponse(['error' => 'Impossible de supprimer une colonne par défaut'], 400);
        }

        // Déplacer les tâches vers la première colonne disponible
        $project = $column->getProject();
        $otherColumns = $this->columnRepository->findByProjectOrderedByPosition($project);
        $firstColumn = null;
        
        foreach ($otherColumns as $col) {
            if ($col->getId() !== $column->getId()) {
                $firstColumn = $col;
                break;
            }
        }

        if ($firstColumn) {
            foreach ($column->getTasks() as $task) {
                $task->setColumn($firstColumn);
            }
        }

        $this->entityManager->remove($column);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Colonne supprimée avec succès']);
    }

    #[Route('/reorder', name: 'reorder', methods: ['POST'])]
    public function reorderColumns(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['columns']) || !is_array($data['columns'])) {
            return new JsonResponse(['error' => 'Données de réorganisation invalides'], 400);
        }

        foreach ($data['columns'] as $index => $columnData) {
            $column = $this->columnRepository->find($columnData['id']);
            if ($column) {
                $column->setPosition($index + 1);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Colonnes réorganisées avec succès']);
    }

    private function canAccessProject(Project $project, $user): bool
    {
        // Vérifier les rôles globaux
        $userRoles = $user->getRoles();
        if (in_array('ROLE_PROJECT_MANAGER', $userRoles) || 
            in_array('ROLE_MANAGER', $userRoles) || 
            in_array('ROLE_COLLABORATOR', $userRoles)) {
            return true;
        }

        // Le project manager du projet peut accéder
        if ($project->getProjectManager() === $user) {
            return true;
        }

        // Les membres de l'équipe peuvent accéder
        return $project->getTeamMembers()->contains($user);
    }

    private function canManageProject(Project $project, $user): bool
    {
        // Vérifier si l'utilisateur a le rôle PROJECT_MANAGER global
        if (in_array('ROLE_PROJECT_MANAGER', $user->getRoles())) {
            return true;
        }
        
        // Sinon, vérifier si l'utilisateur est le project manager de ce projet spécifique
        return $project->getProjectManager() === $user;
    }
}
