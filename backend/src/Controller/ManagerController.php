<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Task;
use App\Entity\Project;
use App\Repository\UserRepository;
use App\Repository\TaskRepository;
use App\Repository\ProjectRepository;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/manager')]
class ManagerController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private TaskRepository $taskRepository,
        private ProjectRepository $projectRepository,
        private NotificationRepository $notificationRepository
    ) {}

    #[Route('/dashboard', name: 'manager_dashboard', methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function getDashboard(): JsonResponse
    {
        try {
            $manager = $this->getUser();
            
            // Récupérer les collaborateurs sous la supervision du manager
            $collaborators = $this->getCollaboratorsUnderSupervision($manager);
            
            // Statistiques générales
            $stats = $this->getManagerStats($manager, $collaborators);
            
            // Tâches en cours avec progression
            $tasksInProgress = $this->getTasksInProgress($collaborators);
            
            // Alertes récentes
            $recentAlerts = $this->getRecentAlerts($manager);
            
            // Projets supervisés
            $supervisedProjects = $this->getSupervisedProjects($manager);
            
            return new JsonResponse([
                'stats' => $stats,
                'collaborators' => $collaborators,
                'tasksInProgress' => $tasksInProgress,
                'recentAlerts' => $recentAlerts,
                'supervisedProjects' => $supervisedProjects
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors du chargement du dashboard: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/tasks/progress', name: 'manager_tasks_progress', methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function getTasksProgress(): JsonResponse
    {
        try {
            $manager = $this->getUser();
            $collaborators = $this->getCollaboratorsUnderSupervision($manager);
            
            $tasks = $this->taskRepository->createQueryBuilder('t')
                ->leftJoin('t.assignee', 'u')
                ->leftJoin('t.project', 'p')
                ->where('u IN (:collaborators)')
                ->setParameter('collaborators', $collaborators)
                ->orderBy('t.dueDate', 'ASC')
                ->getQuery()
                ->getResult();
            
            $tasksData = [];
            foreach ($tasks as $task) {
                $progress = $this->calculateTaskProgress($task);
                $isOverdue = $task->getDueDate() && $task->getDueDate() < new \DateTime() && $task->getStatus() !== 'completed';
                
                $tasksData[] = [
                    'id' => $task->getId(),
                    'title' => $task->getTitle(),
                    'description' => $task->getDescription(),
                    'status' => $task->getStatus(),
                    'priority' => $task->getPriority(),
                    'estimatedHours' => $task->getEstimatedHours(),
                    'actualHours' => $task->getActualHours(),
                    'dueDate' => $task->getDueDate() ? $task->getDueDate()->format('Y-m-d') : null,
                    'createdAt' => $task->getCreatedAt() ? $task->getCreatedAt()->format('Y-m-d H:i:s') : null,
                    'progress' => $progress,
                    'isOverdue' => $isOverdue,
                    'assignee' => $task->getAssignee() ? [
                        'id' => $task->getAssignee()->getId(),
                        'firstName' => $task->getAssignee()->getFirstName(),
                        'lastName' => $task->getAssignee()->getLastName(),
                        'email' => $task->getAssignee()->getEmail()
                    ] : null,
                    'project' => $task->getProject() ? [
                        'id' => $task->getProject()->getId(),
                        'name' => $task->getProject()->getName()
                    ] : null
                ];
            }
            
            return new JsonResponse($tasksData);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la récupération des tâches: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/tasks/{id}/priority', name: 'manager_update_task_priority', methods: ['PUT'])]
    #[IsGranted('ROLE_MANAGER')]
    public function updateTaskPriority(int $id, Request $request): JsonResponse
    {
        try {
            $task = $this->taskRepository->find($id);
            if (!$task) {
                return new JsonResponse(['error' => 'Tâche non trouvée'], 404);
            }
            
            // Vérifier que le manager supervise cette tâche
            if (!$this->canManageTask($task)) {
                return new JsonResponse(['error' => 'Vous ne pouvez pas modifier cette tâche'], 403);
            }
            
            $data = json_decode($request->getContent(), true);
            if (!isset($data['priority'])) {
                return new JsonResponse(['error' => 'Priorité manquante'], 400);
            }
            
            $newPriority = $data['priority'];
            $validPriorities = ['low', 'medium', 'high', 'urgent'];
            
            if (!in_array($newPriority, $validPriorities)) {
                return new JsonResponse(['error' => 'Priorité invalide'], 400);
            }
            
            $task->setPriority($newPriority);
            $this->entityManager->persist($task);
            $this->entityManager->flush();
            
            return new JsonResponse([
                'message' => 'Priorité mise à jour avec succès',
                'task' => [
                    'id' => $task->getId(),
                    'title' => $task->getTitle(),
                    'priority' => $task->getPriority()
                ]
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la mise à jour de la priorité: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/reports/task-distribution', name: 'manager_report_task_distribution', methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function getTaskDistributionReport(): JsonResponse
    {
        try {
            $manager = $this->getUser();
            $collaborators = $this->getCollaboratorsUnderSupervision($manager);
            
            $report = [
                'totalTasks' => 0,
                'tasksByStatus' => [],
                'tasksByPriority' => [],
                'tasksByCollaborator' => [],
                'overdueTasks' => 0,
                'averageCompletionTime' => 0
            ];
            
            $tasks = $this->taskRepository->createQueryBuilder('t')
                ->leftJoin('t.assignee', 'u')
                ->where('u IN (:collaborators)')
                ->setParameter('collaborators', $collaborators)
                ->getQuery()
                ->getResult();
            
            $report['totalTasks'] = count($tasks);
            $totalCompletionTime = 0;
            $completedTasks = 0;
            
            foreach ($tasks as $task) {
                // Par statut
                $status = $task->getStatus();
                $report['tasksByStatus'][$status] = ($report['tasksByStatus'][$status] ?? 0) + 1;
                
                // Par priorité
                $priority = $task->getPriority();
                $report['tasksByPriority'][$priority] = ($report['tasksByPriority'][$priority] ?? 0) + 1;
                
                // Par collaborateur
                if ($task->getAssignee()) {
                    $assigneeId = $task->getAssignee()->getId();
                    $report['tasksByCollaborator'][$assigneeId] = [
                        'name' => $task->getAssignee()->getFirstName() . ' ' . $task->getAssignee()->getLastName(),
                        'count' => ($report['tasksByCollaborator'][$assigneeId]['count'] ?? 0) + 1
                    ];
                }
                
                // Tâches en retard
                if ($task->getDueDate() && $task->getDueDate() < new \DateTime() && $task->getStatus() !== 'completed') {
                    $report['overdueTasks']++;
                }
                
                // Temps de completion moyen
                if ($task->getStatus() === 'completed' && $task->getCreatedAt()) {
                    $completionTime = $task->getUpdatedAt() ? $task->getUpdatedAt()->diff($task->getCreatedAt())->days : 0;
                    $totalCompletionTime += $completionTime;
                    $completedTasks++;
                }
            }
            
            if ($completedTasks > 0) {
                $report['averageCompletionTime'] = round($totalCompletionTime / $completedTasks, 1);
            }
            
            return new JsonResponse($report);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la génération du rapport: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/reports/performance', name: 'manager_report_performance', methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function getPerformanceReport(): JsonResponse
    {
        try {
            $manager = $this->getUser();
            $collaborators = $this->getCollaboratorsUnderSupervision($manager);
            
            $report = [];
            
            foreach ($collaborators as $collaborator) {
                $tasks = $this->taskRepository->findBy(['assignee' => $collaborator]);
                
                $totalTasks = count($tasks);
                $completedTasks = 0;
                $overdueTasks = 0;
                $totalEstimatedHours = 0;
                $totalActualHours = 0;
                
                foreach ($tasks as $task) {
                    if ($task->getStatus() === 'completed') {
                        $completedTasks++;
                    }
                    
                    if ($task->getDueDate() && $task->getDueDate() < new \DateTime() && $task->getStatus() !== 'completed') {
                        $overdueTasks++;
                    }
                    
                    $totalEstimatedHours += $task->getEstimatedHours() ?? 0;
                    $totalActualHours += $task->getActualHours() ?? 0;
                }
                
                $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;
                $efficiency = $totalEstimatedHours > 0 ? round(($totalEstimatedHours / max($totalActualHours, 1)) * 100, 1) : 0;
                
                $report[] = [
                    'collaborator' => [
                        'id' => $collaborator->getId(),
                        'firstName' => $collaborator->getFirstName(),
                        'lastName' => $collaborator->getLastName(),
                        'email' => $collaborator->getEmail()
                    ],
                    'totalTasks' => $totalTasks,
                    'completedTasks' => $completedTasks,
                    'overdueTasks' => $overdueTasks,
                    'completionRate' => $completionRate,
                    'efficiency' => $efficiency,
                    'totalEstimatedHours' => $totalEstimatedHours,
                    'totalActualHours' => $totalActualHours
                ];
            }
            
            return new JsonResponse($report);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la génération du rapport de performance: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/alerts', name: 'manager_alerts', methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function getAlerts(): JsonResponse
    {
        try {
            $manager = $this->getUser();
            
        $alerts = $this->notificationRepository->createQueryBuilder('n')
            ->where('n.user = :manager')
            ->andWhere('n.type IN (:types)')
            ->setParameter('manager', $manager)
            ->setParameter('types', ['workload_alert', 'delay_alert'])
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
            
            $alertsData = [];
            foreach ($alerts as $alert) {
                $alertsData[] = [
                    'id' => $alert->getId(),
                    'title' => $alert->getTitle(),
                    'message' => $alert->getMessage(),
                    'type' => $alert->getType(),
                    'isRead' => $alert->isRead(),
                    'createdAt' => $alert->getCreatedAt() ? $alert->getCreatedAt()->format('Y-m-d H:i:s') : null
                ];
            }
            
            return new JsonResponse($alertsData);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la récupération des alertes: ' . $e->getMessage()], 500);
        }
    }

    private function getCollaboratorsUnderSupervision(User $manager): array
    {
        // Pour l'instant, on considère que le manager supervise tous les collaborateurs
        // Dans un vrai système, il y aurait une relation de supervision
        return $this->userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :collaboratorRole')
            ->setParameter('collaboratorRole', '%ROLE_COLLABORATOR%')
            ->getQuery()
            ->getResult();
    }

    private function getManagerStats(User $manager, array $collaborators): array
    {
        $collaboratorIds = array_map(fn($c) => $c->getId(), $collaborators);
        
        $totalTasks = $this->taskRepository->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->leftJoin('t.assignee', 'u')
            ->where('u IN (:collaborators)')
            ->setParameter('collaborators', $collaborators)
            ->getQuery()
            ->getSingleScalarResult();
        
        $completedTasks = $this->taskRepository->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->leftJoin('t.assignee', 'u')
            ->where('u IN (:collaborators)')
            ->andWhere('t.status = :status')
            ->setParameter('collaborators', $collaborators)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();
        
        $overdueTasks = $this->taskRepository->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->leftJoin('t.assignee', 'u')
            ->where('u IN (:collaborators)')
            ->andWhere('t.dueDate < :now')
            ->andWhere('t.status != :status')
            ->setParameter('collaborators', $collaborators)
            ->setParameter('now', new \DateTime())
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();
        
        $unreadAlerts = $this->notificationRepository->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.user = :manager')
            ->andWhere('n.isRead = :isRead')
            ->setParameter('manager', $manager)
            ->setParameter('isRead', false)
            ->getQuery()
            ->getSingleScalarResult();
        
        return [
            'totalCollaborators' => count($collaborators),
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
            'overdueTasks' => $overdueTasks,
            'unreadAlerts' => $unreadAlerts,
            'completionRate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0
        ];
    }

    private function getTasksInProgress(array $collaborators): array
    {
        $tasks = $this->taskRepository->createQueryBuilder('t')
            ->leftJoin('t.assignee', 'u')
            ->leftJoin('t.project', 'p')
            ->where('u IN (:collaborators)')
            ->andWhere('t.status IN (:statuses)')
            ->setParameter('collaborators', $collaborators)
            ->setParameter('statuses', ['todo', 'in_progress'])
            ->orderBy('t.priority', 'DESC')
            ->addOrderBy('t.dueDate', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
        
        $tasksData = [];
        foreach ($tasks as $task) {
            $progress = $this->calculateTaskProgress($task);
            
            $tasksData[] = [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'status' => $task->getStatus(),
                'priority' => $task->getPriority(),
                'progress' => $progress,
                'dueDate' => $task->getDueDate() ? $task->getDueDate()->format('Y-m-d') : null,
                'assignee' => $task->getAssignee() ? [
                    'firstName' => $task->getAssignee()->getFirstName(),
                    'lastName' => $task->getAssignee()->getLastName()
                ] : null,
                'project' => $task->getProject() ? [
                    'name' => $task->getProject()->getName()
                ] : null
            ];
        }
        
        return $tasksData;
    }

    private function getRecentAlerts(User $manager): array
    {
        $alerts = $this->notificationRepository->createQueryBuilder('n')
            ->where('n.user = :manager')
            ->setParameter('manager', $manager)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
        
        $alertsData = [];
        foreach ($alerts as $alert) {
            $alertsData[] = [
                'id' => $alert->getId(),
                'title' => $alert->getTitle(),
                'message' => $alert->getMessage(),
                'type' => $alert->getType(),
                'isRead' => $alert->isRead(),
                'createdAt' => $alert->getCreatedAt() ? $alert->getCreatedAt()->format('Y-m-d H:i:s') : null
            ];
        }
        
        return $alertsData;
    }

    private function getSupervisedProjects(User $manager): array
    {
        // Pour l'instant, on récupère tous les projets
        // Dans un vrai système, il y aurait une relation de supervision
        $projects = $this->projectRepository->findAll();
        
        $projectsData = [];
        foreach ($projects as $project) {
            $tasks = $this->taskRepository->findBy(['project' => $project]);
            $completedTasks = array_filter($tasks, fn($t) => $t->getStatus() === 'completed');
            
            $projectsData[] = [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'description' => $project->getDescription(),
                'totalTasks' => count($tasks),
                'completedTasks' => count($completedTasks),
                'progress' => count($tasks) > 0 ? round((count($completedTasks) / count($tasks)) * 100, 1) : 0
            ];
        }
        
        return $projectsData;
    }

    private function calculateTaskProgress(Task $task): int
    {
        switch ($task->getStatus()) {
            case 'completed':
                return 100;
            case 'in_progress':
                return 50;
            case 'todo':
                return 0;
            default:
                return 0;
        }
    }

    private function canManageTask(Task $task): bool
    {
        // Vérifier si le manager peut gérer cette tâche
        // Pour l'instant, on considère que le manager peut gérer toutes les tâches
        return true;
    }
}
