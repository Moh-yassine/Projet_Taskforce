<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/tasks')]
class TaskAssignmentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TaskRepository $taskRepository,
        private UserRepository $userRepository,
        private ValidatorInterface $validator
    ) {}

    #[Route('/{taskId}/assign', name: 'task_assign', methods: ['POST'])]
    #[IsGranted('ROLE_PROJECT_MANAGER')]
    public function assignTask(int $taskId, Request $request): JsonResponse
    {
        $task = $this->taskRepository->find($taskId);
        if (!$task) {
            return new JsonResponse(['error' => 'Tâche non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['userId'])) {
            return new JsonResponse(['error' => 'ID utilisateur requis'], 400);
        }

        $user = $this->userRepository->find($data['userId']);
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
        }

        // Vérifier que l'utilisateur peut recevoir des tâches
        if (!in_array('ROLE_MANAGER', $user->getRoles()) && !in_array('ROLE_COLLABORATOR', $user->getRoles())) {
            return new JsonResponse(['error' => 'Cet utilisateur ne peut pas recevoir de tâches'], 400);
        }

        // Vérifier la charge de travail
        $workload = $this->calculateUserWorkload($user);
        $estimatedHours = $data['estimatedHours'] ?? $task->getEstimatedHours() ?? 0;
        $newTotalHours = $workload['currentWeekHours'] + $estimatedHours;

        if ($newTotalHours > 35) { // 35h/semaine maximum
            return new JsonResponse([
                'success' => false,
                'message' => $user->getFirstName() . ' ' . $user->getLastName() . ' dépasserait sa charge de travail maximale (' . $newTotalHours . 'h > 35h)'
            ], 400);
        }

        // Mettre à jour la tâche
        $task->setAssignee($user);
        
        if (isset($data['estimatedHours'])) {
            $task->setEstimatedHours($data['estimatedHours']);
        }
        
        if (isset($data['dueDate'])) {
            $task->setDueDate(new \DateTime($data['dueDate']));
        }
        
        if (isset($data['priority'])) {
            $task->setPriority($data['priority']);
        }

        $task->setUpdatedAt(new \DateTime());

        // Valider la tâche
        $errors = $this->validator->validate($task);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['error' => 'Données invalides', 'details' => $errorMessages], 400);
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        // Vérifier si une alerte doit être générée
        $utilizationPercentage = ($newTotalHours / 35) * 100;
        $alert = null;
        
        if ($utilizationPercentage >= 90) {
            $alert = [
                'id' => 'overload_' . $user->getId() . '_' . time(),
                'userId' => $user->getId(),
                'type' => 'overload',
                'message' => $user->getFirstName() . ' ' . $user->getLastName() . ' approche de sa charge de travail maximale (' . number_format($utilizationPercentage, 1) . '%)',
                'severity' => $utilizationPercentage >= 100 ? 'high' : 'medium',
                'isRead' => false,
                'createdAt' => (new \DateTime())->format('c'),
                'taskId' => $task->getId()
            ];
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Tâche assignée avec succès',
            'task' => [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'assignee' => [
                    'id' => $user->getId(),
                    'name' => $user->getFirstName() . ' ' . $user->getLastName()
                ],
                'estimatedHours' => $task->getEstimatedHours(),
                'dueDate' => $task->getDueDate() ? $task->getDueDate()->format('Y-m-d') : null
            ],
            'alert' => $alert
        ]);
    }

    #[Route('/{taskId}/hours', name: 'task_update_hours', methods: ['PUT'])]
    #[IsGranted('ROLE_PROJECT_MANAGER')]
    public function updateTaskHours(int $taskId, Request $request): JsonResponse
    {
        $task = $this->taskRepository->find($taskId);
        if (!$task) {
            return new JsonResponse(['error' => 'Tâche non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['actualHours'])) {
            return new JsonResponse(['error' => 'Heures réelles requises'], 400);
        }

        $task->setActualHours($data['actualHours']);
        $task->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Heures mises à jour avec succès',
            'task' => [
                'id' => $task->getId(),
                'actualHours' => $task->getActualHours()
            ]
        ]);
    }

    #[Route('/{taskId}/unassign', name: 'task_unassign', methods: ['POST'])]
    #[IsGranted('ROLE_PROJECT_MANAGER')]
    public function unassignTask(int $taskId): JsonResponse
    {
        $task = $this->taskRepository->find($taskId);
        if (!$task) {
            return new JsonResponse(['error' => 'Tâche non trouvée'], 404);
        }

        $task->setAssignee(null);
        $task->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Tâche désassignée avec succès'
        ]);
    }

    private function calculateUserWorkload(User $user): array
    {
        $maxWeekHours = 35;
        $tasks = $this->taskRepository->findBy(['assignee' => $user]);
        
        $currentWeekHours = 0;
        foreach ($tasks as $task) {
            $currentWeekHours += $task->getEstimatedHours() ?? 0;
        }
        
        return [
            'currentWeekHours' => $currentWeekHours,
            'maxWeekHours' => $maxWeekHours
        ];
    }
}