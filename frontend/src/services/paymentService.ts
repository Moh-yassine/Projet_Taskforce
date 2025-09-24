import { authService } from './authService'

export interface PaymentConfig {
  publishableKey: string
  user: {
    id: number
    email: string
    name: string
  }
  hasActiveSubscription: boolean
}

export interface SubscriptionResult {
  success: boolean
  subscription_id?: string
  client_secret?: string
  status?: string
  error?: string
}

export interface PremiumFeatures {
  success: boolean
  features?: {
    advanced_reports: {
      name: string
      description: string
      enabled: boolean
    }
    priority_support: {
      name: string
      description: string
      enabled: boolean
    }
    unlimited_projects: {
      name: string
      description: string
      enabled: boolean
    }
    advanced_analytics: {
      name: string
      description: string
      enabled: boolean
    }
    api_access: {
      name: string
      description: string
      enabled: boolean
    }
    custom_branding: {
      name: string
      description: string
      enabled: boolean
    }
    observer_mode: {
      name: string
      description: string
      enabled: boolean
    }
  }
  error?: string
}

class PaymentService {
  private readonly API_BASE_URL = 'http://127.0.0.1:8000/api/payment'
  private readonly STRIPE_PUBLIC_KEY = 'pk_test_51S5oNDRyNP7K69mNAktlFsF76GumOdNPlggTFso5XPKeLbRB842U6wysSFXJlSLPnQJCW65RpOv5wc3jl8ULElT000mLHgVg1T'

  async getConfig(): Promise<PaymentConfig> {
    const response = await fetch(`${this.API_BASE_URL}/config`, {
      method: 'GET',
      headers: authService.getAuthHeaders(),
    })

    if (!response.ok) {
      if (response.status === 401) {
        authService.logout().catch(() => {}) // Ignorer les erreurs de déconnexion
        window.location.href = '/login'
        throw new Error('Non authentifié')
      }
      
      // Gérer les erreurs de parsing JSON
      let errorMessage = 'Erreur lors de la récupération de la configuration de paiement'
      try {
        const errorData = await response.json()
        errorMessage = errorData.message || errorMessage
      } catch (e) {
        // Si ce n'est pas du JSON, utiliser le message par défaut
        console.error('Erreur de parsing JSON:', e)
        errorMessage = `Erreur serveur (${response.status}): ${response.statusText}`
      }
      
      throw new Error(errorMessage)
    }

    return await response.json()
  }

  async createSubscription(): Promise<{ success: boolean; checkout_url?: string; error?: string }> {
    const response = await fetch(`${this.API_BASE_URL}/create-subscription`, {
      method: 'POST',
      headers: {
        ...authService.getAuthHeaders(),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({}),
    })

    if (!response.ok) {
      if (response.status === 401) {
        authService.logout().catch(() => {}) // Ignorer les erreurs de déconnexion
        window.location.href = '/login'
        throw new Error('Non authentifié')
      }
      
      let errorMessage = 'Erreur lors de la création de l\'abonnement'
      try {
        const errorData = await response.json()
        errorMessage = errorData.error || errorData.message || errorMessage
      } catch (e) {
        console.error('Erreur de parsing JSON:', e)
        errorMessage = `Erreur serveur (${response.status}): ${response.statusText}`
      }
      throw new Error(errorMessage)
    }

    return await response.json()
  }

  async getSubscriptionStatus(): Promise<{ success: boolean; subscription?: any; error?: string }> {
    const response = await fetch(`${this.API_BASE_URL}/subscription-status`, {
      method: 'GET',
      headers: authService.getAuthHeaders(),
    })

    if (!response.ok) {
      const errorData = await response.json()
      throw new Error(errorData.error || 'Erreur lors de la récupération du statut d\'abonnement')
    }

    return await response.json()
  }

  async getPremiumFeatures(): Promise<PremiumFeatures> {
    const response = await fetch(`${this.API_BASE_URL}/premium-features`, {
      method: 'GET',
      headers: authService.getAuthHeaders(),
    })

    if (!response.ok) {
      const errorData = await response.json()
      throw new Error(errorData.error || 'Erreur lors de la récupération des fonctionnalités premium')
    }

    return await response.json()
  }

  async cancelSubscription(): Promise<{ success: boolean; message?: string; error?: string }> {
    const response = await fetch(`${this.API_BASE_URL}/cancel-subscription`, {
      method: 'POST',
      headers: authService.getAuthHeaders(),
    })

    if (!response.ok) {
      const errorData = await response.json()
      throw new Error(errorData.error || 'Erreur lors de l\'annulation de l\'abonnement')
    }

    return await response.json()
  }

}

export const paymentService = new PaymentService()
