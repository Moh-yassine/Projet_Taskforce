import { authService } from './authService'

export interface ObserverPermission {
  id: number
  name: string
  description: string
  enabled: boolean
}

export interface ObservedUser {
  id: number
  firstName: string
  lastName: string
  email: string
  isObserved: boolean
}

export interface ObserverSettings {
  permissions: ObserverPermission[]
  observedUsers: ObservedUser[]
}

class ObserverService {
  private readonly API_BASE_URL = 'http://127.0.0.1:8000/api/observer'

  async getObserverSettings(): Promise<ObserverSettings> {
    const response = await fetch(`${this.API_BASE_URL}/settings`, {
      method: 'GET',
      headers: authService.getAuthHeaders(),
    })

    if (!response.ok) {
      if (response.status === 401) {
        authService.logout()
        window.location.href = '/login'
        throw new Error('Non authentifié')
      }
      if (response.status === 403) {
        throw new Error('Fonctionnalité Premium requise pour accéder au mode observateur')
      }
      throw new Error('Erreur lors de la récupération des paramètres d\'observation')
    }

    return await response.json()
  }

  async updatePermission(permissionId: number, enabled: boolean): Promise<{ success: boolean; message?: string }> {
    const response = await fetch(`${this.API_BASE_URL}/permissions/${permissionId}`, {
      method: 'PUT',
      headers: {
        ...authService.getAuthHeaders(),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ enabled }),
    })

    if (!response.ok) {
      if (response.status === 401) {
        authService.logout()
        window.location.href = '/login'
        throw new Error('Non authentifié')
      }
      if (response.status === 403) {
        throw new Error('Fonctionnalité Premium requise')
      }
      const errorData = await response.json()
      throw new Error(errorData.error || 'Erreur lors de la mise à jour de la permission')
    }

    return await response.json()
  }

  async toggleUserObservation(userId: number, isObserved: boolean): Promise<{ success: boolean; message?: string }> {
    const response = await fetch(`${this.API_BASE_URL}/users/${userId}`, {
      method: 'PUT',
      headers: {
        ...authService.getAuthHeaders(),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ isObserved }),
    })

    if (!response.ok) {
      if (response.status === 401) {
        authService.logout()
        window.location.href = '/login'
        throw new Error('Non authentifié')
      }
      if (response.status === 403) {
        throw new Error('Fonctionnalité Premium requise')
      }
      const errorData = await response.json()
      throw new Error(errorData.error || 'Erreur lors de la mise à jour de l\'observation utilisateur')
    }

    return await response.json()
  }

  async getObservedUsers(): Promise<ObservedUser[]> {
    const response = await fetch(`${this.API_BASE_URL}/users`, {
      method: 'GET',
      headers: authService.getAuthHeaders(),
    })

    if (!response.ok) {
      if (response.status === 401) {
        authService.logout()
        window.location.href = '/login'
        throw new Error('Non authentifié')
      }
      if (response.status === 403) {
        throw new Error('Fonctionnalité Premium requise')
      }
      throw new Error('Erreur lors de la récupération des utilisateurs observés')
    }

    return await response.json()
  }

  async checkUserPermission(userId: number, action: string): Promise<{ allowed: boolean; reason?: string }> {
    const response = await fetch(`${this.API_BASE_URL}/check-permission`, {
      method: 'POST',
      headers: {
        ...authService.getAuthHeaders(),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ userId, action }),
    })

    if (!response.ok) {
      if (response.status === 401) {
        authService.logout()
        window.location.href = '/login'
        throw new Error('Non authentifié')
      }
      if (response.status === 403) {
        throw new Error('Fonctionnalité Premium requise')
      }
      const errorData = await response.json()
      throw new Error(errorData.error || 'Erreur lors de la vérification de la permission')
    }

    return await response.json()
  }
}

export const observerService = new ObserverService()
