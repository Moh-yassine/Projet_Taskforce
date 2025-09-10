<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/notifications')]
final class NotificationController extends AbstractController
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'notifications_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $notifications = $this->notificationRepository->findAll();
        
        return $this->json($notifications, 200, [], ['groups' => ['notification:read']]);
    }

    #[Route('/my-notifications', name: 'my_notifications', methods: ['GET'])]
    public function getMyNotifications(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $notifications = $this->notificationRepository->findBy(
            ['user' => $user], 
            ['createdAt' => 'DESC']
        );
        
        return $this->json($notifications, 200, [], ['groups' => ['notification:read']]);
    }

    #[Route('/user/{userId}', name: 'notifications_by_user', methods: ['GET'])]
    public function getByUser(int $userId): JsonResponse
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $notifications = $this->notificationRepository->findBy(
            ['user' => $user], 
            ['createdAt' => 'DESC']
        );
        
        return $this->json($notifications, 200, [], ['groups' => ['notification:read']]);
    }

    #[Route('/user/{userId}/unread', name: 'notifications_unread', methods: ['GET'])]
    public function getUnreadByUser(int $userId): JsonResponse
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $notifications = $this->notificationRepository->findBy([
            'user' => $user,
            'isRead' => false
        ], ['createdAt' => 'DESC']);
        
        return $this->json($notifications, 200, [], ['groups' => ['notification:read']]);
    }

    #[Route('/user/{userId}/count', name: 'notifications_count', methods: ['GET'])]
    public function getUnreadCount(int $userId): JsonResponse
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $count = $this->notificationRepository->count([
            'user' => $user,
            'isRead' => false
        ]);
        
        return $this->json(['count' => $count]);
    }

    #[Route('', name: 'notification_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $user = $this->userRepository->find($data['userId'] ?? null);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $notification = new Notification();
        $notification->setUser($user);
        $notification->setTitle($data['title'] ?? '');
        $notification->setMessage($data['message'] ?? '');
        $notification->setType($data['type'] ?? 'info');
        $notification->setPriority($data['priority'] ?? 'medium');
        $notification->setIsRead(false);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $this->json($notification, 201, [], ['groups' => ['notification:read']]);
    }

    #[Route('/{id}/read', name: 'notification_mark_read', methods: ['PUT'])]
    public function markAsRead(int $id): JsonResponse
    {
        try {
            $notification = $this->notificationRepository->find($id);
            if (!$notification) {
                return $this->json(['error' => 'Notification not found'], 404);
            }

            $notification->setIsRead(true);
            $this->entityManager->persist($notification);
            $this->entityManager->flush();

            error_log(sprintf('Notification %d marked as read', $id));

            return $this->json($notification, 200, [], ['groups' => ['notification:read']]);
        } catch (\Exception $e) {
            error_log('Error marking notification as read: ' . $e->getMessage());
            return $this->json(['error' => 'Internal server error'], 500);
        }
    }

    #[Route('/{id}/unread', name: 'notification_mark_unread', methods: ['PUT'])]
    public function markAsUnread(int $id): JsonResponse
    {
        try {
            $notification = $this->notificationRepository->find($id);
            if (!$notification) {
                return $this->json(['error' => 'Notification not found'], 404);
            }

            $notification->setIsRead(false);
            $this->entityManager->persist($notification);
            $this->entityManager->flush();

            error_log(sprintf('Notification %d marked as unread', $id));

            return $this->json($notification, 200, [], ['groups' => ['notification:read']]);
        } catch (\Exception $e) {
            error_log('Error marking notification as unread: ' . $e->getMessage());
            return $this->json(['error' => 'Internal server error'], 500);
        }
    }

    #[Route('/{id}/toggle', name: 'notification_toggle_read', methods: ['PUT'])]
    public function toggleReadStatus(int $id): JsonResponse
    {
        try {
            $notification = $this->notificationRepository->find($id);
            if (!$notification) {
                return $this->json(['error' => 'Notification not found'], 404);
            }

            $oldStatus = $notification->isRead();
            $newStatus = !$oldStatus;
            
            $notification->setIsRead($newStatus);
            $this->entityManager->persist($notification);
            $this->entityManager->flush();

            // Log du changement pour traçabilité
            error_log(sprintf(
                'Notification %d status changed from %s to %s',
                $id,
                $oldStatus ? 'read' : 'unread',
                $newStatus ? 'read' : 'unread'
            ));

            return $this->json($notification, 200, [], ['groups' => ['notification:read']]);
        } catch (\Exception $e) {
            error_log('Error toggling notification status: ' . $e->getMessage());
            return $this->json(['error' => 'Internal server error'], 500);
        }
    }

    #[Route('/user/{userId}/read-all', name: 'notifications_mark_all_read', methods: ['PUT'])]
    public function markAllAsRead(int $userId): JsonResponse
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $notifications = $this->notificationRepository->findBy([
            'user' => $user,
            'isRead' => false
        ]);

        foreach ($notifications as $notification) {
            $notification->setIsRead(true);
        }

        $this->entityManager->flush();

        return $this->json(['message' => 'All notifications marked as read']);
    }

    #[Route('/{id}', name: 'notification_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $notification = $this->notificationRepository->find($id);
        if (!$notification) {
            return $this->json(['error' => 'Notification not found'], 404);
        }

        $this->entityManager->remove($notification);
        $this->entityManager->flush();

        return $this->json(['message' => 'Notification deleted successfully']);
    }

    #[Route('/user/{userId}/alert', name: 'notification_create_alert', methods: ['POST'])]
    public function createAlert(int $userId, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($userId);
        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setTitle($data['title'] ?? 'Alerte');
        $notification->setMessage($data['message'] ?? '');
        $notification->setType('alert');
        $notification->setPriority('high');
        $notification->setIsRead(false);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $this->json($notification, 201, [], ['groups' => ['notification:read']]);
    }
}
