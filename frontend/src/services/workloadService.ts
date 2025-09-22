import axios from 'axios'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api'

export interface WorkloadAlert {
  id: number
  userId: number
  type: 'overload' | 'delay' | 'approaching_deadline'
  message: string
  severity: 'low' | 'medium' | 'high'
  isRead: boolean
  createdAt: string
  taskId?: number
  projectId?: number
}

export interface UserWorkload {
  userId: number
  userName: string
  currentWeekHours: number
  maxWeekHours: number
  utilizationPercentage: number
  tasks: Array<{
    id: number
    title: string
    estimatedHours: number
    actualHours: number
    dueDate: string
    status: string
  }>
  alerts: WorkloadAlert[]
}

export interface TaskAssignment {
  taskId: number
  userId: number
  estimatedHours: number
  dueDate: string
  priority: 'low' | 'medium' | 'high' | 'urgent'
}

class WorkloadService {
  // Constantes pour la charge de travail
  private readonly MAX_WEEKLY_HOURS = 35 // 8h/jour * 5 jours - 5h de marge
  private readonly MAX_DAILY_HOURS = 8
  private readonly ALERT_THRESHOLD = 0.9 // 90% de la charge maximale

  /**
   * Calcule la charge de travail d'un utilisateur pour la semaine courante
   */
  async getUserWorkload(userId: number): Promise<UserWorkload> {
    try {
      const token = localStorage.getItem('authToken')
      const response = await axios.get(`${API_BASE_URL}/workload/user/${userId}`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      })
      return response.data
    } catch (error) {
      console.error('Erreur lors du calcul de la charge de travail:', error)
      throw error
    }
  }

  /**
   * Obtient la charge de travail de tous les utilisateurs
   */
  async getAllUsersWorkload(): Promise<UserWorkload[]> {
    try {
      const token = localStorage.getItem('authToken')
      const response = await axios.get(`${API_BASE_URL}/workload/all`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      })
      return response.data
    } catch (error) {
      console.error('Erreur lors de la récupération des charges de travail:', error)
      throw error
    }
  }

  /**
   * Assigne une tâche à un utilisateur avec vérification de la charge de travail
   */
  async assignTask(
    assignment: TaskAssignment,
  ): Promise<{ success: boolean; message: string; alert?: WorkloadAlert }> {
    try {
      // Vérifier la charge de travail avant l'assignation
      const workload = await this.getUserWorkload(assignment.userId)
      const newTotalHours = workload.currentWeekHours + assignment.estimatedHours

      // Vérifier si l'assignation dépasse la charge maximale
      if (newTotalHours > this.MAX_WEEKLY_HOURS) {
        return {
          success: false,
          message: `Assignation impossible : ${workload.userName} dépasserait sa charge de travail maximale (${newTotalHours}h > ${this.MAX_WEEKLY_HOURS}h)`,
        }
      }

      // Vérifier le seuil d'alerte
      const utilizationPercentage = (newTotalHours / this.MAX_WEEKLY_HOURS) * 100
      let alert: WorkloadAlert | undefined

      if (utilizationPercentage >= this.ALERT_THRESHOLD * 100) {
        alert = {
          id: Date.now(), // ID temporaire
          userId: assignment.userId,
          type: 'overload',
          message: `${workload.userName} approche de sa charge de travail maximale (${utilizationPercentage.toFixed(1)}%)`,
          severity: utilizationPercentage >= 100 ? 'high' : 'medium',
          isRead: false,
          createdAt: new Date().toISOString(),
          taskId: assignment.taskId,
        }
      }

      // Effectuer l'assignation
      const token = localStorage.getItem('authToken')
      const response = await axios.post(
        `${API_BASE_URL}/tasks/${assignment.taskId}/assign`,
        {
          userId: assignment.userId,
          estimatedHours: assignment.estimatedHours,
          dueDate: assignment.dueDate,
          priority: assignment.priority,
        },
        {
          headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json',
          },
        },
      )

      return {
        success: true,
        message: 'Tâche assignée avec succès',
        alert,
      }
    } catch (error) {
      console.error("Erreur lors de l'assignation de la tâche:", error)
      throw error
    }
  }

  /**
   * Met à jour les heures réelles d'une tâche
   */
  async updateTaskHours(taskId: number, actualHours: number): Promise<void> {
    try {
      await axios.put(`${API_BASE_URL}/tasks/${taskId}/hours`, {
        actualHours,
      })
    } catch (error) {
      console.error('Erreur lors de la mise à jour des heures:', error)
      throw error
    }
  }

  /**
   * Obtient les alertes de charge de travail
   */
  async getWorkloadAlerts(): Promise<WorkloadAlert[]> {
    try {
      const response = await axios.get(`${API_BASE_URL}/workload/alerts`)
      return response.data
    } catch (error) {
      console.error('Erreur lors de la récupération des alertes:', error)
      throw error
    }
  }

  /**
   * Marque une alerte comme lue
   */
  async markAlertAsRead(alertId: number): Promise<void> {
    try {
      await axios.put(`${API_BASE_URL}/workload/alerts/${alertId}/read`)
    } catch (error) {
      console.error("Erreur lors de la mise à jour de l'alerte:", error)
      throw error
    }
  }

  /**
   * Vérifie les retards de tâches et génère des alertes
   */
  async checkTaskDelays(): Promise<WorkloadAlert[]> {
    try {
      const response = await axios.post(`${API_BASE_URL}/workload/check-delays`)
      return response.data
    } catch (error) {
      console.error('Erreur lors de la vérification des retards:', error)
      throw error
    }
  }

  /**
   * Calcule la charge de travail optimale pour une assignation
   */
  calculateOptimalAssignment(availableUsers: any[], estimatedHours: number): any[] {
    return availableUsers
      .map((user) => ({
        ...user,
        score: this.calculateAssignmentScore(user, estimatedHours),
      }))
      .sort((a, b) => b.score - a.score)
  }

  /**
   * Calcule un score d'assignation basé sur la charge de travail et les compétences
   */
  private calculateAssignmentScore(user: any, estimatedHours: number): number {
    const currentUtilization = (user.currentWeekHours / this.MAX_WEEKLY_HOURS) * 100
    const remainingCapacity = this.MAX_WEEKLY_HOURS - user.currentWeekHours

    // Score basé sur la capacité restante (plus c'est élevé, mieux c'est)
    let score = (remainingCapacity / this.MAX_WEEKLY_HOURS) * 100

    // Pénalité si l'assignation dépasse la capacité
    if (estimatedHours > remainingCapacity) {
      score -= 50
    }

    // Bonus si l'utilisateur a les compétences requises
    if (user.skills && user.skills.length > 0) {
      score += 20
    }

    return Math.max(0, score)
  }

  /**
   * Formate les heures pour l'affichage
   */
  formatHours(hours: number): string {
    if (hours < 1) {
      return `${Math.round(hours * 60)}min`
    }
    return `${hours.toFixed(1)}h`
  }

  /**
   * Calcule le pourcentage d'utilisation
   */
  calculateUtilizationPercentage(currentHours: number): number {
    return (currentHours / this.MAX_WEEKLY_HOURS) * 100
  }

  /**
   * Détermine la couleur d'alerte basée sur le pourcentage d'utilisation
   */
  getUtilizationColor(percentage: number): string {
    if (percentage >= 100) return '#e74c3c' // Rouge
    if (percentage >= 90) return '#f39c12' // Orange
    if (percentage >= 75) return '#f1c40f' // Jaune
    return '#27ae60' // Vert
  }
}

export const workloadService = new WorkloadService()
