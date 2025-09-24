export interface UserPermissions {
  canManageProjects: boolean
  canSuperviseTasks: boolean
  canAssignTasks: boolean
  canViewAllTasks: boolean
  canViewReports: boolean
  canManageUsers: boolean
  canManageSkills: boolean
  canViewNotifications: boolean
  canManageNotifications: boolean
  canAccessAdmin: boolean
  canManageRoles: boolean
  primaryRole: string
  roles: string[]
}

export interface User {
  id: number
  email: string
  firstName: string
  lastName: string
  company?: string
  avatar?: string
  roles: string[]
  permissions: UserPermissions
  createdAt: string
  updatedAt: string
}

export interface AuthResponse {
  message: string
  user: User
  token: string
}

export interface ErrorResponse {
  error: string
  message: string
}

import { getSecurityHeaders, isValidJWTFormat, sanitizeInput } from '@/utils/security'

class AuthService {
  private readonly API_BASE_URL = 'http://127.0.0.1:8000/api/auth'

  async register(userData: {
    firstName: string
    lastName: string
    email: string
    company?: string
    password: string
  }): Promise<AuthResponse> {
    // Nettoyer les données d'entrée pour éviter les injections
    const sanitizedData = {
      ...userData,
      firstName: sanitizeInput(userData.firstName),
      lastName: sanitizeInput(userData.lastName),
      email: sanitizeInput(userData.email),
      company: userData.company ? sanitizeInput(userData.company) : undefined,
    }

    const response = await fetch(`${this.API_BASE_URL}/register`, {
      method: 'POST',
      headers: {
        ...getSecurityHeaders(),
      },
      body: JSON.stringify(sanitizedData),
    })

    if (!response.ok) {
      const errorData: ErrorResponse = await response.json()
      throw new Error(errorData.message || "Erreur lors de l'inscription")
    }

    return await response.json()
  }

  async login(credentials: { email: string; password: string }): Promise<AuthResponse> {
    // Nettoyer les données d'entrée
    const sanitizedCredentials = {
      email: sanitizeInput(credentials.email),
      password: credentials.password, // Ne pas nettoyer le mot de passe
    }

    const response = await fetch(`${this.API_BASE_URL}/login`, {
      method: 'POST',
      headers: {
        ...getSecurityHeaders(),
      },
      body: JSON.stringify(sanitizedCredentials),
    })

    if (!response.ok) {
      const errorData: ErrorResponse = await response.json()
      throw new Error(errorData.message || 'Email ou mot de passe incorrect')
    }

    const authData: AuthResponse = await response.json()

    // Valider le format du token JWT avant de le stocker
    if (authData.token && isValidJWTFormat(authData.token)) {
      localStorage.setItem('authToken', authData.token)
      localStorage.setItem('user', JSON.stringify(authData.user))
    } else {
      throw new Error('Token JWT invalide reçu du serveur')
    }

    return authData
  }

  async logout(): Promise<void> {
    try {
      // Appeler l'endpoint de déconnexion pour logger l'activité
      await fetch(`${this.API_BASE_URL}/logout`, {
        method: 'POST',
        headers: this.getAuthHeaders(),
      })
    } catch (error) {
      // Ignorer les erreurs de déconnexion (token expiré, etc.)
      console.log('Erreur lors de la déconnexion côté serveur (normal si token expiré):', error)
    } finally {
      // Nettoyer le localStorage dans tous les cas
      localStorage.removeItem('authToken')
      localStorage.removeItem('user')
      // Nettoyer les anciens tokens fake
      localStorage.removeItem('token')
    }
  }

  isAuthenticated(): boolean {
    const token = this.getAuthToken()
    const user = localStorage.getItem('user')
    
    // Vérifier que le token et l'utilisateur existent
    if (!token || !user || user === 'undefined' || user === 'null') {
      // Nettoyer automatiquement les données corrompues
      this.cleanupCorruptedData()
      return false
    }
    
    // Vérifier que le token JWT a un format valide
    if (!isValidJWTFormat(token)) {
      console.warn('Token JWT invalide détecté, nettoyage automatique du localStorage')
      this.logout().catch(() => {}) // Ignorer les erreurs de déconnexion
      return false
    }
    
    return true
  }

  getCurrentUser(): User | null {
    const userStr = localStorage.getItem('user')
    if (!userStr || userStr === 'undefined' || userStr === 'null') {
      return null
    }
    try {
      return JSON.parse(userStr)
    } catch (error) {
      console.error("Erreur lors du parsing de l'utilisateur:", error)
      // Nettoyer les données corrompues
      localStorage.removeItem('user')
      return null
    }
  }

  getAuthToken(): string | null {
    const token = localStorage.getItem('authToken')
    
    // Vérifier que le token existe et a un format valide
    if (!token || !isValidJWTFormat(token)) {
      console.warn('Token JWT invalide ou manquant')
      return null
    }
    
    return token
  }

  // Fonction utilitaire pour créer des headers avec authentification
  getAuthHeaders(): HeadersInit {
    const token = this.getAuthToken()
    return {
      'Content-Type': 'application/json',
      ...(token && { Authorization: `Bearer ${token}` }),
    }
  }

  // Nettoyer automatiquement les données corrompues
  cleanupCorruptedData(): void {
    const userStr = localStorage.getItem('user')
    const token = localStorage.getItem('authToken')
    
    // Nettoyer les données utilisateur corrompues
    if (userStr === 'undefined' || userStr === 'null' || !userStr) {
      localStorage.removeItem('user')
      console.log('Données utilisateur corrompues supprimées automatiquement')
    }

    // Nettoyer les tokens invalides
    if (token && !isValidJWTFormat(token)) {
      localStorage.removeItem('authToken')
      console.log('Token JWT invalide supprimé automatiquement')
    }

    // Nettoyer les anciens tokens fake
    const oldToken = localStorage.getItem('token')
    if (oldToken && oldToken.startsWith('fake-jwt-token')) {
      localStorage.removeItem('token')
      console.log('Ancien token fake supprimé automatiquement')
    }

    // Si on a un token mais pas d'utilisateur, nettoyer tout
    if (token && !userStr) {
      console.log('Token sans utilisateur détecté - nettoyage complet automatique')
      this.logout().catch(() => {}) // Ignorer les erreurs de déconnexion
    }
  }

  // Nettoyer les anciens tokens fake au démarrage
  cleanupOldTokens(): void {
    this.cleanupCorruptedData()
  }

  // Forcer un nettoyage complet et une reconnexion
  forceReconnect(): void {
    console.log('🔄 Forçage d\'une reconnexion complète...')
    this.logout().catch(() => {}) // Ignorer les erreurs de déconnexion
    
    // Nettoyer aussi sessionStorage
    sessionStorage.clear()
    
    // Rediriger vers la page de connexion
    setTimeout(() => {
      window.location.href = '/login'
    }, 500)
  }
}

export const authService = new AuthService()
