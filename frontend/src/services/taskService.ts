import { authService } from './authService'

const API_BASE_URL = 'http://127.0.0.1:8000/api'

export interface Task {
  id: number
  title: string
  description: string
  status: 'todo' | 'in_progress' | 'completed'
  priority: 'low' | 'medium' | 'high' | 'urgent'
  assignee?: {
    id: number
    firstName: string
    lastName: string
  }
  skills: Array<{
    id: number
    name: string
  }>
  startDate?: string
  dueDate?: string
  estimatedHours?: number
  actualHours?: number
  createdAt: string
  updatedAt: string
  history?: Array<{
    id: number
    action: string
    createdAt: string
    user: {
      id: number
      firstName: string
      lastName: string
    }
  }>
}

export interface CreateTaskData {
  title: string
  description?: string
  status?: 'todo' | 'in_progress' | 'completed'
  priority: 'low' | 'medium' | 'high' | 'urgent'
  projectId: number
  columnId?: number
  assigneeId?: number
  skills?: string[]
  startDate?: string
  dueDate?: string
  estimatedHours?: number
  actualHours?: number
  position?: number
}

export interface UpdateTaskData extends Partial<CreateTaskData> {
  id: number
}

export interface AssignTaskData {
  userId?: number
  reason?: string
}

class TaskService {
  private async makeRequest(url: string, options: RequestInit = {}): Promise<Response> {
    const token = authService.getAuthToken()

    const defaultOptions: RequestInit = {
      headers: {
        ...authService.getAuthHeaders(),
        ...(token && { Authorization: `Bearer ${token}` }),
        ...options.headers,
      },
    }

    const response = await fetch(url, { ...defaultOptions, ...options })

    if (response.status === 401) {
      authService.logout()
      window.location.href = '/login'
      throw new Error('Non autorisé')
    }

    return response
  }

  async getTasksByProject(projectId: number): Promise<Task[]> {
    try {
      const response = await this.makeRequest(`${API_BASE_URL}/tasks?project_id=${projectId}`)

      if (!response.ok) {
        throw new Error(`Erreur ${response.status}: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la récupération des tâches:', error)
      throw error
    }
  }

  /**
   * Récupère toutes les tâches (pour les statistiques admin)
   */
  async getAllTasks(): Promise<Task[]> {
    try {
      const response = await this.makeRequest(`${API_BASE_URL}/tasks`)

      if (!response.ok) {
        throw new Error(`Erreur ${response.status}: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la récupération de toutes les tâches:', error)
      throw error
    }
  }

  async getTask(taskId: number): Promise<Task> {
    try {
      const response = await this.makeRequest(`${API_BASE_URL}/tasks/${taskId}`)

      if (!response.ok) {
        throw new Error(`Erreur ${response.status}: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la récupération de la tâche:', error)
      throw error
    }
  }

  async createTask(taskData: CreateTaskData): Promise<Task> {
    try {
      const response = await this.makeRequest(`${API_BASE_URL}/tasks`, {
        method: 'POST',
        body: JSON.stringify(taskData),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.error || `Erreur ${response.status}: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la création de la tâche:', error)
      throw error
    }
  }

  async updateTask(taskId: number, taskData: Partial<CreateTaskData>): Promise<Task> {
    try {
      const response = await this.makeRequest(`${API_BASE_URL}/tasks/${taskId}`, {
        method: 'PUT',
        body: JSON.stringify(taskData),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.error || `Erreur ${response.status}: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error('Erreur lors de la mise à jour de la tâche:', error)
      throw error
    }
  }

  async deleteTask(taskId: number): Promise<void> {
    try {
      const response = await this.makeRequest(`${API_BASE_URL}/tasks/${taskId}`, {
        method: 'DELETE',
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.error || `Erreur ${response.status}: ${response.statusText}`)
      }
    } catch (error) {
      console.error('Erreur lors de la suppression de la tâche:', error)
      throw error
    }
  }

  async assignTask(taskId: number, assignmentData: AssignTaskData): Promise<Task> {
    try {
      const response = await this.makeRequest(`${API_BASE_URL}/tasks/${taskId}/assign`, {
        method: 'POST',
        body: JSON.stringify(assignmentData),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.error || `Erreur ${response.status}: ${response.statusText}`)
      }

      return await response.json()
    } catch (error) {
      console.error("Erreur lors de l'assignation de la tâche:", error)
      throw error
    }
  }
}

export const taskService = new TaskService()
