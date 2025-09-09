export interface UserPermissions {
  canManageProjects: boolean;
  canSuperviseTasks: boolean;
  canAssignTasks: boolean;
  canViewAllTasks: boolean;
  canViewReports: boolean;
  canManageUsers: boolean;
  canManageSkills: boolean;
  canViewNotifications: boolean;
  canManageNotifications: boolean;
  canAccessAdmin: boolean;
  canManageRoles: boolean;
  primaryRole: string;
  roles: string[];
}

export interface User {
  id: number;
  email: string;
  firstName: string;
  lastName: string;
  company?: string;
  avatar?: string;
  roles: string[];
  permissions: UserPermissions;
  createdAt: string;
  updatedAt: string;
}

export interface AuthResponse {
  message: string;
  user: User;
  token: string;
}

export interface ErrorResponse {
  error: string;
  message: string;
}

class AuthService {
  private readonly API_BASE_URL = '/api/auth';

  async register(userData: {
    firstName: string;
    lastName: string;
    email: string;
    company?: string;
    password: string;
  }): Promise<AuthResponse> {
    const response = await fetch(`${this.API_BASE_URL}/register`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(userData),
    });

    if (!response.ok) {
      const errorData: ErrorResponse = await response.json();
      throw new Error(errorData.message || 'Erreur lors de l\'inscription');
    }

    return await response.json();
  }

  async login(credentials: { email: string; password: string }): Promise<AuthResponse> {
    const response = await fetch(`${this.API_BASE_URL}/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(credentials),
    });

    if (!response.ok) {
      const errorData: ErrorResponse = await response.json();
      throw new Error(errorData.message || 'Email ou mot de passe incorrect');
    }

    const authData: AuthResponse = await response.json();
    
    // Le token JWT est dans le corps de la réponse
    if (authData.token) {
      localStorage.setItem('authToken', authData.token);
      localStorage.setItem('user', JSON.stringify(authData.user));
    }
    
    return authData;
  }

  logout(): void {
    localStorage.removeItem('authToken');
    localStorage.removeItem('user');
    // Nettoyer les anciens tokens fake
    localStorage.removeItem('token');
  }

  isAuthenticated(): boolean {
    return !!this.getAuthToken();
  }

  getCurrentUser(): User | null {
    const userStr = localStorage.getItem('user');
    if (!userStr || userStr === 'undefined' || userStr === 'null') {
      return null;
    }
    try {
      return JSON.parse(userStr);
    } catch (error) {
      console.error('Erreur lors du parsing de l\'utilisateur:', error);
      // Nettoyer les données corrompues
      localStorage.removeItem('user');
      return null;
    }
  }

  getAuthToken(): string | null {
    return localStorage.getItem('authToken');
  }

  // Fonction utilitaire pour créer des headers avec authentification
  getAuthHeaders(): HeadersInit {
    const token = this.getAuthToken();
    return {
      'Content-Type': 'application/json',
      ...(token && { 'Authorization': `Bearer ${token}` })
    };
  }

  // Nettoyer les anciens tokens fake au démarrage
  cleanupOldTokens(): void {
    const oldToken = localStorage.getItem('token');
    if (oldToken && oldToken.startsWith('fake-jwt-token')) {
      localStorage.removeItem('token');
      console.log('Ancien token fake supprimé');
    }
    
    // Nettoyer les données utilisateur corrompues
    const userStr = localStorage.getItem('user');
    if (userStr === 'undefined' || userStr === 'null' || !userStr) {
      localStorage.removeItem('user');
      console.log('Données utilisateur corrompues supprimées');
    }
    
    // Si on a un token mais pas d'utilisateur, nettoyer tout
    const token = localStorage.getItem('authToken');
    if (token && !userStr) {
      console.log('Token sans utilisateur détecté - nettoyage complet');
      this.logout();
    }
  }
}

export const authService = new AuthService();
