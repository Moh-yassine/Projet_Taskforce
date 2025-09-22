import { authService } from './authService'

export interface UserSkill {
  id: number
  user: {
    id: number
    firstName: string
    lastName: string
    email: string
  }
  skill: {
    id: number
    name: string
    description: string
  }
  level: number
  experience: number
  createdAt: string
  updatedAt: string
}

export interface CreateUserSkillData {
  userId: number
  skillId: number
  level: number
  experience: number
}

export interface UpdateUserSkillData {
  level?: number
  experience?: number
}

class UserSkillService {
  private readonly API_BASE_URL = 'http://127.0.0.1:8000/api/user-skills'

  async getUserSkills(): Promise<UserSkill[]> {
    const response = await fetch(this.API_BASE_URL, {
      method: 'GET',
      headers: authService.getAuthHeaders(),
    })

    if (!response.ok) {
      if (response.status === 401) {
        authService.logout()
        window.location.href = '/login'
        throw new Error('Non authentifié')
      }
      throw new Error('Erreur lors de la récupération des compétences utilisateur')
    }

    return await response.json()
  }

  async getUserSkillsByUser(userId: number): Promise<UserSkill[]> {
    const response = await fetch(`${this.API_BASE_URL}/user/${userId}`, {
      method: 'GET',
      headers: authService.getAuthHeaders(),
    })

    if (!response.ok) {
      if (response.status === 401) {
        authService.logout()
        window.location.href = '/login'
        throw new Error('Non authentifié')
      }
      throw new Error("Erreur lors de la récupération des compétences de l'utilisateur")
    }

    return await response.json()
  }

  async createUserSkill(data: CreateUserSkillData): Promise<UserSkill> {
    const response = await fetch(this.API_BASE_URL, {
      method: 'POST',
      headers: authService.getAuthHeaders(),
      body: JSON.stringify(data),
    })

    if (!response.ok) {
      if (response.status === 401) {
        authService.logout()
        window.location.href = '/login'
        throw new Error('Non authentifié')
      }
      const errorData = await response.json()
      throw new Error(errorData.error || 'Erreur lors de la création de la compétence utilisateur')
    }

    return await response.json()
  }

  async updateUserSkill(id: number, data: UpdateUserSkillData): Promise<UserSkill> {
    const response = await fetch(`${this.API_BASE_URL}/${id}`, {
      method: 'PUT',
      headers: authService.getAuthHeaders(),
      body: JSON.stringify(data),
    })

    if (!response.ok) {
      if (response.status === 401) {
        authService.logout()
        window.location.href = '/login'
        throw new Error('Non authentifié')
      }
      const errorData = await response.json()
      throw new Error(
        errorData.error || 'Erreur lors de la mise à jour de la compétence utilisateur',
      )
    }

    return await response.json()
  }

  async deleteUserSkill(id: number): Promise<{ message: string }> {
    const response = await fetch(`${this.API_BASE_URL}/${id}`, {
      method: 'DELETE',
      headers: authService.getAuthHeaders(),
    })

    if (!response.ok) {
      if (response.status === 401) {
        authService.logout()
        window.location.href = '/login'
        throw new Error('Non authentifié')
      }
      const errorData = await response.json()
      throw new Error(
        errorData.error || 'Erreur lors de la suppression de la compétence utilisateur',
      )
    }

    return await response.json()
  }

  // Constantes pour les niveaux de compétence
  static readonly LEVELS = {
    1: 'Débutant',
    2: 'Intermédiaire',
    3: 'Avancé',
    4: 'Expert',
    5: 'Maître',
  } as const

  static readonly LEVEL_COLORS = {
    1: '#ef4444', // Rouge
    2: '#f97316', // Orange
    3: '#eab308', // Jaune
    4: '#22c55e', // Vert
    5: '#3b82f6', // Bleu
  } as const
}

export const userSkillService = new UserSkillService()
