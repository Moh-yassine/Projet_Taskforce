<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Task;
use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class AlertService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationRepository $notificationRepository,
        private TaskRepository $taskRepository,
        private UserRepository $userRepository
    ) {}

    /**
     * Vérifie et génère des alertes de surcharge de travail pour le responsable de projet
     */
    public function checkAndGenerateWorkloadAlerts(): array
    {
        $alerts = [];
        $users = $this->userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :collaboratorRole OR u.roles LIKE :managerRole')
            ->setParameter('collaboratorRole', '%ROLE_COLLABORATOR%')
            ->setParameter('managerRole', '%ROLE_MANAGER%')
            ->getQuery()
            ->getResult();

        foreach ($users as $user) {
            $workload = $this->calculateUserWorkload($user);
            
            
            if ($workload['currentWeekHours'] >= 35) {
                // Trouver tous les managers qui supervisent cet utilisateur
                $supervisingManagers = $this->findSupervisingManagers($user);
                
                foreach ($supervisingManagers as $manager) {
                    // Vérifier si une alerte existe déjà pour cet utilisateur et ce manager aujourd'hui
                    $today = new \DateTime('today');
                    $tomorrow = new \DateTime('tomorrow');
                    $existingAlert = $this->notificationRepository->createQueryBuilder('n')
                        ->where('n.user = :manager')
                        ->andWhere('n.type = :type')
                        ->andWhere('n.message LIKE :userEmail')
                        ->andWhere('n.createdAt >= :today')
                        ->andWhere('n.createdAt < :tomorrow')
                        ->setParameter('manager', $manager)
                        ->setParameter('type', 'workload_alert')
                        ->setParameter('userEmail', '%' . $user->getEmail() . '%')
                        ->setParameter('today', $today)
                        ->setParameter('tomorrow', $tomorrow)
                        ->getQuery()
                        ->getOneOrNullResult();

                    // Ne créer une alerte que si elle n'existe pas déjà
                    if (!$existingAlert) {
                        $alert = $this->createWorkloadAlertForManager($manager, $user, $workload);
                        if ($alert) {
                            $alerts[] = $alert;
                        }
                    }
                }
            }
        }

        return $alerts;
    }

    /**
     * Vérifie et génère des alertes de retard pour le responsable de projet
     */
    public function checkAndGenerateDelayAlerts(): array
    {
        $alerts = [];
        $now = new \DateTimeImmutable();
        
        // Récupérer les tâches en retard
        $overdueTasks = $this->taskRepository->createQueryBuilder('t')
            ->where('t.dueDate < :now')
            ->andWhere('t.status != :completed')
            ->andWhere('t.assignee IS NOT NULL')
            ->setParameter('now', $now)
            ->setParameter('completed', 'completed')
            ->getQuery()
            ->getResult();

        foreach ($overdueTasks as $task) {
            $assignee = $task->getAssignee();
            if ($assignee) {
                $this->createDelayAlertForUser($task, $assignee, $alerts);
            }
        }

        return $alerts;
    }

    /**
     * Crée une alerte de retard pour un utilisateur spécifique
     */
    private function createDelayAlertForUser(Task $task, User $user, array &$alerts): void
    {
        // Trouver tous les managers qui supervisent cet utilisateur
        $supervisingManagers = $this->findSupervisingManagers($user);
        
        foreach ($supervisingManagers as $manager) {
            // Vérifier si une alerte existe déjà pour cette tâche et ce manager aujourd'hui
            $today = new \DateTime('today');
            $tomorrow = new \DateTime('tomorrow');
            $existingAlert = $this->notificationRepository->createQueryBuilder('n')
                ->where('n.user = :manager')
                ->andWhere('n.type = :type')
                ->andWhere('n.message LIKE :taskTitle')
                ->andWhere('n.createdAt >= :today')
                ->andWhere('n.createdAt < :tomorrow')
                ->setParameter('manager', $manager)
                ->setParameter('type', 'delay_alert')
                ->setParameter('taskTitle', '%' . $task->getTitle() . '%')
                ->setParameter('today', $today)
                ->setParameter('tomorrow', $tomorrow)
                ->getQuery()
                ->getOneOrNullResult();

            // Ne créer une alerte que si elle n'existe pas déjà
            if (!$existingAlert) {
                $alert = $this->createDelayAlertForManager($manager, $task, $user);
                if ($alert) {
                    $alerts[] = $alert;
                }
            }
        }
    }

    /**
     * Vérifie et génère toutes les alertes automatiquement
     */
    public function checkAndGenerateAllAlerts(): array
    {
        $workloadAlerts = $this->checkAndGenerateWorkloadAlerts();
        $delayAlerts = $this->checkAndGenerateDelayAlerts();
        
        return array_merge($workloadAlerts, $delayAlerts);
    }

    /**
     * Calcule la charge de travail d'un utilisateur
     */
    private function calculateUserWorkload(User $user): array
    {
        $maxWeekHours = 40; // Maximum d'heures par semaine
        $alertThreshold = 35; // Seuil d'alerte pour anticiper la surcharge
        
        // Récupérer les tâches assignées
        $tasks = $this->taskRepository->createQueryBuilder('t')
            ->where('t.assignee = :user')
            ->andWhere('(t.status = :status1 OR t.status = :status2)')
            ->setParameter('user', $user)
            ->setParameter('status1', 'todo')
            ->setParameter('status2', 'in_progress')
            ->getQuery()
            ->getResult();
        
        $currentWeekHours = 0;
        foreach ($tasks as $task) {
            $currentWeekHours += $task->getEstimatedHours() ?? 0;
        }
        
        $utilizationPercentage = $maxWeekHours > 0 ? ($currentWeekHours / $maxWeekHours) * 100 : 0;
        
        return [
            'currentWeekHours' => $currentWeekHours,
            'maxWeekHours' => $maxWeekHours,
            'utilizationPercentage' => $utilizationPercentage,
            'remainingCapacity' => max(0, $maxWeekHours - $currentWeekHours)
        ];
    }

    /**
     * Crée une alerte de surcharge de travail pour le responsable de projet
     */
    private function createWorkloadAlertForManager(User $projectManager, User $overloadedUser, array $workload): ?Notification
    {
        // Ne pas vérifier les doublons pour permettre toutes les alertes

        $notification = new Notification();
        $notification->setUser($projectManager);
        $notification->setTitle('🚨 Surcharge de travail détectée');
        $notification->setMessage(sprintf(
            'L\'utilisateur %s %s (%s) approche du seuil de surcharge. Charge actuelle: %.1fh/%.1fh (%.1f%%). ' .
            'Seuil d\'alerte: 35h. Il est recommandé de redistribuer certaines tâches ou d\'ajuster les délais.',
            $overloadedUser->getFirstName(),
            $overloadedUser->getLastName(),
            $overloadedUser->getEmail(),
            $workload['currentWeekHours'],
            $workload['maxWeekHours'],
            $workload['utilizationPercentage']
        ));
        $notification->setType('workload_alert');
        $notification->setPriority('high');
        $notification->setIsRead(false);
        $notification->setCreatedAt(new \DateTimeImmutable());
        $notification->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    /**
     * Crée une alerte de retard de tâche pour le responsable de projet
     */
    private function createDelayAlertForManager(User $projectManager, Task $task, ?User $specificAssignee = null): ?Notification
    {
        // Vérifier si une alerte similaire existe déjà récemment (dans les dernières 24h)
        $recentAlert = $this->notificationRepository->createQueryBuilder('n')
            ->where('n.user = :manager')
            ->andWhere('n.type = :type')
            ->andWhere('n.relatedTask = :task')
            ->andWhere('n.createdAt > :recent')
            ->setParameter('manager', $projectManager)
            ->setParameter('type', 'delay_alert')
            ->setParameter('task', $task)
            ->setParameter('recent', new \DateTimeImmutable('-24 hours'))
            ->getQuery()
            ->getOneOrNullResult();

        if ($recentAlert) {
            return null; // Éviter les doublons
        }

        $delay = $task->getDueDate()->diff(new \DateTimeImmutable());
        $delayDays = $delay->days;

        // Déterminer l'assigné pour le message
        $assignee = $task->getAssignee();
        if (!$assignee) {
            return null; // Pas d'assigné
        }

        $assigneeText = sprintf('%s %s (%s)', $assignee->getFirstName(), $assignee->getLastName(), $assignee->getEmail());

        $notification = new Notification();
        $notification->setUser($projectManager);
        $notification->setTitle('⏰ Tâche en retard');
        $notification->setMessage(sprintf(
            'La tâche "%s" assignée à %s est en retard de %d jour(s). ' .
            'Date d\'échéance: %s. Projet: %s. ' .
            'Il est recommandé de réévaluer les priorités ou d\'ajuster le planning.',
            $task->getTitle(),
            $assigneeText,
            $delayDays,
            $task->getDueDate()->format('d/m/Y'),
            $task->getProject() ? $task->getProject()->getName() : 'N/A'
        ));
        $notification->setType('delay_alert');
        $notification->setPriority('high');
        $notification->setIsRead(false);
        $notification->setRelatedTask($task);
        $notification->setRelatedProject($task->getProject());
        $notification->setCreatedAt(new \DateTimeImmutable());
        $notification->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    /**
     * Nettoie les anciennes alertes (plus de 7 jours)
     */
    public function cleanupOldAlerts(): int
    {
        $oldAlerts = $this->notificationRepository->createQueryBuilder('n')
            ->where('n.type IN (:types)')
            ->andWhere('n.createdAt < :oldDate')
            ->setParameter('types', ['workload_alert', 'delay_alert'])
            ->setParameter('oldDate', new \DateTimeImmutable('-7 days'))
            ->getQuery()
            ->getResult();

        $count = count($oldAlerts);
        foreach ($oldAlerts as $alert) {
            $this->entityManager->remove($alert);
        }
        $this->entityManager->flush();

        return $count;
    }

    /**
     * Trouve tous les managers qui supervisent un collaborateur donné
     */
    private function findSupervisingManagers(User $collaborator): array
    {
        // Envoyer les alertes à tous les responsables de projet ET aux managers
        $managers = $this->userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :projectManagerRole OR u.roles LIKE :managerRole')
            ->setParameter('projectManagerRole', '%ROLE_PROJECT_MANAGER%')
            ->setParameter('managerRole', '%ROLE_MANAGER%')
            ->getQuery()
            ->getResult();
        
        return $managers;
    }

}
