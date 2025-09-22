import { ref, computed } from 'vue'
import { paymentService, type SubscriptionData } from '@/services/paymentService'

const hasActiveSubscription = ref(false)
const subscription = ref<SubscriptionData | null>(null)
const loading = ref(false)
const error = ref('')

export function usePremium() {
  const isPremium = computed(() => hasActiveSubscription.value)

  const loadSubscriptionStatus = async () => {
    try {
      loading.value = true
      error.value = ''

      const result = await paymentService.getSubscriptionStatus()
      hasActiveSubscription.value = result.hasActiveSubscription
      subscription.value = result.subscription
    } catch (err: unknown) {
      error.value = (err as Error).message || "Erreur lors du chargement du statut de l'abonnement"
      hasActiveSubscription.value = false
      subscription.value = null
    } finally {
      loading.value = false
    }
  }

  const refreshSubscriptionStatus = async () => {
    await loadSubscriptionStatus()
  }

  const checkPremiumAccess = (_feature: string): boolean => {
    if (!hasActiveSubscription.value) {
      return false
    }

    // Ici vous pouvez ajouter une logique spécifique pour chaque fonctionnalité
    // Par exemple, certaines fonctionnalités premium peuvent avoir des restrictions supplémentaires
    return true
  }

  const requirePremium = (callback: () => void) => {
    if (hasActiveSubscription.value) {
      callback()
    } else {
      // Rediriger vers la page de paiement
      window.location.href = '/payment'
    }
  }

  return {
    // State
    hasActiveSubscription: computed(() => hasActiveSubscription.value),
    subscription: computed(() => subscription.value),
    isPremium,
    loading: computed(() => loading.value),
    error: computed(() => error.value),

    // Methods
    loadSubscriptionStatus,
    refreshSubscriptionStatus,
    checkPremiumAccess,
    requirePremium,
  }
}
