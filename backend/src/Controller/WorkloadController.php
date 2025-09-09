<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Task;
use App\Entity\Workload;
use App\Repository\UserRepository;
use App\Repository\TaskRepository;
use App\Repository\WorkloadRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/workload')]
class WorkloadController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private TaskRepository $taskRepository,
        private WorkloadRepository $workloadRepository
    ) {}

    #[Route('/user/{userId}', name: 'workload_user', methods: ['GET'])]
    #[IsGranted('ROLE_PROJECT_MANAGER')]
    public function getUserWorkload(int $userId): JsonResponse
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
        }

        $workload = $this->calculateUserWorkload($user);
        
        return new JsonResponse($workload);
    }

    #[Route('/all', name: 'workload_all', methods: ['GET'])]
    #[IsGranted('ROLE_PROJECT_MANAGER')]
    public function getAllUsersWorkload(): JsonResponse
    {
        $users = $this->userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :managerRole OR u.roles LIKE :collaboratorRole')
            ->setParameter('managerRole', '%ROLE_MANAGER%')
            ->setParameter('collaboratorRole', '%ROLE_COLLABORATOR%')
            ->getQuery()
            ->getResult();
        $workloads = [];

        foreach ($users as $user) {
            $workloads[] = $this->calculateUserWorkload($user);
        }

        return new JsonResponse($workloads);
    }

    #[Route('/alerts', name: 'workload_alerts', methods: ['GET'])]
    #[IsGranted('ROLE_PROJECT_MANAGER')]
    public function getWorkloadAlerts(): JsonResponse
    {
        $alerts = [];
        $users = $this->userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :managerRole OR u.roles LIKE :collaboratorRole')
            ->setParameter('managerRole', '%ROLE_MANAGER%')
            ->setParameter('collaboratorRole', '%ROLE_COLLABORATOR%')
            ->getQuery()
            ->getResult();

        foreach ($users as $user) {
            $workload = $this->calculateUserWorkload($user);
            
            // Vérifier la surcharge
            if ($workload['utilizationPercentage'] >= 90) {
                $alerts[] = [
                    'id' => 'overload_' . $user->getId(),
                    'userId' => $user->getId(),
                    'type' => 'overload',
                    'message' => $user->getFirstName() . ' ' . $user->getLastName() . ' approche de sa charge de travail maximale (' . number_format($workload['utilizationPercentage'], 1) . '%)',
                    'severity' => $workload['utilizationPercentage'] >= 100 ? 'high' : 'medium',
                    'isRead' => false,
                    'createdAt' => (new \DateTime())->format('c'),
                    'userName' => $user->getFirstName() . ' ' . $user->getLastName()
                ];
            }

            // Vérifier les retards
            $overdueTasks = $this->taskRepository->findOverdueTasksByUser($user);
            foreach ($overdueTasks as $task) {
                $alerts[] = [
                    'id' => 'delay_' . $task->getId(),
                    'userId' => $user->getId(),
                    'type' => 'delay',
                    'message' => 'Tâche en retard : ' . $task->getTitle(),
                    'severity' => 'high',
                    'isRead' => false,
                    'createdAt' => (new \DateTime())->format('c'),
                    'taskId' => $task->getId(),
                    'userName' => $user->getFirstName() . ' ' . $user->getLastName()
                ];
            }
        }

        return new JsonResponse($alerts);
    }

    #[Route('/check-delays', name: 'workload_check_delays', methods: ['POST'])]
    #[IsGranted('ROLE_PROJECT_MANAGER')]
    public function checkTaskDelays(): JsonResponse
    {
        $alerts = [];
        $users = $this->userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :managerRole OR u.roles LIKE :collaboratorRole')
            ->setParameter('managerRole', '%ROLE_MANAGER%')
            ->setParameter('collaboratorRole', '%ROLE_COLLABORATOR%')
            ->getQuery()
            ->getResult();

        foreach ($users as $user) {
            $overdueTasks = $this->taskRepository->findOverdueTasksByUser($user);
            foreach ($overdueTasks as $task) {
                $alerts[] = [
                    'id' => 'delay_' . $task->getId() . '_' . time(),
                    'userId' => $user->getId(),
                    'type' => 'delay',
                    'message' => 'Tâche en retard : ' . $task->getTitle(),
                    'severity' => 'high',
                    'isRead' => false,
                    'createdAt' => (new \DateTime())->format('c'),
                    'taskId' => $task->getId(),
                    'userName' => $user->getFirstName() . ' ' . $user->getLastName()
                ];
            }
        }

        return new JsonResponse($alerts);
    }

    #[Route('/alerts/{alertId}/read', name: 'workload_alert_read', methods: ['PUT'])]
    #[IsGranted('ROLE_PROJECT_MANAGER')]
    public function markAlertAsRead(string $alertId): JsonResponse
    {
        // Dans un vrai système, on stockerait les alertes en base
        // Pour l'instant, on simule juste la réponse
        return new JsonResponse(['success' => true, 'message' => 'Alerte marquée comme lue']);
    }

    private function calculateUserWorkload(User $user): array
    {
        $maxWeekHours = 35; // 8h/jour × 5 jours - 5h de marge
        
        // Récupérer les tâches assignées à l'utilisateur
        $tasks = $this->taskRepository->findBy(['assignee' => $user]);
        
        $currentWeekHours = 0;
        $taskDetails = [];
        
        foreach ($tasks as $task) {
            $estimatedHours = $task->getEstimatedHours() ?? 0;
            $currentWeekHours += $estimatedHours;
            
            $taskDetails[] = [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'estimatedHours' => $estimatedHours,
                'actualHours' => $task->getActualHours() ?? 0,
                'dueDate' => $task->getDueDate() ? $task->getDueDate()->format('Y-m-d') : null,
                'status' => $task->getStatus()
            ];
        }
        
        $utilizationPercentage = $maxWeekHours > 0 ? ($currentWeekHours / $maxWeekHours) * 100 : 0;
        
        return [
            'userId' => $user->getId(),
            'userName' => $user->getFirstName() . ' ' . $user->getLastName(),
            'currentWeekHours' => $currentWeekHours,
            'maxWeekHours' => $maxWeekHours,
            'utilizationPercentage' => $utilizationPercentage,
            'tasks' => $taskDetails,
            'alerts' => [] // Les alertes sont gérées séparément
        ];
    }
}