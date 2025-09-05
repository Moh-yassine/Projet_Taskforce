<?php

namespace App\Controller;

use App\Entity\Project;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/projects')]
class ProjectController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProjectRepository $projectRepository,
        private UserRepository $userRepository,
        private ValidatorInterface $validator
    ) {}

    #[Route('', name: 'api_projects_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        // Temporairement, utilisons l'utilisateur avec l'ID 11 (dashboard@example.com)
        $user = $this->userRepository->find(11);
        $projects = $this->projectRepository->findBy(['projectManager' => $user]);

        $projectsData = array_map(function (Project $project) {
            return [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'description' => $project->getDescription(),
                'status' => $project->getStatus(),
                'createdAt' => $project->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $project->getUpdatedAt()->format('Y-m-d H:i:s'),
                'projectManager' => [
                    'id' => $project->getProjectManager()->getId(),
                    'firstName' => $project->getProjectManager()->getFirstName(),
                    'lastName' => $project->getProjectManager()->getLastName(),
                    'email' => $project->getProjectManager()->getEmail()
                ],
                'teamMembers' => $project->getTeamMembers()->map(function ($member) {
                    return [
                        'id' => $member->getId(),
                        'firstName' => $member->getFirstName(),
                        'lastName' => $member->getLastName(),
                        'email' => $member->getEmail()
                    ];
                })->toArray()
            ];
        }, $projects);

        return $this->json($projectsData);
    }

    #[Route('', name: 'api_projects_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        // Temporairement, utilisons l'utilisateur avec l'ID 11 (dashboard@example.com)
        $user = $this->userRepository->find(11);

        if (!$data) {
            return $this->json(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        $requiredFields = ['name'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->json(['message' => "Le champ '$field' est requis"], Response::HTTP_BAD_REQUEST);
            }
        }

        $project = new Project();
        $project->setName($data['name']);
        $project->setDescription($data['description'] ?? '');
        $project->setStatus($data['status'] ?? 'planning');
        $project->setProjectManager($user);
        
        // Définir des dates par défaut
        $project->setStartDate(new \DateTime());
        $project->setEndDate((new \DateTime())->modify('+1 month'));

        $errors = $this->validator->validate($project);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['message' => 'Erreur de validation: ' . implode(', ', $errorMessages)], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Projet créé avec succès',
            'project' => [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'description' => $project->getDescription(),
                'status' => $project->getStatus(),
                'createdAt' => $project->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $project->getUpdatedAt()->format('Y-m-d H:i:s'),
                'projectManager' => [
                    'id' => $project->getProjectManager()->getId(),
                    'firstName' => $project->getProjectManager()->getFirstName(),
                    'lastName' => $project->getProjectManager()->getLastName(),
                    'email' => $project->getProjectManager()->getEmail()
                ]
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_projects_show', methods: ['GET'])]
    public function show(Project $project): JsonResponse
    {
        // Temporairement, permettre l'accès à tous les projets

        return $this->json([
            'id' => $project->getId(),
            'name' => $project->getName(),
            'description' => $project->getDescription(),
            'status' => $project->getStatus(),
            'createdAt' => $project->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $project->getUpdatedAt()->format('Y-m-d H:i:s'),
            'projectManager' => [
                'id' => $project->getProjectManager()->getId(),
                'firstName' => $project->getProjectManager()->getFirstName(),
                'lastName' => $project->getProjectManager()->getLastName(),
                'email' => $project->getProjectManager()->getEmail()
            ],
            'teamMembers' => $project->getTeamMembers()->map(function ($member) {
                return [
                    'id' => $member->getId(),
                    'firstName' => $member->getFirstName(),
                    'lastName' => $member->getLastName(),
                    'email' => $member->getEmail()
                ];
            })->toArray()
        ]);
    }

    #[Route('/{id}', name: 'api_projects_update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(Request $request, Project $project): JsonResponse
    {
        $user = $this->getUser();
        
        if ($project->getProjectManager() !== $user) {
            return $this->json(['message' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
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

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Projet mis à jour avec succès',
            'project' => [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'description' => $project->getDescription(),
                'status' => $project->getStatus(),
                'updatedAt' => $project->getUpdatedAt()->format('Y-m-d H:i:s')
            ]
        ]);
    }

    #[Route('/{id}', name: 'api_projects_delete', methods: ['DELETE'])]
    // #[IsGranted('ROLE_USER')] // Temporairement désactivé
    public function delete(Project $project): JsonResponse
    {
        // Temporairement, permettre la suppression de tous les projets
        // $user = $this->getUser();
        // 
        // if ($project->getProjectManager() !== $user) {
        //     return $this->json(['message' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        // }

        $this->entityManager->remove($project);
        $this->entityManager->flush();

        return $this->json(['message' => 'Projet supprimé avec succès']);
    }
}
