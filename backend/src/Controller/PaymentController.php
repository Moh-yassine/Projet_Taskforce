<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/payment', name: 'payment_')]
#[IsGranted('ROLE_USER')]
class PaymentController extends AbstractController
{
    private StripeService $stripeService;
    private SerializerInterface $serializer;
    private EntityManagerInterface $entityManager;

    public function __construct(StripeService $stripeService, SerializerInterface $serializer, EntityManagerInterface $entityManager)
    {
        $this->stripeService = $stripeService;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
    }

    #[Route('/config', name: 'config', methods: ['GET'])]
    public function getConfig(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier directement dans la base de données
        $subscription = $this->entityManager->getRepository(\App\Entity\Subscription::class)
            ->findOneBy(['user' => $user, 'status' => 'active', 'plan' => 'premium']);

        $hasActiveSubscription = $subscription !== null;

        return new JsonResponse([
            'publishableKey' => $this->stripeService->getPublicKey(),
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getFullName(),
            ],
            'hasActiveSubscription' => $hasActiveSubscription,
        ]);
    }

    #[Route('/create-subscription', name: 'create_subscription', methods: ['POST'])]
    public function createSubscription(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Check if user already has an active premium subscription
        $existingSubscription = $this->entityManager->getRepository(\App\Entity\Subscription::class)
            ->findOneBy(['user' => $user, 'status' => 'active', 'plan' => 'premium']);
            
        if ($existingSubscription) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Vous avez déjà un abonnement premium actif.',
            ], 400);
        }

        try {
            // Créer une session Stripe Checkout
            $result = $this->stripeService->createCheckoutSession($user);

            if ($result['success']) {
                return new JsonResponse([
                    'success' => true,
                    'checkout_url' => $result['checkout_url'],
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'error' => $result['error'],
                ], 400);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur serveur: ' . $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/subscription-status', name: 'subscription_status', methods: ['GET'])]
    public function getSubscriptionStatus(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Utiliser directement le repository pour trouver l'abonnement actif
        $subscription = $this->entityManager->getRepository(\App\Entity\Subscription::class)
            ->findOneBy(['user' => $user, 'status' => 'active', 'plan' => 'premium']);
        
        if (!$subscription) {
            return new JsonResponse([
                'hasActiveSubscription' => false,
                'subscription' => null,
            ]);
        }

        return new JsonResponse([
            'hasActiveSubscription' => true,
            'subscription' => [
                'id' => $subscription->getId(),
                'status' => $subscription->getStatus(),
                'plan' => $subscription->getPlan(),
                'amount' => $subscription->getAmount(),
                'currency' => $subscription->getCurrency(),
                'currentPeriodStart' => $subscription->getCurrentPeriodStart()->format('Y-m-d H:i:s'),
                'currentPeriodEnd' => $subscription->getCurrentPeriodEnd()->format('Y-m-d H:i:s'),
                'stripeSubscriptionId' => $subscription->getStripeSubscriptionId(),
            ],
        ]);
    }

    #[Route('/cancel-subscription', name: 'cancel_subscription', methods: ['POST'])]
    public function cancelSubscription(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $subscription = $user->getActivePremiumSubscription();
        
        if (!$subscription) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Aucun abonnement actif trouvé.',
            ], 400);
        }

        $result = $this->stripeService->cancelSubscription($subscription->getStripeSubscriptionId());

        if ($result['success']) {
            return new JsonResponse([
                'success' => true,
                'message' => 'Abonnement annulé avec succès.',
            ]);
        } else {
            return new JsonResponse([
                'success' => false,
                'error' => $result['error'],
            ], 400);
        }
    }

    #[Route('/webhook', name: 'webhook', methods: ['POST'])]
    public function handleWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->headers->get('stripe-signature');

        if (!$signature) {
            return new JsonResponse(['error' => 'Signature manquante'], 400);
        }

        $result = $this->stripeService->handleWebhook($payload, $signature);

        if ($result['success']) {
            return new JsonResponse(['status' => 'success']);
        } else {
            return new JsonResponse(['error' => $result['error']], 400);
        }
    }


    #[Route('/premium-features', name: 'premium_features', methods: ['GET'])]
    public function getPremiumFeatures(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier directement dans la base de données
        $subscription = $this->entityManager->getRepository(\App\Entity\Subscription::class)
            ->findOneBy(['user' => $user, 'status' => 'active', 'plan' => 'premium']);

        if (!$subscription) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Abonnement premium requis pour accéder à ces fonctionnalités.',
            ], 403);
        }

        // Return premium features data
        return new JsonResponse([
            'success' => true,
            'features' => [
                'advanced_reports' => [
                    'name' => 'Rapports Avancés',
                    'description' => 'Accès à des rapports détaillés et des analyses avancées',
                    'enabled' => true,
                ],
                'priority_support' => [
                    'name' => 'Support Prioritaire',
                    'description' => 'Support client prioritaire avec réponse garantie sous 24h',
                    'enabled' => true,
                ],
                'unlimited_projects' => [
                    'name' => 'Projets Illimités',
                    'description' => 'Création de projets sans limite',
                    'enabled' => true,
                ],
                'advanced_analytics' => [
                    'name' => 'Analyses Avancées',
                    'description' => 'Tableaux de bord personnalisés et métriques détaillées',
                    'enabled' => true,
                ],
                'api_access' => [
                    'name' => 'Accès API',
                    'description' => 'Accès à l\'API REST pour intégrations tierces',
                    'enabled' => true,
                ],
                'custom_branding' => [
                    'name' => 'Personnalisation',
                    'description' => 'Personnalisation de l\'interface avec votre marque',
                    'enabled' => true,
                ],
                'observer_mode' => [
                    'name' => 'Mode Observateur',
                    'description' => 'Limitez les actions de certains utilisateurs sur vos tableaux',
                    'enabled' => true,
                ],
            ],
        ]);
    }
}

