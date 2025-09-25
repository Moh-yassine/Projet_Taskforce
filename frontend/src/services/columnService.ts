import { authService } from './authService'

export interface Column {
  id: number
  name: string
  description?: string
  color: string
  position: number
  isDefault: boolean
  tasksCount: number
  tasks: Task[]
}

export interface Task {
  id: number
  title: string
  description?: string
  priority: string
  position: number
  assignee?: {
    id: number
    firstName: string
    lastName: string
  }
  dueDate?: string
  createdAt: string
}

class ColumnService {
  private API_BASE_URL = 'http://127.0.0.1:8000/api'

  async getColumnsByProject(projectId: number): Promise<Column[]> {
    const response = await fetch(`${this.API_BASE_URL}/columns/project/${projectId}`, {
      method: 'GET',
      headers: {
        ...authService.getAuthHeaders(),
        'Content-Type': 'application/json',
      },
    })

    if (!response.ok) {
      if (response.status === 401) {
        authService.logout().catch(() => {})
        window.location.href = '/login'
        throw new Error('Non authentifié')
      }
      
      let errorMessage = 'Erreur lors de la récupération des colonnes'
      try {
        const errorData = await response.json()
        errorMessage = errorData.error || errorData.message || errorMessage
      } catch (e) {
        console.error('Erreur de parsing JSON:', e)
        errorMessage = `Erreur serveur (${response.status}): ${response.statusText}`
      }
      throw new Error(errorMessage)
    }

    return await response.json()
  }

  async createColumn(projectId: number, columnData: {
    name: string
    description?: string
    color?: string
  }): Promise<Column> {
    const response = await fetch(`${this.API_BASE_URL}/columns/project/${projectId}`, {
      method: 'POST',
      headers: {
        ...authService.getAuthHeaders(),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(columnData),
    })

    if (!response.ok) {
      if (response.status === 401) {
        authService.logout().catch(() => {})
        window.location.href = '/login'
        throw new Error('Non authentifié')
      }
      
      let errorMessage = 'Erreur lors de la création de la colonne'
      try {
        const errorData = await response.json()
        errorMessage = errorData.error || errorData.message || errorMessage
      } catch (e) {
        console.error('Erreur de parsing JSON:', e)
        errorMessage = `Erreur serveur (${response.status}): ${response.statusText}`
      }
      throw new Error(errorMessage)
    }

    return await response.json()
  }

  async updateColumn(columnId: number, columnData: {
    name?: string
    description?: string
    color?: string
  }): Promise<Column> {
    const response = await fetch(`${this.API_BASE_URL}/columns/${columnId}`, {
      method: 'PUT',
      headers: {
        ...authService.getAuthHeaders(),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(columnData),
    })

    if (!response.ok) {
      if (response.status === 401) {
        authService.logout().catch(() => {})
        window.location.href = '/login'
        throw new Error('Non authentifié')
      }
      
      let errorMessage = 'Erreur lors de la mise à jour de la colonne'
      try {
        const errorData = await response.json()
        errorMessage = errorData.error || errorData.message || errorMessage
      } catch (e) {
        console.error('Erreur de parsing JSON:', e)
        errorMessage = `Erreur serveur (${response.status}): ${response.statusText}`
      }
      throw new Error(errorMessage)
    }

    return await response.json()
  }

  async deleteColumn(columnId: number): Promise<void> {
    const response = await fetch(`${this.API_BASE_URL}/columns/${columnId}`, {
      method: 'DELETE',
      headers: {
        ...authService.getAuthHeaders(),
        'Content-Type': 'application/json',
      },
    })

    if (!response.ok) {
      if (response.status === 401) {
        authService.logout().catch(() => {})
        window.location.href = '/login'
        throw new Error('Non authentifié')
      }
      
      let errorMessage = 'Erreur lors de la suppression de la colonne'
      try {
        const errorData = await response.json()
        errorMessage = errorData.error || errorData.message || errorMessage
      } catch (e) {
        console.error('Erreur de parsing JSON:', e)
        errorMessage = `Erreur serveur (${response.status}): ${response.statusText}`
      }
      throw new Error(errorMessage)
    }
  }

  async reorderColumns(columns: { id: number; position: number }[]): Promise<void> {
    const response = await fetch(`${this.API_BASE_URL}/columns/reorder`, {
      method: 'POST',
      headers: {
        ...authService.getAuthHeaders(),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ columns }),
    })

    if (!response.ok) {
      if (response.status === 401) {
        authService.logout().catch(() => {})
        window.location.href = '/login'
        throw new Error('Non authentifié')
      }
      
      let errorMessage = 'Erreur lors de la réorganisation des colonnes'
      try {
        const errorData = await response.json()
        errorMessage = errorData.error || errorData.message || errorMessage
      } catch (e) {
        console.error('Erreur de parsing JSON:', e)
        errorMessage = `Erreur serveur (${response.status}): ${response.statusText}`
      }
      throw new Error(errorMessage)
    }
  }
}

export const columnService = new ColumnService()
