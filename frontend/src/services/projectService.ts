import { authService } from './authService';

export interface Project {
  id: number;
  name: string;
  description: string;
  status: string;
  createdAt: string;
  updatedAt: string;
  projectManager?: {
    id: number;
    firstName: string;
    lastName: string;
    email: string;
  };
  teamMembers?: Array<{
    id: number;
    firstName: string;
    lastName: string;
    email: string;
  }>;
}

export interface CreateProjectData {
  name: string;
  description: string;
  status?: string;
}

class ProjectService {
  private readonly API_BASE_URL = '/api/projects';

  async getProjects(): Promise<Project[]> {
    const response = await fetch(this.API_BASE_URL, {
      method: 'GET',
      headers: authService.getAuthHeaders(),
    });

    if (!response.ok) {
      if (response.status === 401) {
        // Rediriger vers la page de connexion si non authentifié
        authService.logout();
        window.location.href = '/login';
        throw new Error('Non authentifié');
      }
      throw new Error('Erreur lors de la récupération des projets');
    }

    return await response.json();
  }

  async createProject(data: CreateProjectData): Promise<{ message: string; project: Project }> {
    const response = await fetch(this.API_BASE_URL, {
      method: 'POST',
      headers: authService.getAuthHeaders(),
      body: JSON.stringify(data),
    });

    if (!response.ok) {
      if (response.status === 401) {
        // Rediriger vers la page de connexion si non authentifié
        authService.logout();
        window.location.href = '/login';
        throw new Error('Non authentifié');
      }
      const errorData = await response.json();
      throw new Error(errorData.message || 'Erreur lors de la création du projet');
    }

    return await response.json();
  }

  async getProject(id: number): Promise<Project> {
    const response = await fetch(`${this.API_BASE_URL}/${id}`, {
      method: 'GET',
      headers: authService.getAuthHeaders(),
    });

    if (!response.ok) {
      if (response.status === 401) {
        throw new Error('Non authentifié');
      }
      throw new Error('Erreur lors de la récupération du projet');
    }

    return await response.json();
  }

  async updateProject(id: number, data: Partial<CreateProjectData>): Promise<{ message: string; project: Project }> {
    const response = await fetch(`${this.API_BASE_URL}/${id}`, {
      method: 'PUT',
      headers: authService.getAuthHeaders(),
      body: JSON.stringify(data),
    });

    if (!response.ok) {
      if (response.status === 401) {
        throw new Error('Non authentifié');
      }
      const errorData = await response.json();
      throw new Error(errorData.message || 'Erreur lors de la mise à jour du projet');
    }

    return await response.json();
  }

  async deleteProject(id: number): Promise<{ message: string }> {
    const response = await fetch(`${this.API_BASE_URL}/${id}`, {
      method: 'DELETE',
      headers: authService.getAuthHeaders(),
    });

    if (!response.ok) {
      if (response.status === 401) {
        // Rediriger vers la page de connexion si non authentifié
        authService.logout();
        window.location.href = '/login';
        throw new Error('Non authentifié');
      }
      const errorData = await response.json();
      throw new Error(errorData.message || 'Erreur lors de la suppression du projet');
    }

    return await response.json();
  }

  // Constantes pour les statuts de projet
  static readonly STATUS = {
    PLANNING: 'planning',
    IN_PROGRESS: 'in_progress',
    COMPLETED: 'completed',
    ON_HOLD: 'on_hold'
  } as const;

  static readonly STATUS_LABELS = {
    [this.STATUS.PLANNING]: 'En planification',
    [this.STATUS.IN_PROGRESS]: 'En cours',
    [this.STATUS.COMPLETED]: 'Terminé',
    [this.STATUS.ON_HOLD]: 'En attente'
  } as const;
}

export const projectService = new ProjectService();
