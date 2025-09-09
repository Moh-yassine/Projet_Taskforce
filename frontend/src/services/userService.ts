import axios from 'axios'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8003/api'

export interface AssignableUser {
  id: number
  firstName: string
  lastName: string
  email: string
  roles: string[]
  currentWeekHours: number
  maxWeekHours: number
  utilizationPercentage: number
  remainingCapacity: number
  canReceiveTasks: boolean
  skills: any[]
}

export interface UserWorkload {
  userId: number
  userName: string
  currentWeekHours: number
  maxWeekHours: number
  utilizationPercentage: number
  remainingCapacity: number
  tasks: Array<{
    id: number
    title: string
    description: string
    estimatedHours: number
    actualHours: number
    dueDate: string | null
    status: string
    priority: string
    project: {
      id: number
      name: string
    } | null
  }>
  skills: any[]
}

class UserService {
  /**
   * Récupère les utilisateurs qui peuvent recevoir des tâches
   */
  async getAssignableUsers(): Promise<AssignableUser[]> {
    try {
      const token = localStorage.getItem('authToken')
      const response = await axios.get(`${API_BASE_URL}/users/assignable`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })
      return response.data
    } catch (error) {
      console.error('Erreur lors de la récupération des utilisateurs assignables:', error)
      throw error
    }
  }

  /**
   * Récupère la charge de travail détaillée d'un utilisateur
   */
  async getUserWorkload(userId: number): Promise<UserWorkload> {
    try {
      const token = localStorage.getItem('authToken')
      const response = await axios.get(`${API_BASE_URL}/users/${userId}/workload`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })
      return response.data
    } catch (error) {
      console.error('Erreur lors de la récupération de la charge de travail:', error)
      throw error
    }
  }

  /**
   * Récupère les tâches d'un utilisateur
   */
  async getUserTasks(userId: number): Promise<any[]> {
    try {
      const token = localStorage.getItem('authToken')
      const response = await axios.get(`${API_BASE_URL}/users/${userId}/tasks`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })
      return response.data
    } catch (error) {
      console.error('Erreur lors de la récupération des tâches de l\'utilisateur:', error)
      throw error
    }
  }

  /**
   * Calcule le score d'assignation pour un utilisateur
   */
  calculateAssignmentScore(user: AssignableUser, estimatedHours: number): number {
    let score = 0
    
    // Score basé sur la capacité restante (70% du score)
    const capacityScore = (user.remainingCapacity / user.maxWeekHours) * 70
    score += capacityScore
    
    // Pénalité si l'assignation dépasse la capacité
    if (estimatedHours > user.remainingCapacity) {
      score -= 50
    }
    
    // Bonus si l'utilisateur a des compétences (20% du score)
    if (user.skills && user.skills.length > 0) {
      score += 20
    }
    
    // Bonus pour les managers (10% du score)
    if (user.roles.includes('ROLE_MANAGER')) {
      score += 10
    }
    
    return Math.max(0, score)
  }

  /**
   * Trie les utilisateurs par score d'assignation
   */
  sortUsersByAssignmentScore(users: AssignableUser[], estimatedHours: number): AssignableUser[] {
    return users
      .map(user => ({
        ...user,
        assignmentScore: this.calculateAssignmentScore(user, estimatedHours)
      }))
      .sort((a, b) => b.assignmentScore - a.assignmentScore)
  }

  /**
   * Filtre les utilisateurs qui peuvent recevoir une tâche
   */
  getEligibleUsers(users: AssignableUser[], estimatedHours: number): AssignableUser[] {
    return users.filter(user => 
      user.canReceiveTasks && 
      user.remainingCapacity >= estimatedHours
    )
  }

  /**
   * Obtient les recommandations d'assignation
   */
  getAssignmentRecommendations(users: AssignableUser[], estimatedHours: number, limit: number = 3): AssignableUser[] {
    const eligibleUsers = this.getEligibleUsers(users, estimatedHours)
    const sortedUsers = this.sortUsersByAssignmentScore(eligibleUsers, estimatedHours)
    
    return sortedUsers.slice(0, limit)
  }

  /**
   * Formate le nom complet d'un utilisateur
   */
  getFullName(user: AssignableUser): string {
    return `${user.firstName} ${user.lastName}`
  }

  /**
   * Obtient le rôle affiché d'un utilisateur
   */
  getDisplayRole(user: AssignableUser): string {
    if (user.roles.includes('ROLE_MANAGER')) {
      return 'Manager'
    } else if (user.roles.includes('ROLE_COLLABORATOR')) {
      return 'Collaborateur'
    }
    return 'Utilisateur'
  }

  /**
   * Vérifie si un utilisateur peut recevoir une tâche
   */
  canAssignToUser(user: AssignableUser, estimatedHours: number): boolean {
    return user.canReceiveTasks && 
           user.remainingCapacity >= estimatedHours &&
           user.utilizationPercentage < 100
  }

  /**
   * Obtient la couleur d'alerte basée sur le pourcentage d'utilisation
   */
  getUtilizationColor(percentage: number): string {
    if (percentage >= 100) return '#e74c3c' // Rouge
    if (percentage >= 90) return '#f39c12' // Orange
    if (percentage >= 75) return '#f1c40f' // Jaune
    return '#27ae60' // Vert
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
   * Récupère les tâches de l'utilisateur connecté
   */
  async getMyTasks(): Promise<any[]> {
    try {
      const token = localStorage.getItem('authToken')
      const response = await axios.get(`${API_BASE_URL}/users/my-tasks`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })
      return response.data
    } catch (error) {
      console.error('Erreur lors de la récupération de mes tâches:', error)
      throw error
    }
  }
}

export const userService = new UserService()
