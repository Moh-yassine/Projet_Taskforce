<?php

namespace App\Controller;

use App\Service\AutoAssignmentService;
use App\Service\PermissionService;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auto-assignment')]
class AutoAssignmentController extends AbstractController
{
    public function __construct(
        private AutoAssignmentService $autoAssignmentService,
        private PermissionService $permissionService,
        private EntityManagerInterface $entityManager,
        private TaskRepository $taskRepository
    ) {}

    /**
     * Récupère les statistiques d'assignation automatique
     */
    #[Route('/stats', name: 'auto_assignment_stats', methods: ['GET'])]
    public function getStats(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Seuls les responsables de projet peuvent accéder aux statistiques
        if (!$this->permissionService->canAssignTasks($user)) {
            return $this->json(['message' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        try {
            $stats = $this->autoAssignmentService->getAssignmentStats();
            return $this->json($stats);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère les tâches non assignées
     */
    #[Route('/unassigned-tasks', name: 'auto_assignment_unassigned_tasks', methods: ['GET'])]
    public function getUnassignedTasks(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->permissionService->canAssignTasks($user)) {
            return $this->json(['message' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        try {
            $unassignedTasks = $this->taskRepository->createQueryBuilder('t')
                ->leftJoin('t.project', 'p')
                ->leftJoin('t.skills', 's')
                ->where('t.assignee IS NULL')
                ->andWhere('t.status != :status')
                ->setParameter('status', 'completed')
                ->orderBy('t.priority', 'DESC')
                ->addOrderBy('t.dueDate', 'ASC')
                ->getQuery()
                ->getResult();

            $tasks = [];
            foreach ($unassignedTasks as $task) {
                $skills = [];
                foreach ($task->getSkills() as $skill) {
                    $skills[] = [
                        'id' => $skill->getId(),
                        'name' => $skill->getName()
                    ];
                }

                $tasks[] = [
                    'id' => $task->getId(),
                    'title' => $task->getTitle(),
                    'description' => $task->getDescription(),
                    'priority' => $task->getPriority(),
                    'status' => $task->getStatus(),
                    'estimatedHours' => $task->getEstimatedHours(),
                    'dueDate' => $task->getDueDate()?->format('Y-m-d H:i:s'),
                    'createdAt' => $task->getCreatedAt()?->format('Y-m-d H:i:s'),
                    'project' => $task->getProject() ? [
                        'id' => $task->getProject()->getId(),
                        'name' => $task->getProject()->getName()
                    ] : null,
                    'skills' => $skills
                ];
            }

            return $this->json($tasks);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de la récupération des tâches non assignées',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère les utilisateurs surchargés
     */
    #[Route('/overloaded-users', name: 'auto_assignment_overloaded_users', methods: ['GET'])]
    public function getOverloadedUsers(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->permissionService->canAssignTasks($user)) {
            return $this->json(['message' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        try {
            $stats = $this->autoAssignmentService->getAssignmentStats();
            $overloadedUsers = array_filter($stats['userWorkloads'], function($userWorkload) {
                return $userWorkload['workload']['currentWeekHours'] > 35; // Seuil de surcharge
            });

            $result = [];
            foreach ($overloadedUsers as $userWorkload) {
                $result[] = [
                    'user' => $userWorkload['user'],
                    'currentHours' => $userWorkload['workload']['currentWeekHours'],
                    'utilizationPercentage' => $userWorkload['workload']['utilizationPercentage'],
                    'remainingCapacity' => $userWorkload['workload']['remainingCapacity'],
                    'status' => $this->getWorkloadStatus($userWorkload['workload']['utilizationPercentage'])
                ];
            }

            return $this->json($result);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de la récupération des utilisateurs surchargés',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Trouve le meilleur candidat pour une tâche spécifique
     */
    #[Route('/find-candidate/{taskId}', name: 'auto_assignment_find_candidate', methods: ['GET'])]
    public function findCandidateForTask(int $taskId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->permissionService->canAssignTasks($user)) {
            return $this->json(['message' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        try {
            $task = $this->taskRepository->find($taskId);

            if (!$task) {
                return $this->json(['message' => 'Tâche non trouvée'], Response::HTTP_NOT_FOUND);
            }

            $candidate = $this->autoAssignmentService->findBestCandidateForTask($task);

            if (!$candidate) {
                return $this->json([
                    'message' => 'Aucun candidat approprié trouvé',
                    'candidate' => null
                ]);
            }

            return $this->json([
                'candidate' => [
                    'user' => [
                        'id' => $candidate['user']->getId(),
                        'firstName' => $candidate['user']->getFirstName(),
                        'lastName' => $candidate['user']->getLastName(),
                        'email' => $candidate['user']->getEmail()
                    ],
                    'score' => round($candidate['score'], 2),
                    'skillMatch' => round($candidate['skillMatch'], 2),
                    'workload' => $candidate['workload'],
                    'recommendation' => $this->getRecommendationText($candidate['score'])
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de la recherche de candidat',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Assigne automatiquement toutes les tâches non assignées
     */
    #[Route('/assign-all', name: 'auto_assignment_assign_all', methods: ['POST'])]
    public function assignAllTasks(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->permissionService->canAssignTasks($user)) {
            return $this->json(['message' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        try {
            $results = $this->autoAssignmentService->assignAllUnassignedTasks();
            
            return $this->json([
                'message' => 'Assignation automatique terminée',
                'assigned' => $results['assigned'],
                'failed' => $results['failed'],
                'details' => $results['details']
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de l\'assignation automatique',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Redistribue les tâches pour équilibrer la charge de travail
     */
    #[Route('/redistribute', name: 'auto_assignment_redistribute', methods: ['POST'])]
    public function redistributeTasks(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->permissionService->canAssignTasks($user)) {
            return $this->json(['message' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        try {
            $results = $this->autoAssignmentService->redistributeWorkload();
            
            return $this->json([
                'message' => 'Redistribution terminée',
                'redistributed' => $results['redistributed'],
                'failed' => $results['failed'],
                'details' => $results['details']
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de la redistribution',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Assigne une tâche spécifique au meilleur candidat
     */
    #[Route('/assign-task/{taskId}', name: 'auto_assignment_assign_task', methods: ['POST'])]
    public function assignSpecificTask(int $taskId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->permissionService->canAssignTasks($user)) {
            return $this->json(['message' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        try {
            $task = $this->taskRepository->find($taskId);

            if (!$task) {
                return $this->json(['message' => 'Tâche non trouvée'], Response::HTTP_NOT_FOUND);
            }

            if ($task->getAssignee()) {
                return $this->json(['message' => 'Cette tâche est déjà assignée'], Response::HTTP_BAD_REQUEST);
            }

            $candidate = $this->autoAssignmentService->findBestCandidateForTask($task);

            if (!$candidate) {
                return $this->json(['message' => 'Aucun candidat approprié trouvé'], Response::HTTP_NOT_FOUND);
            }

            $task->setAssignee($candidate['user']);
            $task->setIsAutoAssigned(true);
            $this->entityManager->persist($task);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Tâche assignée avec succès',
                'task' => [
                    'id' => $task->getId(),
                    'title' => $task->getTitle()
                ],
                'assignedTo' => [
                    'id' => $candidate['user']->getId(),
                    'firstName' => $candidate['user']->getFirstName(),
                    'lastName' => $candidate['user']->getLastName(),
                    'email' => $candidate['user']->getEmail()
                ],
                'assignmentScore' => round($candidate['score'], 2),
                'skillMatch' => round($candidate['skillMatch'], 2)
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de l\'assignation de la tâche',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère les détails de charge de travail pour tous les utilisateurs
     */
    #[Route('/workload-details', name: 'auto_assignment_workload_details', methods: ['GET'])]
    public function getWorkloadDetails(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->permissionService->canAssignTasks($user)) {
            return $this->json(['message' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
        }

        try {
            $stats = $this->autoAssignmentService->getAssignmentStats();
            
            $workloadDetails = [];
            foreach ($stats['userWorkloads'] as $userWorkload) {
                $workloadDetails[] = [
                    'user' => $userWorkload['user'],
                    'workload' => $userWorkload['workload'],
                    'status' => $this->getWorkloadStatus($userWorkload['workload']['utilizationPercentage']),
                    'statusClass' => $this->getWorkloadStatusClass($userWorkload['workload']['utilizationPercentage'])
                ];
            }

            return $this->json([
                'workloadDetails' => $workloadDetails,
                'summary' => [
                    'totalUsers' => $stats['totalUsers'],
                    'unassignedTasks' => $stats['unassignedTasks'],
                    'overloadedUsers' => $stats['overloadedUsers']
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur lors de la récupération des détails de charge',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Détermine le statut de charge de travail
     */
    private function getWorkloadStatus(float $utilizationPercentage): string
    {
        if ($utilizationPercentage >= 100) {
            return 'Surchargé';
        } elseif ($utilizationPercentage >= 87.5) { // 35/40 = 87.5%
            return 'Presque surchargé';
        } elseif ($utilizationPercentage >= 75) {
            return 'Occupé';
        } elseif ($utilizationPercentage >= 50) {
            return 'Modérément occupé';
        } else {
            return 'Disponible';
        }
    }

    /**
     * Détermine la classe CSS pour le statut de charge
     */
    private function getWorkloadStatusClass(float $utilizationPercentage): string
    {
        if ($utilizationPercentage >= 100) {
            return 'overloaded';
        } elseif ($utilizationPercentage >= 87.5) {
            return 'busy';
        } elseif ($utilizationPercentage >= 75) {
            return 'occupied';
        } else {
            return 'available';
        }
    }

    /**
     * Génère un texte de recommandation basé sur le score
     */
    private function getRecommendationText(float $score): string
    {
        if ($score >= 0.8) {
            return 'Excellent candidat - Compétences et disponibilité parfaites';
        } elseif ($score >= 0.6) {
            return 'Bon candidat - Correspond bien aux exigences';
        } elseif ($score >= 0.4) {
            return 'Candidat acceptable - Quelques compromis nécessaires';
        } else {
            return 'Candidat sous-optimal - Considérer d\'autres options';
        }
    }
}
