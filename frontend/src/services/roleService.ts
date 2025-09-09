export interface UserPermissions {
  canManageProjects: boolean;
  canSuperviseTasks: boolean;
  canAssignTasks: boolean;
  canViewAllTasks: boolean;
  canViewReports: boolean;
  canManageUsers: boolean;
  canManageSkills: boolean;
  canViewNotifications: boolean;
  canManageNotifications: boolean;
  primaryRole: string;
  roles: string[];
}

export interface Role {
  value: string;
  label: string;
  description: string;
}

export interface UserWithPermissions {
  id: number;
  email: string;
  firstName: string;
  lastName: string;
  company?: string;
  roles: string[];
  permissions: UserPermissions;
  createdAt: string;
}

class RoleService {
  private readonly API_BASE_URL = '/api/roles';

  async assignRole(userId: number, role: string): Promise<UserWithPermissions> {
    const response = await fetch(`${this.API_BASE_URL}/assign`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('authToken')}`
      },
      body: JSON.stringify({ userId, role })
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'Erreur lors de l\'assignation du rôle');
    }

    return await response.json();
  }

  async getUsers(): Promise<UserWithPermissions[]> {
    const response = await fetch(`${this.API_BASE_URL}/users`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('authToken')}`
      }
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'Erreur lors de la récupération des utilisateurs');
    }

    const data = await response.json();
    return data.users;
  }

  async getPermissions(): Promise<UserPermissions> {
    const response = await fetch(`${this.API_BASE_URL}/permissions`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('authToken')}`
      }
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'Erreur lors de la récupération des permissions');
    }

    const data = await response.json();
    return data.permissions;
  }

  async getAvailableRoles(): Promise<Role[]> {
    const response = await fetch(`${this.API_BASE_URL}/available-roles`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('authToken')}`
      }
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'Erreur lors de la récupération des rôles');
    }

    const data = await response.json();
    return data.roles;
  }

  // Méthodes utilitaires pour vérifier les permissions
  canManageProjects(permissions?: UserPermissions): boolean {
    if (!permissions) {
      const user = JSON.parse(localStorage.getItem('user') || '{}');
      return user.permissions?.canManageProjects || false;
    }
    return permissions.canManageProjects;
  }

  canSuperviseTasks(permissions?: UserPermissions): boolean {
    if (!permissions) {
      const user = JSON.parse(localStorage.getItem('user') || '{}');
      return user.permissions?.canSuperviseTasks || false;
    }
    return permissions.canSuperviseTasks;
  }

  canAssignTasks(permissions?: UserPermissions): boolean {
    if (!permissions) {
      const user = JSON.parse(localStorage.getItem('user') || '{}');
      return user.permissions?.canAssignTasks || false;
    }
    return permissions.canAssignTasks;
  }

  canViewAllTasks(permissions?: UserPermissions): boolean {
    if (!permissions) {
      const user = JSON.parse(localStorage.getItem('user') || '{}');
      return user.permissions?.canViewAllTasks || false;
    }
    return permissions.canViewAllTasks;
  }

  canViewReports(permissions?: UserPermissions): boolean {
    if (!permissions) {
      const user = JSON.parse(localStorage.getItem('user') || '{}');
      return user.permissions?.canViewReports || false;
    }
    return permissions.canViewReports;
  }

  canManageUsers(permissions?: UserPermissions): boolean {
    if (!permissions) {
      const user = JSON.parse(localStorage.getItem('user') || '{}');
      return user.permissions?.canManageUsers || false;
    }
    return permissions.canManageUsers;
  }

  canManageSkills(permissions?: UserPermissions): boolean {
    if (!permissions) {
      const user = JSON.parse(localStorage.getItem('user') || '{}');
      return user.permissions?.canManageSkills || false;
    }
    return permissions.canManageSkills;
  }

  canViewNotifications(permissions?: UserPermissions): boolean {
    if (!permissions) {
      const user = JSON.parse(localStorage.getItem('user') || '{}');
      return user.permissions?.canViewNotifications || false;
    }
    return permissions.canViewNotifications;
  }

  canManageNotifications(permissions?: UserPermissions): boolean {
    if (!permissions) {
      const user = JSON.parse(localStorage.getItem('user') || '{}');
      return user.permissions?.canManageNotifications || false;
    }
    return permissions.canManageNotifications;
  }

  getPrimaryRole(permissions?: UserPermissions): string {
    if (!permissions) {
      const user = JSON.parse(localStorage.getItem('user') || '{}');
      return user.permissions?.primaryRole || 'ROLE_USER';
    }
    return permissions.primaryRole;
  }

  isProjectManager(permissions?: UserPermissions): boolean {
    return this.getPrimaryRole(permissions) === 'ROLE_PROJECT_MANAGER';
  }

  isManager(permissions?: UserPermissions): boolean {
    return this.getPrimaryRole(permissions) === 'ROLE_MANAGER';
  }

  isCollaborator(permissions?: UserPermissions): boolean {
    return this.getPrimaryRole(permissions) === 'ROLE_COLLABORATOR';
  }

  getRoleLabel(role: string): string {
    const roleLabels: Record<string, string> = {
      'ROLE_PROJECT_MANAGER': 'Responsable de Projet',
      'ROLE_MANAGER': 'Manager',
      'ROLE_COLLABORATOR': 'Collaborateur',
      'ROLE_USER': 'Utilisateur'
    };
    return roleLabels[role] || role;
  }

  getRoleDescription(role: string): string {
    const roleDescriptions: Record<string, string> = {
      'ROLE_PROJECT_MANAGER': 'Accès complet à toutes les fonctionnalités',
      'ROLE_MANAGER': 'Superviseur avec accès aux tâches et rapports',
      'ROLE_COLLABORATOR': 'Accès limité aux tâches assignées',
      'ROLE_USER': 'Utilisateur de base'
    };
    return roleDescriptions[role] || '';
  }
}

export const roleService = new RoleService();
