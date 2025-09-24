<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/observer', name: 'observer_')]
#[IsGranted('ROLE_USER')]
class ObserverController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    #[Route('/settings', name: 'settings', methods: ['GET'])]
    public function getObserverSettings(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que l'utilisateur a un abonnement Premium actif
        if (!$user->hasActivePremiumSubscription()) {
            return new JsonResponse([
                'error' => 'Fonctionnalité Premium requise pour accéder au mode observateur',
            ], 403);
        }

        // Récupérer les paramètres d'observation (pour l'instant, des données par défaut)
        $permissions = [
            [
                'id' => 1,
                'name' => 'Modification des tâches',
                'description' => 'Empêcher la modification des tâches par les utilisateurs observés',
                'enabled' => false
            ],
            [
                'id' => 2,
                'name' => 'Création de tâches',
                'description' => 'Empêcher la création de nouvelles tâches',
                'enabled' => false
            ],
            [
                'id' => 3,
                'name' => 'Suppression de tâches',
                'description' => 'Empêcher la suppression de tâches existantes',
                'enabled' => false
            ],
            [
                'id' => 4,
                'name' => 'Modification des projets',
                'description' => 'Empêcher la modification des paramètres de projet',
                'enabled' => false
            ]
        ];

        // Récupérer tous les utilisateurs pour la sélection
        $users = $this->userRepository->findAll();
        $observedUsers = [];

        foreach ($users as $userEntity) {
            if ($userEntity->getId() !== $user->getId()) { // Exclure l'utilisateur actuel
                $observedUsers[] = [
                    'id' => $userEntity->getId(),
                    'firstName' => $userEntity->getFirstName(),
                    'lastName' => $userEntity->getLastName(),
                    'email' => $userEntity->getEmail(),
                    'isObserved' => false // Par défaut, aucun utilisateur n'est observé
                ];
            }
        }

        return new JsonResponse([
            'permissions' => $permissions,
            'observedUsers' => $observedUsers,
        ]);
    }

    #[Route('/permissions/{id}', name: 'update_permission', methods: ['PUT'])]
    public function updatePermission(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que l'utilisateur a un abonnement Premium actif
        if (!$user->hasActivePremiumSubscription()) {
            return new JsonResponse([
                'error' => 'Fonctionnalité Premium requise',
            ], 403);
        }

        $data = json_decode($request->getContent(), true);
        $enabled = $data['enabled'] ?? false;

        // Ici, vous pourriez sauvegarder les paramètres en base de données
        // Pour l'instant, on simule juste la mise à jour

        return new JsonResponse([
            'success' => true,
            'message' => 'Permission mise à jour avec succès',
        ]);
    }

    #[Route('/users/{id}', name: 'update_user_observation', methods: ['PUT'])]
    public function updateUserObservation(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que l'utilisateur a un abonnement Premium actif
        if (!$user->hasActivePremiumSubscription()) {
            return new JsonResponse([
                'error' => 'Fonctionnalité Premium requise',
            ], 403);
        }

        $data = json_decode($request->getContent(), true);
        $isObserved = $data['isObserved'] ?? false;

        // Vérifier que l'utilisateur cible existe
        $targetUser = $this->userRepository->find($id);
        if (!$targetUser) {
            return new JsonResponse([
                'error' => 'Utilisateur non trouvé',
            ], 404);
        }

        // Ici, vous pourriez sauvegarder l'état d'observation en base de données
        // Pour l'instant, on simule juste la mise à jour

        return new JsonResponse([
            'success' => true,
            'message' => $isObserved ? 'Utilisateur mis en observation' : 'Utilisateur retiré de l\'observation',
        ]);
    }

    #[Route('/users', name: 'get_observed_users', methods: ['GET'])]
    public function getObservedUsers(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que l'utilisateur a un abonnement Premium actif
        if (!$user->hasActivePremiumSubscription()) {
            return new JsonResponse([
                'error' => 'Fonctionnalité Premium requise',
            ], 403);
        }

        // Récupérer tous les utilisateurs observés
        $users = $this->userRepository->findAll();
        $observedUsers = [];

        foreach ($users as $userEntity) {
            if ($userEntity->getId() !== $user->getId()) { // Exclure l'utilisateur actuel
                $observedUsers[] = [
                    'id' => $userEntity->getId(),
                    'firstName' => $userEntity->getFirstName(),
                    'lastName' => $userEntity->getLastName(),
                    'email' => $userEntity->getEmail(),
                    'isObserved' => false // Par défaut, aucun utilisateur n'est observé
                ];
            }
        }

        return new JsonResponse($observedUsers);
    }

    #[Route('/check-permission', name: 'check_permission', methods: ['POST'])]
    public function checkUserPermission(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que l'utilisateur a un abonnement Premium actif
        if (!$user->hasActivePremiumSubscription()) {
            return new JsonResponse([
                'error' => 'Fonctionnalité Premium requise',
            ], 403);
        }

        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'] ?? null;
        $action = $data['action'] ?? null;

        if (!$userId || !$action) {
            return new JsonResponse([
                'error' => 'Paramètres manquants',
            ], 400);
        }

        // Vérifier que l'utilisateur cible existe
        $targetUser = $this->userRepository->find($userId);
        if (!$targetUser) {
            return new JsonResponse([
                'error' => 'Utilisateur non trouvé',
            ], 404);
        }

        // Ici, vous pourriez implémenter la logique de vérification des permissions
        // Pour l'instant, on simule que toutes les actions sont autorisées
        $allowed = true;
        $reason = null;

        return new JsonResponse([
            'allowed' => $allowed,
            'reason' => $reason,
        ]);
    }
}
