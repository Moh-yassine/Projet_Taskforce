<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/roles')]
#[IsGranted('ROLE_USER')]
class RoleController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private PermissionService $permissionService
    ) {}

    #[Route('/assign', name: 'app_assign_role', methods: ['POST'])]
    #[IsGranted('ROLE_PROJECT_MANAGER')]
    public function assignRole(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['userId']) || !isset($data['role'])) {
            return $this->json(['message' => 'Données manquantes'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->find($data['userId']);
        if (!$user) {
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $validRoles = [
            PermissionService::ROLE_PROJECT_MANAGER,
            PermissionService::ROLE_MANAGER,
            PermissionService::ROLE_COLLABORATOR
        ];

        if (!in_array($data['role'], $validRoles)) {
            return $this->json(['message' => 'Rôle invalide'], Response::HTTP_BAD_REQUEST);
        }

        // Supprimer les anciens rôles spécifiques
        $currentRoles = $user->getRoles();
        $newRoles = array_filter($currentRoles, function($role) use ($validRoles) {
            return !in_array($role, $validRoles);
        });

        // Ajouter le nouveau rôle
        $newRoles[] = $data['role'];
        $user->setRoles($newRoles);

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Rôle assigné avec succès',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'roles' => $user->getRoles(),
                'permissions' => $this->permissionService->getUserPermissions($user)
            ]
        ]);
    }

    #[Route('/users', name: 'app_list_users', methods: ['GET'])]
    #[IsGranted('ROLE_PROJECT_MANAGER')]
    public function listUsers(): JsonResponse
    {
        $users = $this->userRepository->findAll();
        
        $usersData = array_map(function(User $user) {
            return [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'company' => $user->getCompany(),
                'roles' => $user->getRoles(),
                'primaryRole' => $user->getPrimaryRole(),
                'permissions' => $this->permissionService->getUserPermissions($user),
                'createdAt' => $user->getCreatedAt()->format('c')
            ];
        }, $users);

        return $this->json([
            'users' => $usersData
        ]);
    }

    #[Route('/permissions', name: 'app_get_permissions', methods: ['GET'])]
    public function getPermissions(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'permissions' => $this->permissionService->getUserPermissions($user)
        ]);
    }

    #[Route('/available-roles', name: 'app_available_roles', methods: ['GET'])]
    #[IsGranted('ROLE_PROJECT_MANAGER')]
    public function getAvailableRoles(): JsonResponse
    {
        return $this->json([
            'roles' => [
                [
                    'value' => PermissionService::ROLE_PROJECT_MANAGER,
                    'label' => 'Responsable de Projet',
                    'description' => 'Accès complet à toutes les fonctionnalités'
                ],
                [
                    'value' => PermissionService::ROLE_MANAGER,
                    'label' => 'Manager',
                    'description' => 'Superviseur avec accès aux tâches et rapports'
                ],
                [
                    'value' => PermissionService::ROLE_COLLABORATOR,
                    'label' => 'Collaborateur',
                    'description' => 'Accès limité aux tâches assignées'
                ]
            ]
        ]);
    }
}
