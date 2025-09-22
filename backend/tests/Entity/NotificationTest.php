<?php

namespace App\Tests\Entity;

use App\Entity\Notification;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class NotificationTest extends TestCase
{
    public function testNotificationCreation(): void
    {
        $notification = new Notification();
        
        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertNull($notification->getId());
        $this->assertNull($notification->getUser());
        $this->assertNull($notification->getTitle());
        $this->assertNull($notification->getMessage());
        $this->assertEquals('info', $notification->getType());
        $this->assertFalse($notification->isRead());
        $this->assertInstanceOf(\DateTimeInterface::class, $notification->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $notification->getUpdatedAt());
    }

    public function testNotificationUser(): void
    {
        $notification = new Notification();
        $user = new User();
        $user->setEmail('test@example.com');

        $notification->setUser($user);
        $this->assertEquals($user, $notification->getUser());
    }

    public function testNotificationTitle(): void
    {
        $notification = new Notification();
        $title = 'Test Notification';

        $notification->setTitle($title);
        $this->assertEquals($title, $notification->getTitle());
    }

    public function testNotificationMessage(): void
    {
        $notification = new Notification();
        $message = 'This is a test notification message';

        $notification->setMessage($message);
        $this->assertEquals($message, $notification->getMessage());
    }

    public function testNotificationType(): void
    {
        $notification = new Notification();
        $type = 'warning';

        $notification->setType($type);
        $this->assertEquals($type, $notification->getType());
    }

    public function testNotificationReadStatus(): void
    {
        $notification = new Notification();

        $this->assertFalse($notification->isRead());
        
        $notification->setRead(true);
        $this->assertTrue($notification->isRead());
        
        $notification->setRead(false);
        $this->assertFalse($notification->isRead());
    }

    public function testNotificationCreatedAt(): void
    {
        $notification = new Notification();
        $createdAt = new \DateTime('2023-01-01 10:00:00');

        $notification->setCreatedAt($createdAt);
        $this->assertEquals($createdAt, $notification->getCreatedAt());
    }

    public function testNotificationUpdatedAt(): void
    {
        $notification = new Notification();
        $updatedAt = new \DateTime('2023-01-02 15:30:00');

        $notification->setUpdatedAt($updatedAt);
        $this->assertEquals($updatedAt, $notification->getUpdatedAt());
    }

    public function testNotificationMarkAsRead(): void
    {
        $notification = new Notification();
        
        $this->assertFalse($notification->isRead());
        
        $notification->markAsRead();
        $this->assertTrue($notification->isRead());
    }

    public function testNotificationMarkAsUnread(): void
    {
        $notification = new Notification();
        $notification->setRead(true);
        
        $this->assertTrue($notification->isRead());
        
        $notification->markAsUnread();
        $this->assertFalse($notification->isRead());
    }

    public function testNotificationToggleReadStatus(): void
    {
        $notification = new Notification();
        
        $this->assertFalse($notification->isRead());
        
        $notification->toggleReadStatus();
        $this->assertTrue($notification->isRead());
        
        $notification->toggleReadStatus();
        $this->assertFalse($notification->isRead());
    }

    public function testNotificationValidTypes(): void
    {
        $notification = new Notification();
        $validTypes = ['info', 'success', 'warning', 'error', 'alert'];

        foreach ($validTypes as $type) {
            $notification->setType($type);
            $this->assertEquals($type, $notification->getType());
        }
    }

    public function testNotificationPriority(): void
    {
        $notification = new Notification();
        $priority = 'high';

        $notification->setPriority($priority);
        $this->assertEquals($priority, $notification->getPriority());
    }

    public function testNotificationExpiresAt(): void
    {
        $notification = new Notification();
        $expiresAt = new \DateTime('2023-12-31 23:59:59');

        $notification->setExpiresAt($expiresAt);
        $this->assertEquals($expiresAt, $notification->getExpiresAt());
    }

    public function testNotificationIsExpired(): void
    {
        $notification = new Notification();
        
        // Test non-expiring notification
        $this->assertFalse($notification->isExpired());
        
        // Test expired notification
        $notification->setExpiresAt(new \DateTime('-1 day'));
        $this->assertTrue($notification->isExpired());
        
        // Test non-expired notification
        $notification->setExpiresAt(new \DateTime('+1 day'));
        $this->assertFalse($notification->isExpired());
    }

    public function testNotificationMetadata(): void
    {
        $notification = new Notification();
        $metadata = ['action' => 'task_assigned', 'task_id' => 123];

        $notification->setMetadata($metadata);
        $this->assertEquals($metadata, $notification->getMetadata());
    }

    public function testNotificationChannel(): void
    {
        $notification = new Notification();
        $channel = 'email';

        $notification->setChannel($channel);
        $this->assertEquals($channel, $notification->getChannel());
    }

    public function testNotificationToArray(): void
    {
        $notification = new Notification();
        $user = new User();
        $user->setEmail('test@example.com');
        
        $notification->setUser($user);
        $notification->setTitle('Test Title');
        $notification->setMessage('Test Message');
        $notification->setType('info');
        $notification->setRead(true);

        $array = $notification->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals('Test Title', $array['title']);
        $this->assertEquals('Test Message', $array['message']);
        $this->assertEquals('info', $array['type']);
        $this->assertTrue($array['read']);
    }
}
