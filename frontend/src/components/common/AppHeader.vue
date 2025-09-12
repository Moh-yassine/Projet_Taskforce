<template>
  <header class="app-header">
    <div class="header-content">
      <div class="header-left">
        <h1 class="header-title">{{ title }}</h1>
        <p v-if="subtitle" class="header-subtitle">{{ subtitle }}</p>
      </div>
      <div class="header-actions">
        <slot name="actions">
          <button v-if="showBackButton" @click="goBack" class="btn btn-secondary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            {{ backButtonText }}
          </button>
        </slot>
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'

interface Props {
  title: string
  subtitle?: string
  showBackButton?: boolean
  backButtonText?: string
  backRoute?: string
}

const props = withDefaults(defineProps<Props>(), {
  showBackButton: false,
  backButtonText: 'Retour',
  backRoute: '/dashboard'
})

const router = useRouter()

const goBack = () => {
  router.push(props.backRoute)
}
</script>

<style scoped>
.header-left {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.header-subtitle {
  font-size: 1.1rem;
  color: var(--text-secondary);
  margin: 0;
  font-weight: 400;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}
</style>
