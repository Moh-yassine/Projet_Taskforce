import axios from 'axios'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api'

export interface ManagerDashboard {
  stats: {
    totalCollaborators: number
    totalTasks: number
    completedTasks: number
    overdueTasks: number
    unreadAlerts: number
    completionRate: number
  }
  collaborators: Array<{
    id: number
    firstName: string
    lastName: string
    email: string
  }>
  tasksInProgress: Array<{
    id: number
    title: string
    status: string
    priority: string
    progress: number
    dueDate: string | null
    assignee: {
      firstName: string
      lastName: string
    } | null
    project: {
      name: string
    } | null
  }>
  recentAlerts: Array<{
    id: number
    title: string
    message: string
    type: string
    isRead: boolean
    createdAt: string | null
  }>
  supervisedProjects: Array<{
    id: number
    name: string
    description: string
    totalTasks: number
    completedTasks: number
    progress: number
  }>
}

export interface TaskProgress {
  id: number
  title: string
  description: string
  status: string
  priority: string
  estimatedHours: number
  actualHours: number
  dueDate: string | null
  createdAt: string | null
  progress: number
  isOverdue: boolean
  assignee: {
    id: number
    firstName: string
    lastName: string
    email: string
  } | null
  project: {
    id: number
    name: string
  } | null
}

export interface TaskDistributionReport {
  totalTasks: number
  tasksByStatus: Record<string, number>
  tasksByPriority: Record<string, number>
  tasksByCollaborator: Record<string, { name: string; count: number }>
  overdueTasks: number
  averageCompletionTime: number
}

export interface PerformanceReport {
  collaborator: {
    id: number
    firstName: string
    lastName: string
    email: string
  }
  totalTasks: number
  completedTasks: number
  overdueTasks: number
  completionRate: number
  efficiency: number
  totalEstimatedHours: number
  totalActualHours: number
}

export interface ManagerAlert {
  id: number
  title: string
  message: string
  type: string
  isRead: boolean
  createdAt: string | null
}

class ManagerService {
  /**
   * Récupère le dashboard du manager
   */
  async getDashboard(): Promise<ManagerDashboard> {
    try {
      const token = localStorage.getItem('authToken')
      const response = await axios.get(`${API_BASE_URL}/manager/dashboard`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      })
      return response.data
    } catch (error) {
      console.error('Erreur lors de la récupération du dashboard manager:', error)
      throw error
    }
  }

  /**
   * Récupère la progression des tâches
   */
  async getTasksProgress(): Promise<TaskProgress[]> {
    try {
      const token = localStorage.getItem('authToken')
      const response = await axios.get(`${API_BASE_URL}/manager/tasks/progress`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      })
      return response.data
    } catch (error) {
      console.error('Erreur lors de la récupération de la progression des tâches:', error)
      throw error
    }
  }

  /**
   * Met à jour la priorité d'une tâche
   */
  async updateTaskPriority(taskId: number, priority: string): Promise<any> {
    try {
      const token = localStorage.getItem('authToken')
      const response = await axios.put(
        `${API_BASE_URL}/manager/tasks/${taskId}/priority`,
        { priority },
        {
          headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json',
          },
        },
      )
      return response.data
    } catch (error) {
      console.error('Erreur lors de la mise à jour de la priorité:', error)
      throw error
    }
  }

  /**
   * Génère le rapport de répartition des tâches
   */
  async getTaskDistributionReport(): Promise<TaskDistributionReport> {
    try {
      const token = localStorage.getItem('authToken')
      const response = await axios.get(`${API_BASE_URL}/manager/reports/task-distribution`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      })
      return response.data
    } catch (error) {
      console.error('Erreur lors de la génération du rapport de répartition:', error)
      throw error
    }
  }

  /**
   * Génère le rapport de performance des collaborateurs
   */
  async getPerformanceReport(): Promise<PerformanceReport[]> {
    try {
      const token = localStorage.getItem('authToken')
      const response = await axios.get(`${API_BASE_URL}/manager/reports/performance`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      })
      return response.data
    } catch (error) {
      console.error('Erreur lors de la génération du rapport de performance:', error)
      throw error
    }
  }

  /**
   * Récupère les alertes du manager
   */
  async getAlerts(): Promise<ManagerAlert[]> {
    try {
      const token = localStorage.getItem('authToken')
      const response = await axios.get(`${API_BASE_URL}/manager/alerts`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      })
      return response.data
    } catch (error) {
      console.error('Erreur lors de la récupération des alertes:', error)
      throw error
    }
  }

  /**
   * Marque une alerte comme lue
   */
  async markAlertAsRead(alertId: number): Promise<any> {
    try {
      const token = localStorage.getItem('authToken')
      const response = await axios.put(
        `${API_BASE_URL}/notifications/${alertId}/read`,
        {},
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        },
      )
      return response.data
    } catch (error) {
      console.error("Erreur lors du marquage de l'alerte comme lue:", error)
      throw error
    }
  }

  /**
   * Obtient la couleur de priorité
   */
  getPriorityColor(priority: string): string {
    switch (priority) {
      case 'urgent':
        return '#e74c3c'
      case 'high':
        return '#f39c12'
      case 'medium':
        return '#f1c40f'
      case 'low':
        return '#27ae60'
      default:
        return '#95a5a6'
    }
  }

  /**
   * Obtient le libellé de priorité
   */
  getPriorityLabel(priority: string): string {
    switch (priority) {
      case 'urgent':
        return 'Urgente'
      case 'high':
        return 'Élevée'
      case 'medium':
        return 'Moyenne'
      case 'low':
        return 'Faible'
      default:
        return 'Non définie'
    }
  }

  /**
   * Obtient la couleur de statut
   */
  getStatusColor(status: string): string {
    switch (status) {
      case 'completed':
        return '#27ae60'
      case 'in_progress':
        return '#3498db'
      case 'todo':
        return '#95a5a6'
      default:
        return '#95a5a6'
    }
  }

  /**
   * Obtient le libellé de statut
   */
  getStatusLabel(status: string): string {
    switch (status) {
      case 'completed':
        return 'Terminée'
      case 'in_progress':
        return 'En cours'
      case 'todo':
        return 'À faire'
      default:
        return 'Inconnu'
    }
  }

  /**
   * Calcule le pourcentage de progression
   */
  calculateProgressPercentage(progress: number): string {
    return `${progress}%`
  }

  /**
   * Formate la date pour l'affichage
   */
  formatDate(dateString: string | null): string {
    if (!dateString) return 'N/A'
    return new Date(dateString).toLocaleDateString('fr-FR')
  }

  /**
   * Formate la date et l'heure pour l'affichage
   */
  formatDateTime(dateString: string | null): string {
    if (!dateString) return 'N/A'
    return new Date(dateString).toLocaleString('fr-FR')
  }

  /**
   * Vérifie si une tâche est en retard
   */
  isTaskOverdue(dueDate: string | null, status: string): boolean {
    if (!dueDate || status === 'completed') return false
    return new Date(dueDate) < new Date()
  }

  /**
   * Obtient la couleur d'efficacité
   */
  getEfficiencyColor(efficiency: number): string {
    if (efficiency >= 100) return '#27ae60' // Vert
    if (efficiency >= 80) return '#f1c40f' // Jaune
    if (efficiency >= 60) return '#f39c12' // Orange
    return '#e74c3c' // Rouge
  }
}

export const managerService = new ManagerService()
