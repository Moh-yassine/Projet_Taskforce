<template>
  <div class="lazy-route-container">
    <Suspense>
      <template #default>
        <component :is="lazyComponent" v-bind="$attrs" />
      </template>
      <template #fallback>
        <div class="route-loading">
          <div class="loading-spinner">
            <div class="spinner"></div>
          </div>
          <p class="loading-text">Chargement...</p>
        </div>
      </template>
    </Suspense>
  </div>
</template>

<script setup lang="ts">
import { defineAsyncComponent, computed } from 'vue'

interface Props {
  component: () => Promise<{ default: unknown }>
  delay?: number
  timeout?: number
}

const props = withDefaults(defineProps<Props>(), {
  delay: 200,
  timeout: 10000,
})

const lazyComponent = computed(() => {
  return defineAsyncComponent({
    loader: props.component,
    delay: props.delay,
    timeout: props.timeout,
    errorComponent: {
      template: `
        <div class="route-error">
          <div class="error-icon">⚠️</div>
          <h3>Erreur de chargement</h3>
          <p>Impossible de charger cette page. Veuillez réessayer.</p>
          <button @click="$emit('retry')" class="retry-button">
            Réessayer
          </button>
        </div>
      `,
    },
    loadingComponent: {
      template: `
        <div class="route-loading">
          <div class="loading-spinner">
            <div class="spinner"></div>
          </div>
          <p class="loading-text">Chargement...</p>
        </div>
      `,
    },
  })
})

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const emit = defineEmits<{
  retry: []
}>()
</script>

<style scoped>
.lazy-route-container {
  min-height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.route-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  padding: 40px;
  color: #6b7280;
}

.loading-spinner {
  position: relative;
  width: 40px;
  height: 40px;
}

.spinner {
  width: 100%;
  height: 100%;
  border: 3px solid #e5e7eb;
  border-top: 3px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.loading-text {
  font-size: 14px;
  font-weight: 500;
  margin: 0;
}

.route-error {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  padding: 40px;
  text-align: center;
  color: #6b7280;
}

.error-icon {
  font-size: 48px;
  opacity: 0.7;
}

.route-error h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #374151;
}

.route-error p {
  margin: 0;
  font-size: 14px;
  max-width: 300px;
}

.retry-button {
  padding: 8px 16px;
  background-color: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.retry-button:hover {
  background-color: #2563eb;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .route-loading,
  .route-error {
    padding: 20px;
  }

  .loading-spinner {
    width: 32px;
    height: 32px;
  }

  .error-icon {
    font-size: 36px;
  }

  .route-error h3 {
    font-size: 16px;
  }
}
</style>
