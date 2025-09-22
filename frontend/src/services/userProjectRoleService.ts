import { authService } from './authService'

export interface UserProjectRole {
  id: number
  projectId: number
  projectName: string
  role: string
  createdAt: string
  updatedAt: string
}

export interface CreateUserProjectRoleData {
  projectId: number
  role: string
}

class UserProjectRoleService {
  private readonly API_BASE_URL = 'http://127.0.0.1:8000/api/user-project-roles'

  async getUserProjectRoles(): Promise<UserProjectRole[]> {
    const token = localStorage.getItem('authToken')
    if (!token) {
      throw new Error("Token d'authentification manquant")
    }

    const response = await fetch(this.API_BASE_URL, {
      method: 'GET',
      headers: {
        Authorization: `Bearer ${token}`,
        ...authService.getAuthHeaders(),
      },
    })

    if (!response.ok) {
      if (response.status === 401) {
        throw new Error('Non authentifié')
      }
      throw new Error('Erreur lors de la récupération des rôles')
    }

    return await response.json()
  }

  async createOrUpdateUserProjectRole(
    data: CreateUserProjectRoleData,
  ): Promise<{ message: string; userProjectRole: UserProjectRole }> {
    const token = localStorage.getItem('authToken')
    if (!token) {
      throw new Error("Token d'authentification manquant")
    }

    const response = await fetch(this.API_BASE_URL, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
        ...authService.getAuthHeaders(),
      },
      body: JSON.stringify(data),
    })

    if (!response.ok) {
      if (response.status === 401) {
        throw new Error('Non authentifié')
      }
      const errorData = await response.json()
      throw new Error(errorData.message || 'Erreur lors de la création/mise à jour du rôle')
    }

    return await response.json()
  }

  async deleteUserProjectRole(id: number): Promise<{ message: string }> {
    const token = localStorage.getItem('authToken')
    if (!token) {
      throw new Error("Token d'authentification manquant")
    }

    const response = await fetch(`${this.API_BASE_URL}/${id}`, {
      method: 'DELETE',
      headers: {
        Authorization: `Bearer ${token}`,
        ...authService.getAuthHeaders(),
      },
    })

    if (!response.ok) {
      if (response.status === 401) {
        throw new Error('Non authentifié')
      }
      const errorData = await response.json()
      throw new Error(errorData.message || 'Erreur lors de la suppression du rôle')
    }

    return await response.json()
  }

  // Constantes pour les rôles disponibles
  static readonly ROLES = {
    COLLABORATEUR: 'collaborateur',
    MANAGER: 'manager',
    RESPONSABLE: 'responsable',
  } as const

  static readonly ROLE_LABELS = {
    [this.ROLES.COLLABORATEUR]: 'Collaborateur',
    [this.ROLES.MANAGER]: 'Manager',
    [this.ROLES.RESPONSABLE]: 'Responsable de projet',
  } as const

  static readonly ROLE_DESCRIPTIONS = {
    [this.ROLES.COLLABORATEUR]: 'Peut consulter et participer aux tâches du projet',
    [this.ROLES.MANAGER]: "Peut gérer les tâches et coordonner l'équipe",
    [this.ROLES.RESPONSABLE]: 'Peut gérer le projet, les membres et toutes les fonctionnalités',
  } as const
}

export const userProjectRoleService = new UserProjectRoleService()
