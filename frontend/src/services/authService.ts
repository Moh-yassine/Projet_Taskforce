export interface User {
  id: number;
  email: string;
  firstName: string;
  lastName: string;
  company?: string;
  roles: string[];
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
  private readonly API_BASE_URL = 'http://localhost:8000/api/auth';

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
    
    localStorage.setItem('authToken', authData.token);
    localStorage.setItem('user', JSON.stringify(authData.user));
    
    return authData;
  }

  logout(): void {
    localStorage.removeItem('authToken');
    localStorage.removeItem('user');
  }

  isAuthenticated(): boolean {
    return !!this.getAuthToken();
  }

  getCurrentUser(): User | null {
    const userStr = localStorage.getItem('user');
    return userStr ? JSON.parse(userStr) : null;
  }

  getAuthToken(): string | null {
    return localStorage.getItem('authToken');
  }
}

export const authService = new AuthService();
