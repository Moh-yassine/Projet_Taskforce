<?php

namespace App\Controller;

use App\Entity\UserProjectRole;
use App\Entity\Project;
use App\Repository\UserProjectRoleRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/user-project-roles')]
class UserProjectRoleController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserProjectRoleRepository $userProjectRoleRepository,
        private ProjectRepository $projectRepository
    ) {}

    #[Route('', name: 'api_user_project_roles_index', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(): JsonResponse
    {
        $user = $this->getUser();
        $userProjectRoles = $this->userProjectRoleRepository->findBy(['user' => $user]);

        $rolesData = array_map(function (UserProjectRole $userProjectRole) {
            return [
                'id' => $userProjectRole->getId(),
                'projectId' => $userProjectRole->getProject()->getId(),
                'projectName' => $userProjectRole->getProject()->getName(),
                'role' => $userProjectRole->getRole(),
                'createdAt' => $userProjectRole->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $userProjectRole->getUpdatedAt()->format('Y-m-d H:i:s')
            ];
        }, $userProjectRoles);

        return $this->json($rolesData);
    }

    #[Route('', name: 'api_user_project_roles_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        if (!$data || !isset($data['projectId']) || !isset($data['role'])) {
            return $this->json(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        $project = $this->projectRepository->find($data['projectId']);
        if (!$project) {
            return $this->json(['message' => 'Projet non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier si l'utilisateur a déjà un rôle dans ce projet
        $existingRole = $this->userProjectRoleRepository->findOneBy([
            'user' => $user,
            'project' => $project
        ]);

        if ($existingRole) {
            // Mettre à jour le rôle existant
            $existingRole->setRole($data['role']);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Rôle mis à jour avec succès',
                'userProjectRole' => [
                    'id' => $existingRole->getId(),
                    'projectId' => $existingRole->getProject()->getId(),
                    'projectName' => $existingRole->getProject()->getName(),
                    'role' => $existingRole->getRole(),
                    'updatedAt' => $existingRole->getUpdatedAt()->format('Y-m-d H:i:s')
                ]
            ]);
        } else {
            // Créer un nouveau rôle
            $userProjectRole = new UserProjectRole();
            $userProjectRole->setUser($user);
            $userProjectRole->setProject($project);
            $userProjectRole->setRole($data['role']);

            $this->entityManager->persist($userProjectRole);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Rôle attribué avec succès',
                'userProjectRole' => [
                    'id' => $userProjectRole->getId(),
                    'projectId' => $userProjectRole->getProject()->getId(),
                    'projectName' => $userProjectRole->getProject()->getName(),
                    'role' => $userProjectRole->getRole(),
                    'createdAt' => $userProjectRole->getCreatedAt()->format('Y-m-d H:i:s')
                ]
            ], Response::HTTP_CREATED);
        }
    }

    #[Route('/{id}', name: 'api_user_project_roles_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(UserProjectRole $userProjectRole): JsonResponse
    {
        $user = $this->getUser();
        
        if ($userProjectRole->getUser() !== $user) {
            return $this->json(['message' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $this->entityManager->remove($userProjectRole);
        $this->entityManager->flush();

        return $this->json(['message' => 'Rôle supprimé avec succès']);
    }
}
