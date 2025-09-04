<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Skill;
use App\Repository\UserRepository;
use App\Repository\SkillRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api/users')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private SkillRepository $skillRepository,
        private ValidatorInterface $validator,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof User || !$user->isProjectManager()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $role = $request->query->get('role');
        $search = $request->query->get('search');

        if ($search) {
            $users = $this->userRepository->searchUsers($search);
        } elseif ($role) {
            $users = $this->userRepository->findByRole($role);
        } else {
            $users = $this->userRepository->findAll();
        }

        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'fullName' => $user->getFullName(),
                'email' => $user->getEmail(),
                'company' => $user->getCompany(),
                'roles' => $user->getRoles(),
                'skills' => array_map(fn($skill) => [
                    'id' => $skill->getId(),
                    'name' => $skill->getName(),
                    'category' => $skill->getCategory()
                ], $user->getSkills()->toArray()),
                'skillCount' => $user->getSkills()->count(),
                'assignedTaskCount' => $user->getAssignedTasks()->count(),
                'createdAt' => $user->getCreatedAt()->format('Y-m-d')
            ];
        }

        return $this->json($data);
    }

    #[Route('/me', methods: ['GET'])]
    public function getCurrentUser(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof User) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        $data = [
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'fullName' => $user->getFullName(),
            'email' => $user->getEmail(),
            'company' => $user->getCompany(),
            'roles' => $user->getRoles(),
            'isProjectManager' => $user->isProjectManager(),
            'isManager' => $user->isManager(),
            'isCollaborator' => $user->isCollaborator(),
            'skills' => array_map(fn($skill) => [
                'id' => $skill->getId(),
                'name' => $skill->getName(),
                'category' => $skill->getCategory()
            ], $user->getSkills()->toArray()),
            'managedProjects' => array_map(fn($project) => [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'status' => $project->getStatus()
            ], $user->getManagedProjects()->toArray()),
            'assignedProjects' => array_map(fn($project) => [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'status' => $project->getStatus()
            ], $user->getAssignedProjects()->toArray()),
            'assignedTasks' => array_map(fn($task) => [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'status' => $task->getStatus(),
                'priority' => $task->getPriority(),
                'dueDate' => $task->getDueDate()->format('Y-m-d')
            ], $user->getAssignedTasks()->toArray()),
            'createdAt' => $user->getCreatedAt()->format('Y-m-d')
        ];

        return $this->json($data);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $targetUser = $this->userRepository->find($id);
        
        if (!$targetUser) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        if (!$user->isProjectManager() && $user->getId() !== $targetUser->getId()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $data = [
            'id' => $targetUser->getId(),
            'firstName' => $targetUser->getFirstName(),
            'lastName' => $targetUser->getLastName(),
            'fullName' => $targetUser->getFullName(),
            'email' => $targetUser->getEmail(),
            'company' => $targetUser->getCompany(),
            'roles' => $targetUser->getRoles(),
            'skills' => array_map(fn($skill) => [
                'id' => $skill->getId(),
                'name' => $skill->getName(),
                'category' => $skill->getCategory(),
                'description' => $skill->getDescription()
            ], $targetUser->getSkills()->toArray()),
            'assignedTasks' => array_map(fn($task) => [
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'status' => $task->getStatus(),
                'priority' => $task->getPriority(),
                'dueDate' => $task->getDueDate()->format('Y-m-d'),
                'project' => [
                    'id' => $task->getProject()->getId(),
                    'name' => $task->getProject()->getName()
                ]
            ], $targetUser->getAssignedTasks()->toArray()),
            'createdAt' => $targetUser->getCreatedAt()->format('Y-m-d')
        ];

        return $this->json($data);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $targetUser = $this->userRepository->find($id);
        
        if (!$targetUser) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        if (!$user->isProjectManager() && $user->getId() !== $targetUser->getId()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['firstName'])) {
            $targetUser->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $targetUser->setLastName($data['lastName']);
        }
        if (isset($data['company'])) {
            $targetUser->setCompany($data['company']);
        }

        if (isset($data['roles']) && $user->isProjectManager()) {
            $targetUser->setRoles($data['roles']);
        }

        if (isset($data['password'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($targetUser, $data['password']);
            $targetUser->setPassword($hashedPassword);
        }

        $targetUser->setUpdatedAt(new \DateTimeImmutable());

        $errors = $this->validator->validate($targetUser);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], 400);
        }

        $this->entityManager->flush();

        return $this->json(['message' => 'Utilisateur mis à jour avec succès']);
    }

    #[Route('/{id}/skills', methods: ['GET'])]
    public function getUserSkills(int $id): JsonResponse
    {
        $targetUser = $this->userRepository->find($id);
        
        if (!$targetUser) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        if (!$user->isProjectManager() && $user->getId() !== $targetUser->getId()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $skills = $targetUser->getSkills();
        $data = [];
        
        foreach ($skills as $skill) {
            $data[] = [
                'id' => $skill->getId(),
                'name' => $skill->getName(),
                'description' => $skill->getDescription(),
                'category' => $skill->getCategory()
            ];
        }

        return $this->json($data);
    }

    #[Route('/{id}/skills', methods: ['POST'])]
    public function addSkillToUser(int $id, Request $request): JsonResponse
    {
        $targetUser = $this->userRepository->find($id);
        
        if (!$targetUser) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $user = $this->getUser();
        if (!$user instanceof User || !$user->isProjectManager()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $skillId = $data['skillId'];

        $skill = $this->skillRepository->find($skillId);
        if (!$skill) {
            return $this->json(['error' => 'Compétence non trouvée'], 404);
        }

        if ($targetUser->hasSkill($skill)) {
            return $this->json(['error' => 'L\'utilisateur possède déjà cette compétence'], 400);
        }

        $targetUser->addSkill($skill);
        $this->entityManager->flush();

        return $this->json(['message' => 'Compétence ajoutée à l\'utilisateur']);
    }

    #[Route('/{id}/skills/{skillId}', methods: ['DELETE'])]
    public function removeSkillFromUser(int $id, int $skillId): JsonResponse
    {
        $targetUser = $this->userRepository->find($id);
        
        if (!$targetUser) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $user = $this->getUser();
        if (!$user instanceof User || !$user->isProjectManager()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $skill = $this->skillRepository->find($skillId);
        if (!$skill) {
            return $this->json(['error' => 'Compétence non trouvée'], 404);
        }

        if (!$targetUser->hasSkill($skill)) {
            return $this->json(['error' => 'L\'utilisateur ne possède pas cette compétence'], 400);
        }

        $targetUser->removeSkill($skill);
        $this->entityManager->flush();

        return $this->json(['message' => 'Compétence retirée de l\'utilisateur']);
    }

    #[Route('/{id}/workload', methods: ['GET'])]
    public function getUserWorkload(int $id): JsonResponse
    {
        $targetUser = $this->userRepository->find($id);
        
        if (!$targetUser) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        if (!$user->isProjectManager() && $user->getId() !== $targetUser->getId()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $assignedTasks = $targetUser->getAssignedTasks();
        $totalEstimatedHours = 0;
        $totalActualHours = 0;
        $overdueTasks = 0;
        $dueSoonTasks = 0;

        foreach ($assignedTasks as $task) {
            $totalEstimatedHours += $task->getEstimatedHours();
            $totalActualHours += $task->getActualHours();
            
            if ($task->getStatus() !== 'completed') {
                $dueDate = $task->getDueDate();
                $now = new \DateTimeImmutable();
                
                if ($dueDate < $now) {
                    $overdueTasks++;
                } elseif ($dueDate->diff($now)->days <= 3) {
                    $dueSoonTasks++;
                }
            }
        }

        $data = [
            'userId' => $targetUser->getId(),
            'userName' => $targetUser->getFullName(),
            'totalTasks' => $assignedTasks->count(),
            'totalEstimatedHours' => $totalEstimatedHours,
            'totalActualHours' => $totalActualHours,
            'overdueTasks' => $overdueTasks,
            'dueSoonTasks' => $dueSoonTasks,
            'workloadPercentage' => $totalEstimatedHours > 0 ? ($totalActualHours / $totalEstimatedHours) * 100 : 0
        ];

        return $this->json($data);
    }

    #[Route('/available', methods: ['GET'])]
    public function getAvailableUsers(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user instanceof User || !$user->isProjectManager()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $requiredSkills = $request->query->get('skills', '');
        $maxWorkload = $request->query->get('maxWorkload', 40);

        if ($requiredSkills) {
            $skillIds = explode(',', $requiredSkills);
            $users = $this->userRepository->findUsersWithSkills($skillIds);
        } else {
            $users = $this->userRepository->findAll();
        }

        $availableUsers = [];
        foreach ($users as $user) {
            $assignedTasks = $user->getAssignedTasks();
            $totalEstimatedHours = 0;
            
            foreach ($assignedTasks as $task) {
                if ($task->getStatus() !== 'completed') {
                    $totalEstimatedHours += $task->getEstimatedHours();
                }
            }

            if ($totalEstimatedHours <= $maxWorkload) {
                $availableUsers[] = [
                    'id' => $user->getId(),
                    'name' => $user->getFullName(),
                    'email' => $user->getEmail(),
                    'currentWorkload' => $totalEstimatedHours,
                    'availableHours' => $maxWorkload - $totalEstimatedHours,
                    'skills' => array_map(fn($skill) => [
                        'id' => $skill->getId(),
                        'name' => $skill->getName(),
                        'category' => $skill->getCategory()
                    ], $user->getSkills()->toArray())
                ];
            }
        }

        usort($availableUsers, function($a, $b) {
            return $b['availableHours'] <=> $a['availableHours'];
        });

        return $this->json($availableUsers);
    }
}
