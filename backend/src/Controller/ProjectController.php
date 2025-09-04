<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use App\Entity\Skill;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use App\Repository\SkillRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/projects')]
class ProjectController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProjectRepository $projectRepository,
        private TaskRepository $taskRepository,
        private UserRepository $userRepository,
        private SkillRepository $skillRepository,
        private ValidatorInterface $validator
    ) {}

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        if ($user->isProjectManager()) {
            $projects = $this->projectRepository->findByProjectManager($user);
        } else {
            $projects = $this->projectRepository->findByTeamMember($user);
        }

        $data = [];
        foreach ($projects as $project) {
            $data[] = [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'description' => $project->getDescription(),
                'status' => $project->getStatus(),
                'startDate' => $project->getStartDate()->format('Y-m-d'),
                'endDate' => $project->getEndDate()->format('Y-m-d'),
                'projectManager' => [
                    'id' => $project->getProjectManager()->getId(),
                    'name' => $project->getProjectManager()->getFullName()
                ],
                'taskCount' => $project->getTasks()->count(),
                'teamMemberCount' => $project->getTeamMembers()->count()
            ];
        }

        return $this->json($data);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof User || !$user->isProjectManager()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $data = json_decode($request->getContent(), true);

        $project = new Project();
        $project->setName($data['name']);
        $project->setDescription($data['description'] ?? '');
        $project->setStartDate(new \DateTimeImmutable($data['startDate']));
        $project->setEndDate(new \DateTimeImmutable($data['endDate']));
        $project->setStatus($data['status'] ?? 'planning');
        $project->setProjectManager($user);

        if (isset($data['teamMembers'])) {
            foreach ($data['teamMembers'] as $memberId) {
                $member = $this->userRepository->find($memberId);
                if ($member) {
                    $project->addTeamMember($member);
                }
            }
        }

        $errors = $this->validator->validate($project);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Projet créé avec succès',
            'project' => [
                'id' => $project->getId(),
                'name' => $project->getName()
            ]
        ], 201);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $project = $this->projectRepository->find($id);
        
        if (!$project) {
            return $this->json(['error' => 'Projet non trouvé'], 404);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        if (!$user->isProjectManager() && !$project->getTeamMembers()->contains($user)) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $tasks = $this->taskRepository->findByProject($project);
        $taskData = [];
        
        foreach ($tasks as $task) {
            $taskData[] = [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'description' => $task->getDescription(),
                'status' => $task->getStatus(),
                'priority' => $task->getPriority(),
                'dueDate' => $task->getDueDate()->format('Y-m-d'),
                'assignedTo' => $task->getAssignedTo() ? [
                    'id' => $task->getAssignedTo()->getId(),
                    'name' => $task->getAssignedTo()->getFullName()
                ] : null,
                'requiredSkills' => array_map(fn($skill) => [
                    'id' => $skill->getId(),
                    'name' => $skill->getName()
                ], $task->getRequiredSkills()->toArray())
            ];
        }

        $data = [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'description' => $project->getDescription(),
            'status' => $project->getStatus(),
            'startDate' => $project->getStartDate()->format('Y-m-d'),
            'endDate' => $project->getEndDate()->format('Y-m-d'),
            'projectManager' => [
                'id' => $project->getProjectManager()->getId(),
                'name' => $project->getProjectManager()->getFullName()
            ],
            'teamMembers' => array_map(fn($member) => [
                'id' => $member->getId(),
                'name' => $member->getFullName(),
                'email' => $member->getEmail()
            ], $project->getTeamMembers()->toArray()),
            'tasks' => $taskData
        ];

        return $this->json($data);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $project = $this->projectRepository->find($id);
        
        if (!$project) {
            return $this->json(['error' => 'Projet non trouvé'], 404);
        }

        $user = $this->getUser();
        if (!$user instanceof User || !$user->isProjectManager()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        if ($project->getProjectManager() !== $user) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $project->setName($data['name']);
        }
        if (isset($data['description'])) {
            $project->setDescription($data['description']);
        }
        if (isset($data['status'])) {
            $project->setStatus($data['status']);
        }
        if (isset($data['startDate'])) {
            $project->setStartDate(new \DateTimeImmutable($data['startDate']));
        }
        if (isset($data['endDate'])) {
            $project->setEndDate(new \DateTimeImmutable($data['endDate']));
        }

        $project->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return $this->json(['message' => 'Projet mis à jour avec succès']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $project = $this->projectRepository->find($id);
        
        if (!$project) {
            return $this->json(['error' => 'Projet non trouvé'], 404);
        }

        $user = $this->getUser();
        if (!$user instanceof User || !$user->isProjectManager()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        if ($project->getProjectManager() !== $user) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $this->entityManager->remove($project);
        $this->entityManager->flush();

        return $this->json(['message' => 'Projet supprimé avec succès']);
    }

    #[Route('/{id}/tasks', methods: ['POST'])]
    public function createTask(int $id, Request $request): JsonResponse
    {
        $project = $this->projectRepository->find($id);
        
        if (!$project) {
            return $this->json(['error' => 'Projet non trouvé'], 404);
        }

        $user = $this->getUser();
        if (!$user instanceof User || !$user->isProjectManager()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        if ($project->getProjectManager() !== $user) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $data = json_decode($request->getContent(), true);

        $task = new Task();
        $task->setTitle($data['title']);
        $task->setDescription($data['description'] ?? '');
        $task->setPriority($data['priority'] ?? 'medium');
        $task->setDueDate(new \DateTimeImmutable($data['dueDate']));
        $task->setProject($project);
        $task->setCreatedBy($user);

        if (isset($data['assignedTo'])) {
            $assignedUser = $this->userRepository->find($data['assignedTo']);
            if ($assignedUser) {
                $task->setAssignedTo($assignedUser);
            }
        }

        if (isset($data['requiredSkills'])) {
            foreach ($data['requiredSkills'] as $skillId) {
                $skill = $this->skillRepository->find($skillId);
                if ($skill) {
                    $task->addRequiredSkill($skill);
                }
            }
        }

        $errors = $this->validator->validate($task);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Tâche créée avec succès',
            'task' => [
                'id' => $task->getId(),
                'title' => $task->getTitle()
            ]
        ], 201);
    }

    #[Route('/{id}/team-members', methods: ['POST'])]
    public function addTeamMember(int $id, Request $request): JsonResponse
    {
        $project = $this->projectRepository->find($id);
        
        if (!$project) {
            return $this->json(['error' => 'Projet non trouvé'], 404);
        }

        $user = $this->getUser();
        if (!$user instanceof User || !$user->isProjectManager()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        if ($project->getProjectManager() !== $user) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $memberId = $data['userId'];

        $member = $this->userRepository->find($memberId);
        if (!$member) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $project->addTeamMember($member);
        $this->entityManager->flush();

        return $this->json(['message' => 'Membre ajouté au projet']);
    }

    #[Route('/{id}/team-members/{memberId}', methods: ['DELETE'])]
    public function removeTeamMember(int $id, int $memberId): JsonResponse
    {
        $project = $this->projectRepository->find($id);
        
        if (!$project) {
            return $this->json(['error' => 'Projet non trouvé'], 404);
        }

        $user = $this->getUser();
        if (!$user instanceof User || !$user->isProjectManager()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        if ($project->getProjectManager() !== $user) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $member = $this->userRepository->find($memberId);
        if (!$member) {
            return $this->json(['error' => 'Membre non trouvé'], 404);
        }

        $project->removeTeamMember($member);
        $this->entityManager->flush();

        return $this->json(['message' => 'Membre retiré du projet']);
    }
}
