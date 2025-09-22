<?php

namespace App\Service;

use App\Entity\Task;
use App\Entity\User;
use App\Entity\Skill;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use App\Repository\UserSkillRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AutoAssignmentService
{
    private const MAX_WEEKLY_HOURS = 40;
    private const OPTIMAL_WEEKLY_HOURS = 35;
    private const MINIMUM_SKILL_LEVEL = 3; // Niveau minimum requis pour une compétence

    public function __construct(
        private EntityManagerInterface $entityManager,
        private TaskRepository $taskRepository,
        private UserRepository $userRepository,
        private UserSkillRepository $userSkillRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Trouve le meilleur candidat pour une tâche en fonction des compétences et de la charge de travail
     */
    public function findBestCandidateForTask(Task $task): ?array
    {
        // Récupérer tous les utilisateurs qui peuvent être assignés (managers et collaborateurs)
        $availableUsers = $this->userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :managerRole OR u.roles LIKE :collaboratorRole')
            ->setParameter('managerRole', '%ROLE_MANAGER%')
            ->setParameter('collaboratorRole', '%ROLE_COLLABORATOR%')
            ->getQuery()
            ->getResult();

        $candidates = [];

        foreach ($availableUsers as $user) {
            $score = $this->calculateAssignmentScore($task, $user);
            if ($score > 0) { // Seulement les candidats viables
                $candidates[] = [
                    'user' => $user,
                    'score' => $score,
                    'workload' => $this->getCurrentWorkload($user),
                    'skillMatch' => $this->calculateSkillMatch($task, $user)
                ];
            }
        }

        // Trier par score (plus élevé = meilleur candidat)
        usort($candidates, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return !empty($candidates) ? $candidates[0] : null;
    }

    /**
     * Assigne automatiquement toutes les tâches non assignées
     */
    public function assignAllUnassignedTasks(): array
    {
        $unassignedTasks = $this->taskRepository->createQueryBuilder('t')
            ->where('t.assignee IS NULL')
            ->andWhere('t.status != :status')
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getResult();

        $results = [
            'assigned' => 0,
            'failed' => 0,
            'details' => []
        ];

        foreach ($unassignedTasks as $task) {
            $candidate = $this->findBestCandidateForTask($task);
            
            if ($candidate) {
                try {
                    $task->setAssignee($candidate['user']);
                    $task->setIsAutoAssigned(true);
                    $this->entityManager->persist($task);
                    
                    $results['assigned']++;
                    $results['details'][] = [
                        'task_id' => $task->getId(),
                        'task_title' => $task->getTitle(),
                        'assigned_to' => $candidate['user']->getFirstName() . ' ' . $candidate['user']->getLastName(),
                        'score' => $candidate['score'],
                        'skill_match' => $candidate['skillMatch']
                    ];
                    
                    $this->logger->info('Task auto-assigned', [
                        'task_id' => $task->getId(),
                        'user_id' => $candidate['user']->getId(),
                        'score' => $candidate['score']
                    ]);
                } catch (\Exception $e) {
                    $results['failed']++;
                    $this->logger->error('Failed to auto-assign task', [
                        'task_id' => $task->getId(),
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                $results['failed']++;
                $results['details'][] = [
                    'task_id' => $task->getId(),
                    'task_title' => $task->getTitle(),
                    'reason' => 'Aucun candidat approprié trouvé'
                ];
            }
        }

        $this->entityManager->flush();
        return $results;
    }

    /**
     * Redistribue les tâches pour équilibrer la charge de travail
     */
    public function redistributeWorkload(): array
    {
        $overloadedUsers = $this->getOverloadedUsers();
        $underloadedUsers = $this->getUnderloadedUsers();

        $results = [
            'redistributed' => 0,
            'failed' => 0,
            'details' => []
        ];

        foreach ($overloadedUsers as $overloadedUser) {
            $tasks = $this->getReassignableTasks($overloadedUser);
            
            foreach ($tasks as $task) {
                $bestCandidate = $this->findBestAlternativeCandidate($task, $underloadedUsers);
                
                if ($bestCandidate) {
                    try {
                        $oldAssignee = $task->getAssignee();
                        $task->setAssignee($bestCandidate['user']);
                        $this->entityManager->persist($task);
                        
                        $results['redistributed']++;
                        $results['details'][] = [
                            'task_id' => $task->getId(),
                            'task_title' => $task->getTitle(),
                            'from' => $oldAssignee ? $oldAssignee->getFirstName() . ' ' . $oldAssignee->getLastName() : 'Non assigné',
                            'to' => $bestCandidate['user']->getFirstName() . ' ' . $bestCandidate['user']->getLastName(),
                            'reason' => 'Rééquilibrage de charge'
                        ];
                        
                        // Mettre à jour la liste des utilisateurs sous-chargés
                        $bestCandidate['workload'] += $task->getEstimatedHours();
                        if ($bestCandidate['workload'] >= self::OPTIMAL_WEEKLY_HOURS) {
                            $underloadedUsers = array_filter($underloadedUsers, function($u) use ($bestCandidate) {
                                return $u['user']->getId() !== $bestCandidate['user']->getId();
                            });
                        }
                        
                    } catch (\Exception $e) {
                        $results['failed']++;
                        $this->logger->error('Failed to redistribute task', [
                            'task_id' => $task->getId(),
                            'error' => $e->getMessage()
                        ]);
                    }
                    
                    // Arrêter si plus d'utilisateurs sous-chargés
                    if (empty($underloadedUsers)) {
                        break 2;
                    }
                }
            }
        }

        $this->entityManager->flush();
        return $results;
    }

    /**
     * Calcule un score d'assignation pour un utilisateur et une tâche
     */
    private function calculateAssignmentScore(Task $task, User $user): float
    {
        $score = 0;

        // 1. Score basé sur les compétences (40% du score total)
        $skillScore = $this->calculateSkillMatch($task, $user);
        $score += $skillScore * 0.4;

        // 2. Score basé sur la disponibilité (35% du score total)
        $workload = $this->getCurrentWorkload($user);
        $availabilityScore = $this->calculateAvailabilityScore($workload, $task->getEstimatedHours());
        $score += $availabilityScore * 0.35;

        // 3. Score basé sur l'efficacité historique (25% du score total)
        $efficiencyScore = $this->calculateEfficiencyScore($user);
        $score += $efficiencyScore * 0.25;

        return $score;
    }

    /**
     * Calcule le score de correspondance des compétences
     */
    private function calculateSkillMatch(Task $task, User $user): float
    {
        $requiredSkills = $task->getSkills();
        
        if ($requiredSkills->isEmpty()) {
            return 1.0; // Si aucune compétence requise, tout le monde peut faire la tâche
        }

        $userSkills = $this->userSkillRepository->findBy(['user' => $user]);
        $userSkillsMap = [];
        
        foreach ($userSkills as $userSkill) {
            $userSkillsMap[$userSkill->getSkill()->getId()] = $userSkill->getLevel();
        }

        $totalMatch = 0;
        $totalRequired = 0;

        foreach ($requiredSkills as $skill) {
            $totalRequired++;
            
            if (isset($userSkillsMap[$skill->getId()])) {
                $userLevel = $userSkillsMap[$skill->getId()];
                
                if ($userLevel >= self::MINIMUM_SKILL_LEVEL) {
                    // Score basé sur le niveau de compétence (3-5 -> 0.6-1.0)
                    $totalMatch += min(1.0, ($userLevel - 2) / 3);
                }
            }
        }

        return $totalRequired > 0 ? $totalMatch / $totalRequired : 0;
    }

    /**
     * Calcule le score de disponibilité basé sur la charge de travail actuelle
     */
    private function calculateAvailabilityScore(array $workload, int $taskHours): float
    {
        $currentHours = $workload['currentWeekHours'];
        $newTotalHours = $currentHours + $taskHours;

        // Si cela dépasse la charge maximale, score de 0
        if ($newTotalHours > self::MAX_WEEKLY_HOURS) {
            return 0;
        }

        // Score optimal entre 20-35 heures par semaine
        if ($newTotalHours <= self::OPTIMAL_WEEKLY_HOURS) {
            return 1.0 - ($currentHours / self::OPTIMAL_WEEKLY_HOURS);
        }

        // Score décroissant entre 35-40 heures
        return 1.0 - (($newTotalHours - self::OPTIMAL_WEEKLY_HOURS) / (self::MAX_WEEKLY_HOURS - self::OPTIMAL_WEEKLY_HOURS));
    }

    /**
     * Calcule le score d'efficacité basé sur l'historique
     */
    private function calculateEfficiencyScore(User $user): float
    {
        $completedTasks = $this->taskRepository->createQueryBuilder('t')
            ->where('t.assignee = :user')
            ->andWhere('t.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getResult();

        if (empty($completedTasks)) {
            return 0.5; // Score neutre pour les nouveaux utilisateurs
        }

        $totalEfficiency = 0;
        $taskCount = 0;

        foreach ($completedTasks as $task) {
            $estimated = $task->getEstimatedHours();
            $actual = $task->getActualHours();
            
            if ($estimated > 0 && $actual > 0) {
                // Efficacité = temps estimé / temps réel (plafonné à 1.0)
                $efficiency = min(1.0, $estimated / $actual);
                $totalEfficiency += $efficiency;
                $taskCount++;
            }
        }

        return $taskCount > 0 ? $totalEfficiency / $taskCount : 0.5;
    }

    /**
     * Calcule la charge de travail actuelle d'un utilisateur
     */
    private function getCurrentWorkload(User $user): array
    {
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

        return [
            'currentWeekHours' => $currentWeekHours,
            'utilizationPercentage' => ($currentWeekHours / self::MAX_WEEKLY_HOURS) * 100,
            'remainingCapacity' => max(0, self::MAX_WEEKLY_HOURS - $currentWeekHours)
        ];
    }

    /**
     * Récupère les utilisateurs surchargés
     */
    private function getOverloadedUsers(): array
    {
        $users = $this->userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :managerRole OR u.roles LIKE :collaboratorRole')
            ->setParameter('managerRole', '%ROLE_MANAGER%')
            ->setParameter('collaboratorRole', '%ROLE_COLLABORATOR%')
            ->getQuery()
            ->getResult();

        $overloadedUsers = [];
        
        foreach ($users as $user) {
            $workload = $this->getCurrentWorkload($user);
            if ($workload['currentWeekHours'] > self::OPTIMAL_WEEKLY_HOURS) {
                $overloadedUsers[] = [
                    'user' => $user,
                    'workload' => $workload['currentWeekHours']
                ];
            }
        }

        return $overloadedUsers;
    }

    /**
     * Récupère les utilisateurs sous-chargés
     */
    private function getUnderloadedUsers(): array
    {
        $users = $this->userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :managerRole OR u.roles LIKE :collaboratorRole')
            ->setParameter('managerRole', '%ROLE_MANAGER%')
            ->setParameter('collaboratorRole', '%ROLE_COLLABORATOR%')
            ->getQuery()
            ->getResult();

        $underloadedUsers = [];
        
        foreach ($users as $user) {
            $workload = $this->getCurrentWorkload($user);
            if ($workload['currentWeekHours'] < self::OPTIMAL_WEEKLY_HOURS) {
                $underloadedUsers[] = [
                    'user' => $user,
                    'workload' => $workload['currentWeekHours']
                ];
            }
        }

        return $underloadedUsers;
    }

    /**
     * Récupère les tâches qui peuvent être réassignées d'un utilisateur surchargé
     */
    private function getReassignableTasks(array $overloadedUser): array
    {
        return $this->taskRepository->createQueryBuilder('t')
            ->where('t.assignee = :user')
            ->andWhere('(t.status = :status1 OR t.status = :status2)')
            ->andWhere('t.isAutoAssigned = :autoAssigned') // Seulement les tâches auto-assignées
            ->setParameter('user', $overloadedUser['user'])
            ->setParameter('status1', 'todo')
            ->setParameter('status2', 'in_progress')
            ->setParameter('autoAssigned', true)
            ->orderBy('t.priority', 'ASC') // Commencer par les priorités les plus basses
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve le meilleur candidat alternatif pour une tâche lors de la redistribution
     */
    private function findBestAlternativeCandidate(Task $task, array $underloadedUsers): ?array
    {
        $bestCandidate = null;
        $bestScore = 0;

        foreach ($underloadedUsers as $userInfo) {
            $user = $userInfo['user'];
            $currentWorkload = $userInfo['workload'];
            
            // Vérifier si l'utilisateur peut prendre cette tâche
            if ($currentWorkload + $task->getEstimatedHours() <= self::MAX_WEEKLY_HOURS) {
                $score = $this->calculateAssignmentScore($task, $user);
                
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestCandidate = [
                        'user' => $user,
                        'score' => $score,
                        'workload' => $currentWorkload
                    ];
                }
            }
        }

        return $bestCandidate;
    }

    /**
     * Récupère les statistiques d'assignation automatique
     */
    public function getAssignmentStats(): array
    {
        // Tâches non assignées
        $unassignedTasks = $this->taskRepository->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.assignee IS NULL')
            ->andWhere('t.status != :status')
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        // Utilisateurs surchargés
        $allUsers = $this->userRepository->createQueryBuilder('u')
            ->where('u.roles LIKE :managerRole OR u.roles LIKE :collaboratorRole')
            ->setParameter('managerRole', '%ROLE_MANAGER%')
            ->setParameter('collaboratorRole', '%ROLE_COLLABORATOR%')
            ->getQuery()
            ->getResult();

        $overloadedCount = 0;
        $userWorkloads = [];

        foreach ($allUsers as $user) {
            $workload = $this->getCurrentWorkload($user);
            $userWorkloads[] = [
                'user' => [
                    'id' => $user->getId(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'email' => $user->getEmail()
                ],
                'workload' => $workload
            ];
            
            if ($workload['currentWeekHours'] > self::OPTIMAL_WEEKLY_HOURS) {
                $overloadedCount++;
            }
        }

        return [
            'unassignedTasks' => $unassignedTasks,
            'overloadedUsers' => $overloadedCount,
            'userWorkloads' => $userWorkloads,
            'totalUsers' => count($allUsers)
        ];
    }
}
