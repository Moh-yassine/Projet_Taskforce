<?php

namespace App\Service;

use App\Entity\Task;
use App\Entity\User;
use App\Entity\UserSkill;
use App\Entity\Workload;
use App\Repository\UserRepository;
use App\Repository\UserSkillRepository;
use App\Repository\WorkloadRepository;
use Doctrine\ORM\EntityManagerInterface;

class TaskAssignmentService
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private UserSkillRepository $userSkillRepository;
    private WorkloadRepository $workloadRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        UserSkillRepository $userSkillRepository,
        WorkloadRepository $workloadRepository
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->userSkillRepository = $userSkillRepository;
        $this->workloadRepository = $workloadRepository;
    }

    /**
     * Trouve le meilleur utilisateur pour une tâche basé sur les compétences et la charge de travail
     */
    public function findBestUserForTask(Task $task): ?User
    {
        $requiredSkills = $task->getSkills();
        if ($requiredSkills->isEmpty()) {
            return null;
        }

        $candidates = [];
        $users = $this->userRepository->findAll();

        foreach ($users as $user) {
            if (!$user->isCollaborator()) {
                continue;
            }

            $skillMatch = $this->calculateSkillMatch($user, $requiredSkills);
            $workloadScore = $this->calculateWorkloadScore($user);
            $availabilityScore = $this->calculateAvailabilityScore($user);

            if ($skillMatch > 0) {
                $candidates[] = [
                    'user' => $user,
                    'score' => $skillMatch * 0.5 + $workloadScore * 0.3 + $availabilityScore * 0.2
                ];
            }
        }

        if (empty($candidates)) {
            return null;
        }

        // Trier par score décroissant
        usort($candidates, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $candidates[0]['user'];
    }

    /**
     * Calcule le score de correspondance des compétences
     */
    private function calculateSkillMatch(User $user, $requiredSkills): float
    {
        $userSkills = $this->userSkillRepository->findBy(['user' => $user]);
        $userSkillMap = [];
        
        foreach ($userSkills as $userSkill) {
            $userSkillMap[$userSkill->getSkill()->getId()] = $userSkill->getLevel();
        }

        $totalScore = 0;
        $skillCount = 0;

        foreach ($requiredSkills as $requiredSkill) {
            $skillId = $requiredSkill->getId();
            if (isset($userSkillMap[$skillId])) {
                $totalScore += $userSkillMap[$skillId];
            }
            $skillCount++;
        }

        return $skillCount > 0 ? $totalScore / $skillCount : 0;
    }

    /**
     * Calcule le score de charge de travail (plus bas = mieux)
     */
    private function calculateWorkloadScore(User $user): float
    {
        $today = new \DateTime();
        $workload = $this->workloadRepository->findOneBy([
            'user' => $user,
            'date' => $today
        ]);

        if (!$workload) {
            return 1.0; // Pas de charge de travail = disponible
        }

        $percentage = $workload->getWorkloadPercentage();
        if ($percentage >= 100) {
            return 0.0; // Surchargé
        }

        return (100 - $percentage) / 100;
    }

    /**
     * Calcule le score de disponibilité
     */
    private function calculateAvailabilityScore(User $user): float
    {
        // Pour l'instant, on considère que tous les utilisateurs sont disponibles
        // On pourrait ajouter une logique basée sur les congés, les horaires, etc.
        return 1.0;
    }

    /**
     * Assigne automatiquement une tâche
     */
    public function autoAssignTask(Task $task): bool
    {
        $bestUser = $this->findBestUserForTask($task);
        
        if (!$bestUser) {
            return false;
        }

        $task->setAssignee($bestUser);
        $task->setIsAutoAssigned(true);
        
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        // Mettre à jour la charge de travail
        $this->updateUserWorkload($bestUser, $task);

        return true;
    }

    /**
     * Met à jour la charge de travail d'un utilisateur
     */
    public function updateUserWorkload(User $user, Task $task): void
    {
        $today = new \DateTime();
        $workload = $this->workloadRepository->findOneBy([
            'user' => $user,
            'date' => $today
        ]);

        if (!$workload) {
            $workload = new Workload();
            $workload->setUser($user);
            $workload->setDate($today);
        }

        // Calculer la nouvelle charge de travail
        $currentTasks = $workload->getAssignedTasks();
        $estimatedHours = $workload->getEstimatedHours() + $task->getEstimatedHours();
        
        $workload->setAssignedTasks($currentTasks + 1);
        $workload->setEstimatedHours($estimatedHours);
        
        // Calculer le pourcentage de charge (basé sur 8h/jour)
        $workloadPercentage = min(($estimatedHours / 8) * 100, 200);
        $workload->setWorkloadPercentage((int)$workloadPercentage);
        $workload->setIsOverloaded($workloadPercentage > 100);

        $this->entityManager->persist($workload);
        $this->entityManager->flush();

        // Créer une notification si surchargé
        if ($workload->isOverloaded()) {
            $this->createOverloadNotification($user, $workload);
        }
    }

    /**
     * Crée une notification de surcharge
     */
    private function createOverloadNotification(User $user, Workload $workload): void
    {
        $notification = new \App\Entity\Notification();
        $notification->setUser($user);
        $notification->setTitle('Surcharge de travail détectée');
        $notification->setMessage(sprintf(
            'Votre charge de travail est de %d%% aujourd\'hui. Considérez demander de l\'aide.',
            $workload->getWorkloadPercentage()
        ));
        $notification->setType('workload');
        $notification->setPriority('high');

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }

    /**
     * Redistribue les tâches en cas de surcharge
     */
    public function redistributeOverloadedTasks(): array
    {
        $overloadedUsers = $this->workloadRepository->findBy(['isOverloaded' => true]);
        $redistributed = [];

        foreach ($overloadedUsers as $workload) {
            $user = $workload->getUser();
            $tasks = $this->entityManager->getRepository(Task::class)->findBy([
                'assignee' => $user,
                'status' => 'in_progress'
            ]);

            foreach ($tasks as $task) {
                $newAssignee = $this->findBestUserForTask($task);
                if ($newAssignee && $newAssignee !== $user) {
                    $task->setAssignee($newAssignee);
                    $this->entityManager->persist($task);
                    
                    $redistributed[] = [
                        'task' => $task,
                        'from' => $user,
                        'to' => $newAssignee
                    ];
                }
            }
        }

        $this->entityManager->flush();
        return $redistributed;
    }
}
