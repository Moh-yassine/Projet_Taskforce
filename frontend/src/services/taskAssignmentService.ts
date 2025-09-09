import { authService } from './authService';

export interface TaskAssignmentResult {
  message: string;
  task?: any;
  user?: any;
  redistributed?: any[];
  assigned?: any[];
  failed?: any[];
  assigned_count?: number;
  failed_count?: number;
}

class TaskAssignmentService {
  private readonly API_BASE_URL = '/api/task-assignment';

  async autoAssignTask(taskId: number): Promise<TaskAssignmentResult> {
    const response = await fetch(`${this.API_BASE_URL}/auto-assign/${taskId}`, {
      method: 'POST',
      headers: authService.getAuthHeaders(),
    });

    if (!response.ok) {
      if (response.status === 401) {
        authService.logout();
        window.location.href = '/login';
        throw new Error('Non authentifié');
      }
      const errorData = await response.json();
      throw new Error(errorData.error || 'Erreur lors de l\'assignation automatique de la tâche');
    }

    return await response.json();
  }

  async findBestUserForTask(taskId: number): Promise<{ user: any; task: any }> {
    const response = await fetch(`${this.API_BASE_URL}/find-best-user/${taskId}`, {
      method: 'GET',
      headers: authService.getAuthHeaders(),
    });

    if (!response.ok) {
      if (response.status === 401) {
        authService.logout();
        window.location.href = '/login';
        throw new Error('Non authentifié');
      }
      const errorData = await response.json();
      throw new Error(errorData.error || 'Erreur lors de la recherche du meilleur utilisateur');
    }

    return await response.json();
  }

  async redistributeTasks(): Promise<TaskAssignmentResult> {
    const response = await fetch(`${this.API_BASE_URL}/redistribute`, {
      method: 'POST',
      headers: authService.getAuthHeaders(),
    });

    if (!response.ok) {
      if (response.status === 401) {
        authService.logout();
        window.location.href = '/login';
        throw new Error('Non authentifié');
      }
      const errorData = await response.json();
      throw new Error(errorData.error || 'Erreur lors de la redistribution des tâches');
    }

    return await response.json();
  }

  async getUnassignedTasks(): Promise<any[]> {
    const response = await fetch(`${this.API_BASE_URL}/unassigned`, {
      method: 'GET',
      headers: authService.getAuthHeaders(),
    });

    if (!response.ok) {
      if (response.status === 401) {
        authService.logout();
        window.location.href = '/login';
        throw new Error('Non authentifié');
      }
      throw new Error('Erreur lors de la récupération des tâches non assignées');
    }

    return await response.json();
  }

  async autoAssignAllUnassignedTasks(): Promise<TaskAssignmentResult> {
    const response = await fetch(`${this.API_BASE_URL}/auto-assign-all`, {
      method: 'POST',
      headers: authService.getAuthHeaders(),
    });

    if (!response.ok) {
      if (response.status === 401) {
        authService.logout();
        window.location.href = '/login';
        throw new Error('Non authentifié');
      }
      const errorData = await response.json();
      throw new Error(errorData.error || 'Erreur lors de l\'assignation automatique de toutes les tâches');
    }

    return await response.json();
  }

  // Méthodes utilitaires
  calculateSkillMatchScore(userSkills: any[], requiredSkills: any[]): number {
    if (requiredSkills.length === 0) return 0;

    let totalScore = 0;
    let matchedSkills = 0;

    for (const requiredSkill of requiredSkills) {
      const userSkill = userSkills.find(us => us.skill.id === requiredSkill.id);
      if (userSkill) {
        totalScore += userSkill.level;
        matchedSkills++;
      }
    }

    return matchedSkills > 0 ? totalScore / matchedSkills : 0;
  }

  getAssignmentStatusColor(status: string): string {
    switch (status) {
      case 'auto_assigned': return '#3b82f6'; // Bleu
      case 'manual_assigned': return '#22c55e'; // Vert
      case 'unassigned': return '#6b7280'; // Gris
      case 'overloaded': return '#ef4444'; // Rouge
      default: return '#6b7280'; // Gris
    }
  }

  getAssignmentStatusLabel(status: string): string {
    switch (status) {
      case 'auto_assigned': return 'Assigné automatiquement';
      case 'manual_assigned': return 'Assigné manuellement';
      case 'unassigned': return 'Non assigné';
      case 'overloaded': return 'Surchargé';
      default: return 'Inconnu';
    }
  }

  formatAssignmentDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  // Constantes
  static readonly ASSIGNMENT_TYPES = {
    AUTO: 'auto_assigned',
    MANUAL: 'manual_assigned',
    UNASSIGNED: 'unassigned'
  } as const;

  static readonly ASSIGNMENT_STATUS = {
    SUCCESS: 'success',
    FAILED: 'failed',
    PARTIAL: 'partial'
  } as const;
}

export const taskAssignmentService = new TaskAssignmentService();
