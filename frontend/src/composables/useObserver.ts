import { ref, computed } from 'vue'
import { observerService } from '@/services/observerService'
import { authService } from '@/services/authService'

export interface ObserverPermission {
  id: number
  name: string
  description: string
  enabled: boolean
}

export interface ObservedUser {
  id: number
  firstName: string
  lastName: string
  email: string
  isObserved: boolean
}

export function useObserver() {
  const isPremium = ref(false)
  const observerPermissions = ref<ObserverPermission[]>([])
  const observedUsers = ref<ObservedUser[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Computed pour vérifier si l'utilisateur actuel peut effectuer une action
  const canPerformAction = computed(() => {
    return (action: string, targetUserId?: number) => {
      // Si l'utilisateur n'est pas Premium, toutes les actions sont autorisées
      if (!isPremium.value) {
        return true
      }

      // Si pas d'utilisateur cible spécifié, vérifier les permissions globales
      if (!targetUserId) {
        const permission = observerPermissions.value.find(p => 
          p.name.toLowerCase().includes(action.toLowerCase())
        )
        return !permission?.enabled
      }

      // Vérifier si l'utilisateur cible est observé
      const observedUser = observedUsers.value.find(u => u.id === targetUserId)
      if (!observedUser?.isObserved) {
        return true
      }

      // Vérifier les permissions spécifiques pour l'action
      const permission = observerPermissions.value.find(p => 
        p.name.toLowerCase().includes(action.toLowerCase())
      )
      
      return !permission?.enabled
    }
  })

  // Computed pour vérifier si un utilisateur est observé
  const isUserObserved = computed(() => {
    return (userId: number) => {
      const user = observedUsers.value.find(u => u.id === userId)
      return user?.isObserved || false
    }
  })

  // Charger les paramètres d'observation
  const loadObserverSettings = async () => {
    if (!authService.isAuthenticated()) {
      return
    }

    isLoading.value = true
    error.value = null

    try {
      // Vérifier d'abord si l'utilisateur est Premium
      const user = authService.getCurrentUser()
      if (user?.hasActivePremiumSubscription) {
        isPremium.value = true
        
        const settings = await observerService.getObserverSettings()
        observerPermissions.value = settings.permissions
        observedUsers.value = settings.observedUsers
      } else {
        isPremium.value = false
        observerPermissions.value = []
        observedUsers.value = []
      }
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Erreur lors du chargement des paramètres d\'observation'
      console.error('Erreur lors du chargement des paramètres d\'observation:', err)
    } finally {
      isLoading.value = false
    }
  }

  // Vérifier une permission spécifique
  const checkPermission = async (userId: number, action: string): Promise<boolean> => {
    if (!isPremium.value) {
      return true
    }

    try {
      const result = await observerService.checkUserPermission(userId, action)
      return result.allowed
    } catch (err) {
      console.error('Erreur lors de la vérification de la permission:', err)
      return true // En cas d'erreur, autoriser l'action par défaut
    }
  }

  // Mettre à jour une permission
  const updatePermission = async (permissionId: number, enabled: boolean) => {
    try {
      await observerService.updatePermission(permissionId, enabled)
      
      // Mettre à jour l'état local
      const permission = observerPermissions.value.find(p => p.id === permissionId)
      if (permission) {
        permission.enabled = enabled
      }
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Erreur lors de la mise à jour de la permission'
      throw err
    }
  }

  // Basculer l'observation d'un utilisateur
  const toggleUserObservation = async (userId: number, isObserved: boolean) => {
    try {
      await observerService.toggleUserObservation(userId, isObserved)
      
      // Mettre à jour l'état local
      const user = observedUsers.value.find(u => u.id === userId)
      if (user) {
        user.isObserved = isObserved
      }
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Erreur lors de la mise à jour de l\'observation'
      throw err
    }
  }

  return {
    // État
    isPremium,
    observerPermissions,
    observedUsers,
    isLoading,
    error,
    
    // Computed
    canPerformAction,
    isUserObserved,
    
    // Méthodes
    loadObserverSettings,
    checkPermission,
    updatePermission,
    toggleUserObservation,
  }
}
