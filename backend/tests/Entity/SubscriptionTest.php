<?php

namespace App\Tests\Entity;

use App\Entity\Subscription;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class SubscriptionTest extends TestCase
{
    public function testSubscriptionCreation(): void
    {
        $subscription = new Subscription();
        
        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertNull($subscription->getId());
        $this->assertNull($subscription->getUser());
        $this->assertNull($subscription->getStripeSubscriptionId());
        $this->assertNull($subscription->getStripeCustomerId());
        $this->assertEquals('inactive', $subscription->getStatus());
        $this->assertNull($subscription->getCurrentPeriodEnd());
        $this->assertInstanceOf(\DateTimeInterface::class, $subscription->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $subscription->getUpdatedAt());
    }

    public function testSubscriptionUser(): void
    {
        $subscription = new Subscription();
        $user = new User();
        $user->setEmail('test@example.com');

        $subscription->setUser($user);
        $this->assertEquals($user, $subscription->getUser());
    }

    public function testSubscriptionStripeSubscriptionId(): void
    {
        $subscription = new Subscription();
        $stripeSubscriptionId = 'sub_1234567890';

        $subscription->setStripeSubscriptionId($stripeSubscriptionId);
        $this->assertEquals($stripeSubscriptionId, $subscription->getStripeSubscriptionId());
    }

    public function testSubscriptionStripeCustomerId(): void
    {
        $subscription = new Subscription();
        $stripeCustomerId = 'cus_1234567890';

        $subscription->setStripeCustomerId($stripeCustomerId);
        $this->assertEquals($stripeCustomerId, $subscription->getStripeCustomerId());
    }

    public function testSubscriptionStatus(): void
    {
        $subscription = new Subscription();
        $status = 'active';

        $subscription->setStatus($status);
        $this->assertEquals($status, $subscription->getStatus());
    }

    public function testSubscriptionPlan(): void
    {
        $subscription = new Subscription();
        $plan = 'premium';

        $subscription->setPlan($plan);
        $this->assertEquals($plan, $subscription->getPlan());
    }

    public function testSubscriptionCurrentPeriodStart(): void
    {
        $subscription = new Subscription();
        $currentPeriodStart = new \DateTime('2023-01-01');

        $subscription->setCurrentPeriodStart($currentPeriodStart);
        $this->assertEquals($currentPeriodStart, $subscription->getCurrentPeriodStart());
    }

    public function testSubscriptionCurrentPeriodEnd(): void
    {
        $subscription = new Subscription();
        $currentPeriodEnd = new \DateTime('2023-02-01');

        $subscription->setCurrentPeriodEnd($currentPeriodEnd);
        $this->assertEquals($currentPeriodEnd, $subscription->getCurrentPeriodEnd());
    }

    public function testSubscriptionCancelledAt(): void
    {
        $subscription = new Subscription();
        $cancelledAt = new \DateTime('2023-01-15');

        $subscription->setCancelledAt($cancelledAt);
        $this->assertEquals($cancelledAt, $subscription->getCancelledAt());
    }

    public function testSubscriptionCreatedAt(): void
    {
        $subscription = new Subscription();
        $createdAt = new \DateTime('2023-01-01 10:00:00');

        $subscription->setCreatedAt($createdAt);
        $this->assertEquals($createdAt, $subscription->getCreatedAt());
    }

    public function testSubscriptionUpdatedAt(): void
    {
        $subscription = new Subscription();
        $updatedAt = new \DateTime('2023-01-02 15:30:00');

        $subscription->setUpdatedAt($updatedAt);
        $this->assertEquals($updatedAt, $subscription->getUpdatedAt());
    }

    public function testSubscriptionIsActive(): void
    {
        $subscription = new Subscription();
        
        $subscription->setStatus('active');
        $this->assertTrue($subscription->isActive());
        
        $subscription->setStatus('inactive');
        $this->assertFalse($subscription->isActive());
        
        $subscription->setStatus('cancelled');
        $this->assertFalse($subscription->isActive());
    }

    public function testSubscriptionIsCancelled(): void
    {
        $subscription = new Subscription();
        
        $subscription->setStatus('cancelled');
        $this->assertTrue($subscription->isCancelled());
        
        $subscription->setStatus('active');
        $this->assertFalse($subscription->isCancelled());
    }

    public function testSubscriptionIsExpired(): void
    {
        $subscription = new Subscription();
        
        // Test subscription without end date
        $this->assertFalse($subscription->isExpired());
        
        // Test expired subscription
        $subscription->setCurrentPeriodEnd(new \DateTime('-1 day'));
        $this->assertTrue($subscription->isExpired());
        
        // Test non-expired subscription
        $subscription->setCurrentPeriodEnd(new \DateTime('+1 day'));
        $this->assertFalse($subscription->isExpired());
    }

    public function testSubscriptionGetDaysUntilExpiry(): void
    {
        $subscription = new Subscription();
        
        // Test subscription without end date
        $this->assertNull($subscription->getDaysUntilExpiry());
        
        // Test future expiry
        $subscription->setCurrentPeriodEnd(new \DateTime('+30 days'));
        $days = $subscription->getDaysUntilExpiry();
        $this->assertGreaterThanOrEqual(29, $days);
        $this->assertLessThanOrEqual(30, $days);
        
        // Test past expiry
        $subscription->setCurrentPeriodEnd(new \DateTime('-5 days'));
        $this->assertLessThan(0, $subscription->getDaysUntilExpiry());
    }

    public function testSubscriptionCancel(): void
    {
        $subscription = new Subscription();
        $subscription->setStatus('active');
        
        $subscription->cancel();
        
        $this->assertEquals('cancelled', $subscription->getStatus());
        $this->assertInstanceOf(\DateTimeInterface::class, $subscription->getCancelledAt());
    }

    public function testSubscriptionReactivate(): void
    {
        $subscription = new Subscription();
        $subscription->setStatus('cancelled');
        $subscription->setCancelledAt(new \DateTime());
        
        $subscription->reactivate();
        
        $this->assertEquals('active', $subscription->getStatus());
        $this->assertNull($subscription->getCancelledAt());
    }

    public function testSubscriptionUpdatePeriod(): void
    {
        $subscription = new Subscription();
        $start = new \DateTime('2023-01-01');
        $end = new \DateTime('2023-02-01');
        
        $subscription->updatePeriod($start, $end);
        
        $this->assertEquals($start, $subscription->getCurrentPeriodStart());
        $this->assertEquals($end, $subscription->getCurrentPeriodEnd());
    }

    public function testSubscriptionValidStatuses(): void
    {
        $subscription = new Subscription();
        $validStatuses = ['active', 'inactive', 'cancelled', 'past_due', 'unpaid'];

        foreach ($validStatuses as $status) {
            $subscription->setStatus($status);
            $this->assertEquals($status, $subscription->getStatus());
        }
    }

    public function testSubscriptionTrialEnd(): void
    {
        $subscription = new Subscription();
        $trialEnd = new \DateTime('2023-01-07');

        $subscription->setTrialEnd($trialEnd);
        $this->assertEquals($trialEnd, $subscription->getTrialEnd());
    }

    public function testSubscriptionIsInTrial(): void
    {
        $subscription = new Subscription();
        
        // Test without trial
        $this->assertFalse($subscription->isInTrial());
        
        // Test active trial
        $subscription->setTrialEnd(new \DateTime('+7 days'));
        $this->assertTrue($subscription->isInTrial());
        
        // Test expired trial
        $subscription->setTrialEnd(new \DateTime('-1 day'));
        $this->assertFalse($subscription->isInTrial());
    }

    public function testSubscriptionToArray(): void
    {
        $subscription = new Subscription();
        $user = new User();
        $user->setEmail('test@example.com');
        
        $subscription->setUser($user);
        $subscription->setStatus('active');
        $subscription->setPlan('premium');
        $subscription->setStripeSubscriptionId('sub_123');

        $array = $subscription->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals('active', $array['status']);
        $this->assertEquals('premium', $array['plan']);
        $this->assertEquals('sub_123', $array['stripe_subscription_id']);
    }
}
