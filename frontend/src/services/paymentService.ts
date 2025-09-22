import { loadStripe } from '@stripe/stripe-js'
import type { Stripe } from '@stripe/stripe-js'
import axios from 'axios'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api'

let stripePromise: Promise<Stripe | null> | null = null

export interface PaymentConfig {
  publishableKey: string
  user: {
    id: number
    email: string
    name: string
  }
  hasActiveSubscription: boolean
}

export interface SubscriptionData {
  id: number
  status: string
  plan: string
  amount: number
  currency: string
  currentPeriodStart: string
  currentPeriodEnd: string
  stripeSubscriptionId: string
}

export interface PremiumFeature {
  name: string
  description: string
  enabled: boolean
}

export interface PremiumFeatures {
  advanced_reports: PremiumFeature
  priority_support: PremiumFeature
  unlimited_projects: PremiumFeature
  advanced_analytics: PremiumFeature
  api_access: PremiumFeature
  custom_branding: PremiumFeature
}

class PaymentService {
  private stripe: Stripe | null = null

  async initializeStripe(): Promise<Stripe | null> {
    if (stripePromise) {
      return stripePromise
    }

    try {
      const config = await this.getPaymentConfig()
      stripePromise = loadStripe(config.publishableKey)
      this.stripe = await stripePromise
      return this.stripe
    } catch (error) {
      console.error("Erreur lors de l'initialisation de Stripe:", error)
      return null
    }
  }

  async getPaymentConfig(): Promise<PaymentConfig> {
    const token = localStorage.getItem('authToken')
    const response = await axios.get(
      `${API_BASE_URL}/payment/config?t=${Date.now()}&v=${Math.random()}`,
      {
        headers: {
          Authorization: `Bearer ${token}`,
          'Cache-Control': 'no-cache',
          Pragma: 'no-cache',
        },
      },
    )
    return response.data
  }

  async createSubscription(paymentMethodId: string): Promise<{
    success: boolean
    subscription_id?: string
    client_secret?: string
    status?: string
    error?: string
  }> {
    try {
      const token = localStorage.getItem('authToken')
      const response = await axios.post(
        `${API_BASE_URL}/payment/create-subscription`,
        {
          paymentMethodId,
        },
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        },
      )
      return response.data
    } catch (error: any) {
      return {
        success: false,
        error: error.response?.data?.error || "Erreur lors de la création de l'abonnement",
      }
    }
  }

  async getSubscriptionStatus(): Promise<{
    hasActiveSubscription: boolean
    subscription: SubscriptionData | null
  }> {
    try {
      const token = localStorage.getItem('authToken')
      const response = await axios.get(`${API_BASE_URL}/payment/subscription-status`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      })
      return response.data
    } catch (error) {
      console.error("Erreur lors de la récupération du statut de l'abonnement:", error)
      return {
        hasActiveSubscription: false,
        subscription: null,
      }
    }
  }

  async cancelSubscription(): Promise<{
    success: boolean
    message?: string
    error?: string
  }> {
    try {
      const token = localStorage.getItem('authToken')
      const response = await axios.post(
        `${API_BASE_URL}/payment/cancel-subscription`,
        {},
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        },
      )
      return response.data
    } catch (error: any) {
      return {
        success: false,
        error: error.response?.data?.error || "Erreur lors de l'annulation de l'abonnement",
      }
    }
  }

  async getPremiumFeatures(): Promise<{
    success: boolean
    features?: PremiumFeatures
    error?: string
  }> {
    try {
      const token = localStorage.getItem('authToken')
      const response = await axios.get(`${API_BASE_URL}/payment/premium-features`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      })
      return response.data
    } catch (error: any) {
      return {
        success: false,
        error:
          error.response?.data?.error ||
          'Erreur lors de la récupération des fonctionnalités premium',
      }
    }
  }

  async processPayment(): Promise<{
    success: boolean
    subscription_id?: string
    client_secret?: string
    status?: string
    error?: string
  }> {
    if (!this.stripe) {
      await this.initializeStripe()
    }

    if (!this.stripe) {
      return {
        success: false,
        error: "Stripe n'est pas initialisé",
      }
    }

    try {
      // Create payment method
      const { error: paymentMethodError, paymentMethod } = await this.stripe.createPaymentMethod({
        type: 'card',
        card: {
          // Les détails de la carte seront fournis par les éléments Stripe
        },
      })

      if (paymentMethodError) {
        return {
          success: false,
          error:
            paymentMethodError.message || 'Erreur lors de la création de la méthode de paiement',
        }
      }

      if (!paymentMethod) {
        return {
          success: false,
          error: 'Impossible de créer la méthode de paiement',
        }
      }

      // Create subscription
      const result = await this.createSubscription(paymentMethod.id)

      if (result.success && result.client_secret) {
        // Confirm payment
        const { error: confirmError } = await this.stripe.confirmCardPayment(result.client_secret)

        if (confirmError) {
          return {
            success: false,
            error: confirmError.message || 'Erreur lors de la confirmation du paiement',
          }
        }
      }

      return result
    } catch (error: any) {
      return {
        success: false,
        error: error.message || 'Erreur lors du traitement du paiement',
      }
    }
  }
}

export const paymentService = new PaymentService()
