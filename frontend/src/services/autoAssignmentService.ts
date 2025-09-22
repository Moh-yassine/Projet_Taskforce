import axios from 'axios'
import { API_CONFIG } from '@/config/api'

export interface UnassignedTask {
  id: number
  title: string
  description: string
  priority: string
  status: string
  estimatedHours: number
  dueDate?: string
  createdAt: string
  project?: {
    id: number
    name: string
  }
  skills: Array<{
    id: number
    name: string
  }>
}

export interface OverloadedUser {
  user: {
    id: number
    firstName: string
    lastName: string
    email: string
  }
  currentHours: number
  utilizationPercentage: number
  remainingCapacity: number
  status: string
}

export interface TaskCandidate {
  user: {
    id: number
    firstName: string
    lastName: string
    email: string
  }
  score: number
  skillMatch: number
  workload: {
    currentWeekHours: number
    utilizationPercentage: number
    remainingCapacity: number
  }
  recommendation: string
}

export interface AssignmentStats {
  unassignedTasks: number
  overloadedUsers: number
  userWorkloads: Array<{
    user: {
      id: number
      firstName: string
      lastName: string
      email: string
    }
    workload: {
      currentWeekHours: number
      utilizationPercentage: number
      remainingCapacity: number
    }
  }>
  totalUsers: number
}

export interface WorkloadDetail {
  user: {
    id: number
    firstName: string
    lastName: string
    email: string
  }
  workload: {
    currentWeekHours: number
    utilizationPercentage: number
    remainingCapacity: number
  }
  status: string
  statusClass: string
}

export interface AssignmentResult {
  assigned?: number
  redistributed?: number
  failed: number
  details: Array<{
    task_id?: number
    task_title?: string
    assigned_to?: string
    from?: string
    to?: string
    score?: number
    skill_match?: number
    reason?: string
  }>
}

class AutoAssignmentService {
  private getAuthHeaders() {
    const token = localStorage.getItem('authToken')
    return {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
    }
  }

  /**
   * Récupère les statistiques d'assignation automatique
   */
  async getAssignmentStats(): Promise<AssignmentStats> {
    try {
      const response = await axios.get(`${API_CONFIG.BASE_URL}/auto-assignment/stats`, {
        headers: this.getAuthHeaders(),
      })
      return response.data
    } catch (error) {
      console.error('Erreur lors de la récupération des statistiques:', error)
      throw error
    }
  }

  /**
   * Récupère toutes les tâches non assignées
   */
  async getUnassignedTasks(): Promise<UnassignedTask[]> {
    try {
      const response = await axios.get(`${API_CONFIG.BASE_URL}/auto-assignment/unassigned-tasks`, {
        headers: this.getAuthHeaders(),
      })
      return response.data
    } catch (error) {
      console.error('Erreur lors de la récupération des tâches non assignées:', error)
      throw error
    }
  }

  /**
   * Récupère tous les utilisateurs surchargés
   */
  async getOverloadedUsers(): Promise<OverloadedUser[]> {
    try {
      const response = await axios.get(`${API_CONFIG.BASE_URL}/auto-assignment/overloaded-users`, {
        headers: this.getAuthHeaders(),
      })
      return response.data
    } catch (error) {
      console.error('Erreur lors de la récupération des utilisateurs surchargés:', error)
      throw error
    }
  }

  /**
   * Trouve le meilleur candidat pour une tâche spécifique
   */
  async findCandidateForTask(taskId: number): Promise<{ candidate: TaskCandidate | null; message?: string }> {
    try {
      const response = await axios.get(`${API_CONFIG.BASE_URL}/auto-assignment/find-candidate/${taskId}`, {
        headers: this.getAuthHeaders(),
      })
      return response.data
    } catch (error) {
      console.error('Erreur lors de la recherche de candidat:', error)
      throw error
    }
  }

  /**
   * Assigne automatiquement toutes les tâches non assignées
   */
  async assignAllTasks(): Promise<AssignmentResult & { message: string }> {
    try {
      const response = await axios.post(`${API_CONFIG.BASE_URL}/auto-assignment/assign-all`, {}, {
        headers: this.getAuthHeaders(),
      })
      return response.data
    } catch (error) {
      console.error('Erreur lors de l\'assignation automatique:', error)
      throw error
    }
  }

  /**
   * Redistribue les tâches pour équilibrer la charge de travail
   */
  async redistributeTasks(): Promise<AssignmentResult & { message: string }> {
    try {
      const response = await axios.post(`${API_CONFIG.BASE_URL}/auto-assignment/redistribute`, {}, {
        headers: this.getAuthHeaders(),
      })
      return response.data
    } catch (error) {
      console.error('Erreur lors de la redistribution:', error)
      throw error
    }
  }

  /**
   * Assigne une tâche spécifique au meilleur candidat
   */
  async assignSpecificTask(taskId: number): Promise<{
    message: string
    task: { id: number; title: string }
    assignedTo: {
      id: number
      firstName: string
      lastName: string
      email: string
    }
    assignmentScore: number
    skillMatch: number
  }> {
    try {
      const response = await axios.post(`${API_CONFIG.BASE_URL}/auto-assignment/assign-task/${taskId}`, {}, {
        headers: this.getAuthHeaders(),
      })
      return response.data
    } catch (error) {
      console.error('Erreur lors de l\'assignation de la tâche:', error)
      throw error
    }
  }

  /**
   * Récupère les détails de charge de travail pour tous les utilisateurs
   */
  async getWorkloadDetails(): Promise<{
    workloadDetails: WorkloadDetail[]
    summary: {
      totalUsers: number
      unassignedTasks: number
      overloadedUsers: number
    }
  }> {
    try {
      const response = await axios.get(`${API_CONFIG.BASE_URL}/auto-assignment/workload-details`, {
        headers: this.getAuthHeaders(),
      })
      return response.data
    } catch (error) {
      console.error('Erreur lors de la récupération des détails de charge:', error)
      throw error
    }
  }

  /**
   * Formate la priorité pour affichage
   */
  formatPriority(priority: string): string {
    const priorities: Record<string, string> = {
      low: 'Faible',
      medium: 'Moyenne',
      high: 'Élevée',
      urgent: 'Urgente',
    }
    return priorities[priority] || priority
  }

  /**
   * Formate le statut pour affichage
   */
  formatStatus(status: string): string {
    const statuses: Record<string, string> = {
      todo: 'À faire',
      in_progress: 'En cours',
      completed: 'Terminé',
      cancelled: 'Annulé',
      planning: 'En planification',
    }
    return statuses[status] || status
  }

  /**
   * Obtient la classe CSS pour la priorité
   */
  getPriorityClass(priority: string): string {
    const classes: Record<string, string> = {
      low: 'priority-low',
      medium: 'priority-medium',
      high: 'priority-high',
      urgent: 'priority-urgent',
    }
    return classes[priority] || 'priority-medium'
  }

  /**
   * Obtient la classe CSS pour le statut de charge
   */
  getWorkloadStatusClass(utilizationPercentage: number): string {
    if (utilizationPercentage >= 100) {
      return 'overloaded'
    } else if (utilizationPercentage >= 87.5) {
      return 'busy'
    } else if (utilizationPercentage >= 75) {
      return 'occupied'
    } else {
      return 'available'
    }
  }

  /**
   * Obtient le statut de charge de travail
   */
  getWorkloadStatus(utilizationPercentage: number): string {
    if (utilizationPercentage >= 100) {
      return 'Surchargé'
    } else if (utilizationPercentage >= 87.5) {
      return 'Presque surchargé'
    } else if (utilizationPercentage >= 75) {
      return 'Occupé'
    } else if (utilizationPercentage >= 50) {
      return 'Modérément occupé'
    } else {
      return 'Disponible'
    }
  }

  /**
   * Formate la durée en heures
   */
  formatHours(hours: number): string {
    if (hours === 0) return '0h'
    if (hours === 1) return '1h'
    return `${hours}h`
  }

  /**
   * Formate la date
   */
  formatDate(dateString?: string): string {
    if (!dateString) return 'N/A'
    
    try {
      const date = new Date(dateString)
      return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      })
    } catch {
      return 'N/A'
    }
  }

  /**
   * Formate la date et l'heure
   */
  formatDateTime(dateString?: string): string {
    if (!dateString) return 'N/A'
    
    try {
      const date = new Date(dateString)
      return date.toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    } catch {
      return 'N/A'
    }
  }

  /**
   * Calcule le pourcentage de correspondance des compétences
   */
  calculateSkillMatchPercentage(skillMatch: number): number {
    return Math.round(skillMatch * 100)
  }

  /**
   * Calcule le score d'assignation en pourcentage
   */
  calculateAssignmentScorePercentage(score: number): number {
    return Math.round(score * 100)
  }

  /**
   * Détermine si une tâche est urgente (échéance proche)
   */
  isTaskUrgent(dueDate?: string): boolean {
    if (!dueDate) return false
    
    try {
      const due = new Date(dueDate)
      const now = new Date()
      const diffInDays = (due.getTime() - now.getTime()) / (1000 * 60 * 60 * 24)
      return diffInDays <= 3 && diffInDays >= 0 // Urgent si échéance dans 3 jours ou moins
    } catch {
      return false
    }
  }

  /**
   * Détermine si une tâche est en retard
   */
  isTaskOverdue(dueDate?: string): boolean {
    if (!dueDate) return false
    
    try {
      const due = new Date(dueDate)
      const now = new Date()
      return due.getTime() < now.getTime()
    } catch {
      return false
    }
  }
}

export const autoAssignmentService = new AutoAssignmentService()
