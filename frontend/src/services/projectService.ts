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
  private readonly API_BASE_URL = 'http://localhost:8003/api/projects';

  async getProjects(): Promise<Project[]> {
    // Temporairement, sans authentification pour les tests
    const response = await fetch(this.API_BASE_URL, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('Erreur lors de la récupération des projets');
    }

    return await response.json();
  }

  async createProject(data: CreateProjectData): Promise<{ message: string; project: Project }> {
    // Temporairement, sans authentification pour les tests
    const response = await fetch(this.API_BASE_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'Erreur lors de la création du projet');
    }

    return await response.json();
  }

  async getProject(id: number): Promise<Project> {
    const token = localStorage.getItem('authToken');
    if (!token) {
      throw new Error('Token d\'authentification manquant');
    }

    const response = await fetch(`${this.API_BASE_URL}/${id}`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
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
    const token = localStorage.getItem('authToken');
    if (!token) {
      throw new Error('Token d\'authentification manquant');
    }

    const response = await fetch(`${this.API_BASE_URL}/${id}`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
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
    // Temporairement, sans authentification pour les tests
    const response = await fetch(`${this.API_BASE_URL}/${id}`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
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
