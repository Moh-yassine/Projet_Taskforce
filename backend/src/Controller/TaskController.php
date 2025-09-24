<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\Skill;
use App\Repository\TaskRepository;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use App\Repository\SkillRepository;
use App\Service\PermissionService;
use App\Service\AlertService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/tasks', name: 'api_tasks_')]
class TaskController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
        private TaskRepository $taskRepository,
        private ProjectRepository $projectRepository,
        private UserRepository $userRepository,
        private SkillRepository $skillRepository,
        private PermissionService $permissionService,
        private AlertService $alertService
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $projectId = $request->query->get('project_id');
        
        if ($projectId) {
            $project = $this->projectRepository->find($projectId);
            if (!$project || !$this->permissionService->canViewProject($user, $project)) {
                return $this->json(['message' => 'Accès non autorisé à ce projet'], Response::HTTP_FORBIDDEN);
            }
            $tasks = $this->taskRepository->findByProject((int) $projectId);
        } else {
            // Filtrer les tâches selon les permissions
            if ($this->permissionService->canViewAllTasks($user)) {
                $tasks = $this->taskRepository->findAll();
            } else {
                // Le collaborateur ne voit que ses tâches assignées
                $tasks = $this->taskRepository->findBy(['assignee' => $user]);
            }
        }

        // Filtrer les tâches que l'utilisateur peut voir
        $filteredTasks = array_filter($tasks, function($task) use ($user) {
            return $this->permissionService->canViewTask($user, $task);
        });

        $data = $this->serializer->serialize($filteredTasks, 'json', ['groups' => 'task:read']);
        return new JsonResponse($data, 200, [], true);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Seul le responsable de projet peut créer des tâches
        if (!$this->permissionService->canAssignTasks($user)) {
            return $this->json(['message' => 'Accès non autorisé pour créer des tâches'], Response::HTTP_FORBIDDEN);
        }

        try {
            $taskData = json_decode($request->getContent(), true);
            
            $task = new Task();
            $task->setTitle($taskData['title']);
            $task->setDescription($taskData['description'] ?? '');
            $task->setStatus($taskData['status'] ?? 'todo');
            $task->setPriority($taskData['priority']);
            
            // Nouveaux champs
            if (isset($taskData['dueDate'])) {
                $task->setDueDate(new \DateTime($taskData['dueDate']));
            }
            if (isset($taskData['estimatedHours'])) {
                $task->setEstimatedHours($taskData['estimatedHours']);
            }
            if (isset($taskData['actualHours'])) {
                $task->setActualHours($taskData['actualHours']);
            }
            
            // Projet
            if (isset($taskData['projectId'])) {
                $project = $this->projectRepository->find($taskData['projectId']);
                if ($project) {
                    $task->setProject($project);
                }
            }
            
            // Assignation
            if (isset($taskData['assigneeId']) && $taskData['assigneeId']) {
                $assignee = $this->userRepository->find($taskData['assigneeId']);
                if ($assignee) {
                    $task->setAssignee($assignee);
                }
            }
            
            // Assignations multiples
            if (isset($taskData['assignments']) && is_array($taskData['assignments'])) {
                foreach ($taskData['assignments'] as $assignmentData) {
                    if (isset($assignmentData['userId']) && isset($assignmentData['role'])) {
                        $user = $this->userRepository->find($assignmentData['userId']);
                        if ($user) {
                            $assignment = new \App\Entity\TaskAssignment();
                            $assignment->setTask($task);
                            $assignment->setUser($user);
                            $assignment->setRole($assignmentData['role']);
                            $task->addAssignment($assignment);
                        }
                    }
                }
            }
            
            // Compétences
            if (isset($taskData['skills']) && is_array($taskData['skills'])) {
                foreach ($taskData['skills'] as $skillName) {
                    if (is_string($skillName) && !empty(trim($skillName))) {
                        // Chercher si la compétence existe déjà
                        $skill = $this->skillRepository->findOneBy(['name' => trim($skillName)]);
                        if (!$skill) {
                            // Créer une nouvelle compétence
                            $skill = new Skill();
                            $skill->setName(trim($skillName));
                            $this->em->persist($skill);
                        }
                        $task->addSkill($skill);
                    }
                }
            }
            
            $this->em->persist($task);
            $this->em->flush();
            
            // Assignation automatique supprimée - utilisez l'assignation simple
            
            // Les alertes seront vérifiées automatiquement via l'EventListener
            
            $data = $this->serializer->serialize($task, 'json', ['groups' => 'task:read']);
            return new JsonResponse($data, 201, [], true);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Task $task): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->permissionService->canViewTask($user, $task)) {
            return $this->json(['message' => 'Accès non autorisé à cette tâche'], Response::HTTP_FORBIDDEN);
        }

        $data = $this->serializer->serialize($task, 'json', ['groups' => 'task:read']);
        return new JsonResponse($data, 200, [], true);
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Task $task, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->permissionService->canEditTask($user, $task)) {
            return $this->json(['message' => 'Accès non autorisé pour modifier cette tâche'], Response::HTTP_FORBIDDEN);
        }

        try {
            $taskData = json_decode($request->getContent(), true);
            
            $task->setTitle($taskData['title']);
            $task->setDescription($taskData['description'] ?? '');
            $task->setStatus($taskData['status'] ?? $task->getStatus());
            $task->setPriority($taskData['priority']);
            $task->setUpdatedAt(new \DateTime());
            
            // Nouveaux champs
            if (isset($taskData['dueDate'])) {
                $task->setDueDate(new \DateTime($taskData['dueDate']));
            }
            if (isset($taskData['estimatedHours'])) {
                $task->setEstimatedHours($taskData['estimatedHours']);
            }
            if (isset($taskData['actualHours'])) {
                $task->setActualHours($taskData['actualHours']);
            }
            
            // Assignation simple (pour compatibilité)
            if (isset($taskData['assigneeId'])) {
                if ($taskData['assigneeId']) {
                    $assignee = $this->userRepository->find($taskData['assigneeId']);
                    $task->setAssignee($assignee);
                } else {
                    $task->setAssignee(null);
                }
            }
            
            // Assignations multiples
            if (isset($taskData['assignments']) && is_array($taskData['assignments'])) {
                // Supprimer toutes les assignations existantes
                foreach ($task->getAssignments() as $assignment) {
                    $task->removeAssignment($assignment);
                    $this->em->remove($assignment);
                }
                
                // Ajouter les nouvelles assignations
                foreach ($taskData['assignments'] as $assignmentData) {
                    if (isset($assignmentData['userId']) && isset($assignmentData['role'])) {
                        $user = $this->userRepository->find($assignmentData['userId']);
                        if ($user) {
                            $assignment = new \App\Entity\TaskAssignment();
                            $assignment->setTask($task);
                            $assignment->setUser($user);
                            $assignment->setRole($assignmentData['role']);
                            $task->addAssignment($assignment);
                        }
                    }
                }
            }
            
            // Compétences
            if (isset($taskData['skills']) && is_array($taskData['skills'])) {
                // Supprimer toutes les compétences existantes
                foreach ($task->getSkills() as $skill) {
                    $task->removeSkill($skill);
                }
                
                // Ajouter les nouvelles compétences
                foreach ($taskData['skills'] as $skillName) {
                    if (is_string($skillName) && !empty(trim($skillName))) {
                        // Chercher si la compétence existe déjà
                        $skill = $this->skillRepository->findOneBy(['name' => trim($skillName)]);
                        if (!$skill) {
                            // Créer une nouvelle compétence
                            $skill = new Skill();
                            $skill->setName(trim($skillName));
                            $this->em->persist($skill);
                        }
                        $task->addSkill($skill);
                    }
                }
            }
            
            $this->em->flush();
            
            // Les alertes seront vérifiées automatiquement via l'EventListener
            
            $data = $this->serializer->serialize($task, 'json', ['groups' => 'task:read']);
            return new JsonResponse($data, 200, [], true);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Task $task): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Permettre la suppression si l'utilisateur a les permissions d'assignation OU s'il est assigné à la tâche
        $canDelete = $this->permissionService->canAssignTasks($user) || 
                     $task->getAssignee() === $user;
        
        if (!$canDelete) {
            return $this->json(['message' => 'Accès non autorisé pour supprimer cette tâche'], Response::HTTP_FORBIDDEN);
        }

        try {
            $this->em->remove($task);
            $this->em->flush();
            
            return new JsonResponse(['message' => 'Tâche supprimée avec succès'], 200);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors de la suppression de la tâche: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/trigger-alerts', name: 'trigger_alerts', methods: ['POST'])]
    public function triggerAlerts(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Seuls les managers peuvent déclencher manuellement les alertes
        if (!$this->permissionService->canAssignTasks($user)) {
            return $this->json(['message' => 'Accès non autorisé pour déclencher les alertes'], Response::HTTP_FORBIDDEN);
        }

        try {
            $alerts = $this->alertService->checkAndGenerateAllAlerts();
            
            return $this->json([
                'message' => sprintf('%d alertes générées', count($alerts)),
                'alerts' => $alerts
            ], 200, [], ['groups' => ['notification:read']]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
