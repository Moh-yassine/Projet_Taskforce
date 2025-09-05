export interface Skill {
  id: number;
  name: string;
  description?: string;
}

class SkillService {
  private readonly API_BASE_URL = 'http://localhost:8003/api/skills';

  async getSkills(): Promise<Skill[]> {
    const response = await fetch(this.API_BASE_URL, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error('Erreur lors de la récupération des compétences');
    }

    return await response.json();
  }

  async createSkill(data: { name: string; description?: string }): Promise<Skill> {
    const response = await fetch(this.API_BASE_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'Erreur lors de la création de la compétence');
    }

    return await response.json();
  }

  async updateSkill(id: number, data: { name?: string; description?: string }): Promise<Skill> {
    const response = await fetch(`${this.API_BASE_URL}/${id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'Erreur lors de la mise à jour de la compétence');
    }

    return await response.json();
  }

  async deleteSkill(id: number): Promise<{ message: string }> {
    const response = await fetch(`${this.API_BASE_URL}/${id}`, {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    if (!response.ok) {
      const errorData = await response.json();
      throw new Error(errorData.message || 'Erreur lors de la suppression de la compétence');
    }

    return await response.json();
  }
}

export const skillService = new SkillService();
