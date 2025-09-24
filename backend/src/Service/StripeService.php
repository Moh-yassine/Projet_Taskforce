<?php

namespace App\Service;

use App\Entity\Subscription;
use App\Entity\User;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Subscription as StripeSubscription;
use Stripe\Price;
use Stripe\Product;
use Stripe\PaymentIntent;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;

class StripeService
{
    private EntityManagerInterface $entityManager;
    private SubscriptionRepository $subscriptionRepository;
    private string $stripeSecretKey;
    private string $stripePublicKey;

    public function __construct(
        EntityManagerInterface $entityManager,
        SubscriptionRepository $subscriptionRepository,
        string $stripeSecretKey = null,
        string $stripePublicKey = null
    ) {
        $this->entityManager = $entityManager;
        $this->subscriptionRepository = $subscriptionRepository;
        
        // Utiliser vos clés Stripe directement pour le test
        $this->stripeSecretKey = $stripeSecretKey ?: 'sk_test_51S5oNDRyNP7K69mNIagsNjPlXg9Ndd1101C7pRjH3mIjQIfa2UnswStmLaBlRZhzlBOJEydjQcWym6p8aVpH4kxf00mcwmHhCC';
        $this->stripePublicKey = $stripePublicKey ?: 'pk_test_51S5oNDRyNP7K69mNAktlFsF76GumOdNPlggTFso5XPKeLbRB842U6wysSFXJlSLPnQJCW65RpOv5wc3jl8ULElT000mLHgVg1T';
        
        Stripe::setApiKey($this->stripeSecretKey);
    }

    public function getPublicKey(): string
    {
        return $this->stripePublicKey;
    }

    /**
     * Create or retrieve a Stripe customer for a user
     */
    public function createOrGetCustomer(User $user): Customer
    {
        // Check if user already has a subscription with a customer ID
        $existingSubscription = $this->subscriptionRepository->findOneBy(['user' => $user]);
        
        if ($existingSubscription && $existingSubscription->getStripeCustomerId()) {
            try {
                return Customer::retrieve($existingSubscription->getStripeCustomerId());
            } catch (ApiErrorException $e) {
                // Customer doesn't exist in Stripe, create a new one
            }
        }

        // Create new customer
        $customer = Customer::create([
            'email' => $user->getEmail(),
            'name' => $user->getFullName(),
            'metadata' => [
                'user_id' => $user->getId(),
            ],
        ]);

        return $customer;
    }

    /**
     * Create a premium subscription for a user
     */
    public function createPremiumSubscription(User $user, string $paymentMethodId): array
    {
        try {
            // Create or get customer
            $customer = $this->createOrGetCustomer($user);

            // Attach payment method to customer
            $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach(['customer' => $customer->id]);

            // Set as default payment method
            Customer::update($customer->id, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethodId,
                ],
            ]);

            // Create or get the premium product and price
            $product = $this->getOrCreatePremiumProduct();
            $price = $this->getOrCreatePremiumPrice($product->id);

            // Create subscription
            $stripeSubscription = StripeSubscription::create([
                'customer' => $customer->id,
                'items' => [
                    [
                        'price' => $price->id,
                    ],
                ],
                'payment_behavior' => 'default_incomplete',
                'payment_settings' => ['save_default_payment_method' => 'on_subscription'],
                'expand' => ['latest_invoice.payment_intent'],
            ]);

            // Save subscription to database
            $subscription = new Subscription();
            $subscription->setUser($user);
            $subscription->setStripeSubscriptionId($stripeSubscription->id);
            $subscription->setStripeCustomerId($customer->id);
            $subscription->setStatus($stripeSubscription->status);
            $subscription->setPlan('premium');
            $subscription->setAmount(2999); // $29.99 in cents
            $subscription->setCurrency('eur');
            $subscription->setCurrentPeriodStart(new \DateTimeImmutable('@' . ($stripeSubscription->current_period_start ?? time())));
            $subscription->setCurrentPeriodEnd(new \DateTimeImmutable('@' . ($stripeSubscription->current_period_end ?? time() + 30 * 24 * 60 * 60)));

            $this->entityManager->persist($subscription);
            $this->entityManager->flush();

            $clientSecret = null;
            if ($stripeSubscription->latest_invoice && 
                $stripeSubscription->latest_invoice->payment_intent && 
                $stripeSubscription->latest_invoice->payment_intent->client_secret) {
                $clientSecret = $stripeSubscription->latest_invoice->payment_intent->client_secret;
            }

            return [
                'success' => true,
                'subscription_id' => $stripeSubscription->id,
                'client_secret' => $clientSecret,
                'status' => $stripeSubscription->status,
            ];

        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => 'Erreur Stripe: ' . $e->getMessage(),
                'stripe_error_type' => $e->getStripeCode(),
                'stripe_error_code' => method_exists($e, 'getDeclineCode') ? $e->getDeclineCode() : null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur générale: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];
        }
    }

    /**
     * Get or create the premium product
     */
    private function getOrCreatePremiumProduct(): Product
    {
        try {
            // Try to find existing product
            $products = Product::all(['limit' => 100]);
            foreach ($products->data as $product) {
                if ($product->name === 'TaskForce Premium') {
                    return $product;
                }
            }

            // Create new product
            return Product::create([
                'name' => 'TaskForce Premium',
                'description' => 'Accès aux fonctionnalités premium de TaskForce avec mode observateur',
                'type' => 'service',
            ]);
        } catch (ApiErrorException $e) {
            throw new \Exception('Erreur lors de la création/récupération du produit: ' . $e->getMessage());
        }
    }

    /**
     * Get or create the premium price
     */
    private function getOrCreatePremiumPrice(string $productId): Price
    {
        try {
            // Try to find existing price
            $prices = Price::all(['product' => $productId, 'limit' => 100]);
            foreach ($prices->data as $price) {
                if ($price->unit_amount === 2999 && $price->currency === 'eur' && $price->recurring) {
                    return $price;
                }
            }

            // Create new price
            return Price::create([
                'product' => $productId,
                'unit_amount' => 2999, // €29.99
                'currency' => 'eur',
                'recurring' => [
                    'interval' => 'month',
                ],
            ]);
        } catch (ApiErrorException $e) {
            throw new \Exception('Erreur lors de la création/récupération du prix: ' . $e->getMessage());
        }
    }

    /**
     * Handle webhook events from Stripe
     */
    public function handleWebhook(string $payload, string $signature): array
    {
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                $_ENV['STRIPE_WEBHOOK_SECRET'] ?? ''
            );

            switch ($event->type) {
                case 'customer.subscription.updated':
                case 'customer.subscription.deleted':
                    $this->handleSubscriptionUpdate($event->data->object);
                    break;
                case 'invoice.payment_succeeded':
                    $this->handlePaymentSucceeded($event->data->object);
                    break;
                case 'invoice.payment_failed':
                    $this->handlePaymentFailed($event->data->object);
                    break;
            }

            return ['success' => true];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle subscription updates
     */
    private function handleSubscriptionUpdate(StripeSubscription $stripeSubscription): void
    {
        $subscription = $this->subscriptionRepository->findByStripeSubscriptionId($stripeSubscription->id);
        
        if ($subscription) {
            $subscription->setStatus($stripeSubscription->status);
            $subscription->setCurrentPeriodStart(new \DateTimeImmutable('@' . ($stripeSubscription->current_period_start ?? time())));
            $subscription->setCurrentPeriodEnd(new \DateTimeImmutable('@' . ($stripeSubscription->current_period_end ?? time() + 30 * 24 * 60 * 60)));
            
            $this->entityManager->flush();
        }
    }

    /**
     * Handle successful payment
     */
    private function handlePaymentSucceeded($invoice): void
    {
        if ($invoice->subscription) {
            $subscription = $this->subscriptionRepository->findByStripeSubscriptionId($invoice->subscription);
            if ($subscription) {
                $subscription->setStatus('active');
                $this->entityManager->flush();
            }
        }
    }

    /**
     * Handle failed payment
     */
    private function handlePaymentFailed($invoice): void
    {
        if ($invoice->subscription) {
            $subscription = $this->subscriptionRepository->findByStripeSubscriptionId($invoice->subscription);
            if ($subscription) {
                $subscription->setStatus('past_due');
                $this->entityManager->flush();
            }
        }
    }

    /**
     * Create a Stripe Checkout session for premium subscription
     */
    public function createCheckoutSession(User $user): array
    {
        try {
            // Create or get customer
            $customer = $this->createOrGetCustomer($user);

            // Create checkout session
            $session = Session::create([
                'customer' => $customer->id,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'TaskForce Premium',
                            'description' => 'Abonnement Premium TaskForce avec fonctionnalités avancées et mode observateur',
                        ],
                        'unit_amount' => 2999, // 29.99€ en centimes
                        'recurring' => [
                            'interval' => 'month',
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => 'http://localhost:5173/dashboard?premium=success',
                'cancel_url' => 'http://localhost:5173/payment?cancelled=true',
                'metadata' => [
                    'user_id' => $user->getId(),
                ],
            ]);

            return [
                'success' => true,
                'checkout_url' => $session->url,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de la création de la session de paiement: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(string $subscriptionId): array
    {
        try {
            $stripeSubscription = StripeSubscription::retrieve($subscriptionId);
            $stripeSubscription->cancel();

            $subscription = $this->subscriptionRepository->findByStripeSubscriptionId($subscriptionId);
            if ($subscription) {
                $subscription->setStatus('canceled');
                $this->entityManager->flush();
            }

            return ['success' => true];

        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get subscription details
     */
    public function getSubscriptionDetails(string $subscriptionId): ?array
    {
        try {
            $stripeSubscription = StripeSubscription::retrieve($subscriptionId);
            
            return [
                'id' => $stripeSubscription->id,
                'status' => $stripeSubscription->status,
                'current_period_start' => $stripeSubscription->current_period_start,
                'current_period_end' => $stripeSubscription->current_period_end,
                'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end,
            ];

        } catch (ApiErrorException $e) {
            return null;
        }
    }
}

