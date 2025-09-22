<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Project;
use App\Entity\Task;

class PermissionService
{
    public const ROLE_PROJECT_MANAGER = 'ROLE_PROJECT_MANAGER';
    public const ROLE_MANAGER = 'ROLE_MANAGER';
    public const ROLE_COLLABORATOR = 'ROLE_COLLABORATOR';

    /**
     * Vérifie si l'utilisateur peut gérer les projets
     */
    public function canManageProjects(User $user): bool
    {
        return $user->isProjectManager();
    }

    /**
     * Vérifie si l'utilisateur peut superviser les tâches
     */
    public function canSuperviseTasks(User $user): bool
    {
        return $user->isProjectManager() || $user->isManager();
    }

    /**
     * Vérifie si l'utilisateur peut assigner des tâches
     */
    public function canAssignTasks(User $user): bool
    {
        return $user->isProjectManager();
    }

    /**
     * Vérifie si l'utilisateur peut voir toutes les tâches
     */
    public function canViewAllTasks(User $user): bool
    {
        return $user->isProjectManager() || $user->isManager();
    }

    /**
     * Vérifie si l'utilisateur peut modifier une tâche
     */
    public function canEditTask(User $user, Task $task): bool
    {
        // Le responsable de projet peut tout modifier
        if ($user->isProjectManager()) {
            return true;
        }

        // Le manager peut modifier les tâches de ses projets
        if ($user->isManager()) {
            return $this->isUserInProject($user, $task->getProject());
        }

        // Le collaborateur peut modifier ses propres tâches
        if ($user->isCollaborator()) {
            return $task->getAssignee() === $user;
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur peut voir une tâche
     */
    public function canViewTask(User $user, Task $task): bool
    {
        // Le responsable de projet peut tout voir
        if ($user->isProjectManager()) {
            return true;
        }

        // Le manager peut voir les tâches de ses projets
        if ($user->isManager()) {
            return $this->isUserInProject($user, $task->getProject());
        }

        // Le collaborateur peut voir ses propres tâches
        if ($user->isCollaborator()) {
            return $task->getAssignee() === $user;
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur peut voir un projet
     */
    public function canViewProject(User $user, Project $project): bool
    {
        // Le responsable de projet peut tout voir
        if ($user->isProjectManager()) {
            return true;
        }

        // Le manager et collaborateur peuvent voir les projets auxquels ils participent
        return $this->isUserInProject($user, $project);
    }

    /**
     * Vérifie si l'utilisateur peut modifier un projet
     */
    public function canEditProject(User $user, Project $project): bool
    {
        // Seul le responsable de projet peut modifier les projets
        return $user->isProjectManager() && $project->getProjectManager() === $user;
    }

    /**
     * Vérifie si l'utilisateur peut voir les rapports
     */
    public function canViewReports(User $user): bool
    {
        return $user->isProjectManager() || $user->isManager();
    }

    /**
     * Vérifie si l'utilisateur peut gérer les utilisateurs
     */
    public function canManageUsers(User $user): bool
    {
        return $user->isProjectManager();
    }

    /**
     * Vérifie si l'utilisateur peut gérer les compétences
     */
    public function canManageSkills(User $user): bool
    {
        return $user->isProjectManager();
    }

    /**
     * Vérifie si l'utilisateur peut voir les notifications
     */
    public function canViewNotifications(User $user): bool
    {
        return true; // Tous les utilisateurs peuvent voir leurs notifications
    }

    /**
     * Vérifie si l'utilisateur peut gérer les notifications
     */
    public function canManageNotifications(User $user): bool
    {
        return $user->isProjectManager() || $user->isManager();
    }

    /**
     * Vérifie si l'utilisateur peut accéder à l'admin
     */
    public function canAccessAdmin(User $user): bool
    {
        return $user->isProjectManager() || $user->isManager();
    }

    /**
     * Vérifie si l'utilisateur peut gérer les rôles
     */
    public function canManageRoles(User $user): bool
    {
        return $user->isProjectManager();
    }

    /**
     * Vérifie si l'utilisateur participe à un projet
     */
    private function isUserInProject(User $user, Project $project): bool
    {
        // Vérifier si l'utilisateur est le responsable du projet
        if ($project->getProjectManager() === $user) {
            return true;
        }

        // Vérifier si l'utilisateur est membre de l'équipe
        return $user->getAssignedProjects()->contains($project);
    }

    /**
     * Retourne les permissions de l'utilisateur sous forme de tableau
     */
    public function getUserPermissions(User $user): array
    {
        return [
            'canManageProjects' => $this->canManageProjects($user),
            'canSuperviseTasks' => $this->canSuperviseTasks($user),
            'canAssignTasks' => $this->canAssignTasks($user),
            'canViewAllTasks' => $this->canViewAllTasks($user),
            'canViewReports' => $this->canViewReports($user),
            'canManageUsers' => $this->canManageUsers($user),
            'canManageSkills' => $this->canManageSkills($user),
            'canViewNotifications' => $this->canViewNotifications($user),
            'canManageNotifications' => $this->canManageNotifications($user),
            'canAccessAdmin' => $this->canAccessAdmin($user),
            'canManageRoles' => $this->canManageRoles($user),
            'primaryRole' => $user->getPrimaryRole(),
            'roles' => $user->getRoles()
        ];
    }
}
