<?php

namespace App\Controller;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/webhooks', name: 'webhook_')]
class WebhookController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer)
    {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
    }

    #[Route('/stripe', name: 'stripe', methods: ['POST'])]
    public function stripeWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $data = json_decode($payload, true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $eventType = $data['type'] ?? null;
        $eventData = $data['data']['object'] ?? null;

        if (!$eventType || !$eventData) {
            return new JsonResponse(['error' => 'Invalid event data'], 400);
        }

        try {
            switch ($eventType) {
                case 'checkout.session.completed':
                    $this->handleCheckoutSessionCompleted($eventData);
                    break;
                
                case 'customer.subscription.created':
                case 'customer.subscription.updated':
                    $this->handleSubscriptionUpdated($eventData);
                    break;
                
                case 'customer.subscription.deleted':
                    $this->handleSubscriptionDeleted($eventData);
                    break;
                
                case 'invoice.payment_succeeded':
                    $this->handleInvoicePaymentSucceeded($eventData);
                    break;
                
                case 'invoice.payment_failed':
                    $this->handleInvoicePaymentFailed($eventData);
                    break;
                
                default:
                    // Log unhandled event types
                    error_log("Unhandled Stripe webhook event: {$eventType}");
            }

            return new JsonResponse(['status' => 'success']);

        } catch (\Exception $e) {
            error_log("Stripe webhook error: " . $e->getMessage());
            return new JsonResponse(['error' => 'Webhook processing failed'], 500);
        }
    }

    private function handleCheckoutSessionCompleted(array $sessionData): void
    {
        $customerEmail = $sessionData['customer_details']['email'] ?? null;
        $subscriptionId = $sessionData['subscription'] ?? null;

        if (!$customerEmail || !$subscriptionId) {
            return;
        }

        // Trouver l'utilisateur par email
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $customerEmail]);

        if (!$user) {
            error_log("User not found for email: {$customerEmail}");
            return;
        }

        // Vérifier si l'abonnement existe déjà
        $existingSubscription = $this->entityManager->getRepository(Subscription::class)
            ->findOneBy(['stripeSubscriptionId' => $subscriptionId]);

        if ($existingSubscription) {
            return;
        }

        // Créer un nouvel abonnement
        $subscription = new Subscription();
        $subscription->setUser($user);
        $subscription->setStripeSubscriptionId($subscriptionId);
        $subscription->setStripeCustomerId($sessionData['customer'] ?? null);
        $subscription->setStatus('active');
        $subscription->setPlan('premium');
        $subscription->setAmount(2999); // €29.99 en centimes
        $subscription->setCurrency('eur');
        $subscription->setCurrentPeriodStart(new \DateTimeImmutable());
        $subscription->setCurrentPeriodEnd(new \DateTimeImmutable('+1 month'));

        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        error_log("Subscription created for user: {$customerEmail}");
    }

    private function handleSubscriptionUpdated(array $subscriptionData): void
    {
        $subscriptionId = $subscriptionData['id'] ?? null;
        if (!$subscriptionId) {
            return;
        }

        $subscription = $this->entityManager->getRepository(Subscription::class)
            ->findOneBy(['stripeSubscriptionId' => $subscriptionId]);

        if (!$subscription) {
            return;
        }

        $subscription->setStatus($subscriptionData['status'] ?? 'incomplete');
        
        if (isset($subscriptionData['current_period_start'])) {
            $subscription->setCurrentPeriodStart(
                new \DateTimeImmutable('@' . $subscriptionData['current_period_start'])
            );
        }
        
        if (isset($subscriptionData['current_period_end'])) {
            $subscription->setCurrentPeriodEnd(
                new \DateTimeImmutable('@' . $subscriptionData['current_period_end'])
            );
        }

        $this->entityManager->flush();
    }

    private function handleSubscriptionDeleted(array $subscriptionData): void
    {
        $subscriptionId = $subscriptionData['id'] ?? null;
        if (!$subscriptionId) {
            return;
        }

        $subscription = $this->entityManager->getRepository(Subscription::class)
            ->findOneBy(['stripeSubscriptionId' => $subscriptionId]);

        if ($subscription) {
            $subscription->setStatus('canceled');
            $this->entityManager->flush();
        }
    }

    private function handleInvoicePaymentSucceeded(array $invoiceData): void
    {
        $subscriptionId = $invoiceData['subscription'] ?? null;
        if (!$subscriptionId) {
            return;
        }

        $subscription = $this->entityManager->getRepository(Subscription::class)
            ->findOneBy(['stripeSubscriptionId' => $subscriptionId]);

        if ($subscription) {
            $subscription->setStatus('active');
            $this->entityManager->flush();
        }
    }

    private function handleInvoicePaymentFailed(array $invoiceData): void
    {
        $subscriptionId = $invoiceData['subscription'] ?? null;
        if (!$subscriptionId) {
            return;
        }

        $subscription = $this->entityManager->getRepository(Subscription::class)
            ->findOneBy(['stripeSubscriptionId' => $subscriptionId]);

        if ($subscription) {
            $subscription->setStatus('past_due');
            $this->entityManager->flush();
        }
    }
}
