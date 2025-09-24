<template>
  <div v-if="showContent" class="observer-guard">
    <slot />
  </div>
  <div v-else-if="showRestrictionMessage" class="observer-restriction">
    <div class="restriction-content">
      <div class="restriction-icon">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
        </svg>
      </div>
      <h3>Action restreinte</h3>
      <p>{{ restrictionMessage }}</p>
      <div v-if="showUpgradeButton" class="restriction-actions">
        <button @click="goToPremium" class="btn btn-premium">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
          </svg>
          Passer à Premium
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useObserver } from '@/composables/useObserver'

interface Props {
  action: string
  targetUserId?: number
  showUpgradeButton?: boolean
  customMessage?: string
}

const props = withDefaults(defineProps<Props>(), {
  showUpgradeButton: true,
  customMessage: ''
})

const router = useRouter()
const { isPremium, canPerformAction, loadObserverSettings } = useObserver()

// Computed pour déterminer si le contenu doit être affiché
const showContent = computed(() => {
  return canPerformAction.value(props.action, props.targetUserId)
})

// Computed pour déterminer si le message de restriction doit être affiché
const showRestrictionMessage = computed(() => {
  return !showContent.value
})

// Message de restriction personnalisé
const restrictionMessage = computed(() => {
  if (props.customMessage) {
    return props.customMessage
  }

  if (!isPremium.value) {
    return 'Cette fonctionnalité est disponible uniquement pour les utilisateurs Premium.'
  }

  const actionMessages: Record<string, string> = {
    'create': 'La création de tâches est restreinte pour cet utilisateur.',
    'edit': 'La modification de tâches est restreinte pour cet utilisateur.',
    'delete': 'La suppression de tâches est restreinte pour cet utilisateur.',
    'modify': 'La modification de projets est restreinte pour cet utilisateur.',
  }

  return actionMessages[props.action.toLowerCase()] || 'Cette action est restreinte pour cet utilisateur.'
})

// Navigation vers la page Premium
const goToPremium = () => {
  router.push('/premium')
}

// Charger les paramètres d'observation au montage
onMounted(() => {
  loadObserverSettings()
})
</script>

<style scoped>
.observer-guard {
  width: 100%;
}

.observer-restriction {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  border-radius: 12px;
  border: 2px dashed #cbd5e1;
  margin: 1rem 0;
}

.restriction-content {
  text-align: center;
  max-width: 400px;
}

.restriction-icon {
  width: 64px;
  height: 64px;
  background: linear-gradient(135deg, #f59e0b, #fbbf24);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem auto;
  color: white;
  box-shadow: 0 8px 25px rgba(245, 158, 11, 0.3);
}

.restriction-content h3 {
  color: #1f2937;
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 1rem 0;
}

.restriction-content p {
  color: #6b7280;
  font-size: 1rem;
  line-height: 1.6;
  margin: 0 0 2rem 0;
}

.restriction-actions {
  display: flex;
  justify-content: center;
}

.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
  border: none;
}

.btn-premium {
  background: linear-gradient(135deg, #ffd700, #ffed4e);
  color: #333;
  box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
}

.btn-premium:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
}

.btn-premium svg {
  color: #333;
}

/* Responsive */
@media (max-width: 768px) {
  .observer-restriction {
    padding: 1.5rem;
  }
  
  .restriction-icon {
    width: 48px;
    height: 48px;
  }
  
  .restriction-content h3 {
    font-size: 1.25rem;
  }
  
  .restriction-content p {
    font-size: 0.9rem;
  }
}
</style>
