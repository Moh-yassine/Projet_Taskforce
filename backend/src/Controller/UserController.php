<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private TaskRepository $taskRepository
    ) {}

    #[Route('/assignable', name: 'users_assignable', methods: ['GET'])]
    #[IsGranted('ROLE_PROJECT_MANAGER')]
    public function getAssignableUsers(): JsonResponse
    {
        // Récupérer les utilisateurs qui peuvent recevoir des tâches
        $users = $this->userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :managerRole OR u.roles LIKE :collaboratorRole')
            ->setParameter('managerRole', '%ROLE_MANAGER%')
            ->setParameter('collaboratorRole', '%ROLE_COLLABORATOR%')
            ->getQuery()
            ->getResult();
        
        $assignableUsers = [];
        foreach ($users as $user) {
            $workload = $this->calculateUserWorkload($user);
            
            $assignableUsers[] = [
                'id' => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'currentWeekHours' => $workload['currentWeekHours'],
                'maxWeekHours' => $workload['maxWeekHours'],
                'utilizationPercentage' => $workload['utilizationPercentage'],
                'remainingCapacity' => $workload['maxWeekHours'] - $workload['currentWeekHours'],
                'canReceiveTasks' => $workload['currentWeekHours'] < $workload['maxWeekHours'],
                'skills' => $this->getUserSkills($user)
            ];
        }

        // Trier par capacité restante (décroissant)
        usort($assignableUsers, function($a, $b) {
            return $b['remainingCapacity'] <=> $a['remainingCapacity'];
        });

        return new JsonResponse($assignableUsers);
    }

    #[Route('/{userId}/workload', name: 'user_workload', methods: ['GET'])]
    #[IsGranted('ROLE_PROJECT_MANAGER')]
    public function getUserWorkload(int $userId): JsonResponse
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
        }

        $workload = $this->calculateUserWorkload($user);
        $tasks = $this->taskRepository->findBy(['assignee' => $user]);
        
        $taskDetails = [];
        foreach ($tasks as $task) {
            $taskDetails[] = [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'description' => $task->getDescription(),
                'estimatedHours' => $task->getEstimatedHours(),
                'actualHours' => $task->getActualHours(),
                'dueDate' => $task->getDueDate() ? $task->getDueDate()->format('Y-m-d') : null,
                'status' => $task->getStatus(),
                'priority' => $task->getPriority(),
                'project' => $task->getProject() ? [
                    'id' => $task->getProject()->getId(),
                    'name' => $task->getProject()->getName()
                ] : null
            ];
        }

        return new JsonResponse([
            'userId' => $user->getId(),
            'userName' => $user->getFirstName() . ' ' . $user->getLastName(),
            'currentWeekHours' => $workload['currentWeekHours'],
            'maxWeekHours' => $workload['maxWeekHours'],
            'utilizationPercentage' => $workload['utilizationPercentage'],
            'remainingCapacity' => $workload['maxWeekHours'] - $workload['currentWeekHours'],
            'tasks' => $taskDetails,
            'skills' => $this->getUserSkills($user)
        ]);
    }

    #[Route('/{userId}/tasks', name: 'user_tasks', methods: ['GET'])]
    #[IsGranted('ROLE_PROJECT_MANAGER')]
    public function getUserTasks(int $userId): JsonResponse
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
        }

        $tasks = $this->taskRepository->findBy(['assignee' => $user]);
        
        $taskDetails = [];
        foreach ($tasks as $task) {
            $taskDetails[] = [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'description' => $task->getDescription(),
                'status' => $task->getStatus(),
                'priority' => $task->getPriority(),
                'estimatedHours' => $task->getEstimatedHours(),
                'actualHours' => $task->getActualHours(),
                'dueDate' => $task->getDueDate() ? $task->getDueDate()->format('Y-m-d') : null,
                'createdAt' => $task->getCreatedAt() ? $task->getCreatedAt()->format('Y-m-d H:i:s') : null,
                'project' => $task->getProject() ? [
                    'id' => $task->getProject()->getId(),
                    'name' => $task->getProject()->getName()
                ] : null
            ];
        }

        return new JsonResponse($taskDetails);
    }

    #[Route('/my-tasks', name: 'my_tasks', methods: ['GET'])]
    public function getMyTasks(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $tasks = $this->taskRepository->findBy(['assignee' => $user]);
        
        $taskDetails = [];
        foreach ($tasks as $task) {
            $taskDetails[] = [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'description' => $task->getDescription(),
                'status' => $task->getStatus(),
                'priority' => $task->getPriority(),
                'estimatedHours' => $task->getEstimatedHours(),
                'actualHours' => $task->getActualHours(),
                'dueDate' => $task->getDueDate() ? $task->getDueDate()->format('Y-m-d') : null,
                'createdAt' => $task->getCreatedAt() ? $task->getCreatedAt()->format('Y-m-d H:i:s') : null,
                'project' => $task->getProject() ? [
                    'id' => $task->getProject()->getId(),
                    'name' => $task->getProject()->getName(),
                    'description' => $task->getProject()->getDescription()
                ] : null,
                'assignee' => $task->getAssignee() ? [
                    'id' => $task->getAssignee()->getId(),
                    'firstName' => $task->getAssignee()->getFirstName(),
                    'lastName' => $task->getAssignee()->getLastName(),
                    'email' => $task->getAssignee()->getEmail()
                ] : null
            ];
        }

        return new JsonResponse($taskDetails);
    }

    private function calculateUserWorkload(User $user): array
    {
        $maxWeekHours = 35; // 8h/jour × 5 jours - 5h de marge
        $tasks = $this->taskRepository->findBy(['assignee' => $user]);
        
        $currentWeekHours = 0;
        foreach ($tasks as $task) {
            $currentWeekHours += $task->getEstimatedHours() ?? 0;
        }
        
        $utilizationPercentage = $maxWeekHours > 0 ? ($currentWeekHours / $maxWeekHours) * 100 : 0;
        
        return [
            'currentWeekHours' => $currentWeekHours,
            'maxWeekHours' => $maxWeekHours,
            'utilizationPercentage' => $utilizationPercentage
        ];
    }

    private function getUserSkills(User $user): array
    {
        // Pour l'instant, retourner un tableau vide
        // Dans un vrai système, on récupérerait les compétences de l'utilisateur
        return [];
    }
}