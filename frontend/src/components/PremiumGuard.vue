<template>
  <div v-if="isLoading" class="loading-container">
    <div class="loading-spinner"></div>
    <p>Vérification de votre abonnement Premium...</p>
  </div>
  
  <div v-else-if="!hasPremium" class="premium-required">
    <div class="premium-required-content">
      <div class="premium-icon">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
        </svg>
      </div>
      <h2>Abonnement Premium requis</h2>
      <p>Cette fonctionnalité nécessite un abonnement Premium actif.</p>
      <div class="premium-actions">
        <button @click="goToPremium" class="premium-btn">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
          </svg>
          Activer Premium
        </button>
        <button @click="goBack" class="back-btn">
          Retour au dashboard
        </button>
      </div>
    </div>
  </div>
  
  <slot v-else />
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { paymentService } from '@/services/paymentService'

const router = useRouter()
const isLoading = ref(true)
const hasPremium = ref(false)

onMounted(async () => {
  try {
    const config = await paymentService.getConfig()
    hasPremium.value = config.hasActiveSubscription
  } catch (error) {
    console.error('Erreur lors de la vérification du statut premium:', error)
    hasPremium.value = false
  } finally {
    isLoading.value = false
  }
})

const goToPremium = () => {
  router.push('/premium')
}

const goBack = () => {
  router.push('/dashboard')
}
</script>

<style scoped>
:root {
  --deep-dark: #0D1B2A;
  --deep-navy: #1B263B;
  --deep-blue: #415A77;
  --deep-light: #778DA9;
  --deep-pale: #E0E1DD;
  --primary-color: var(--deep-blue);
  --primary-hover: var(--deep-navy);
  --white: #ffffff;
  --text-primary: var(--deep-dark);
  --text-secondary: var(--deep-blue);
  --success-color: #10b981;
  --error-color: #ef4444;
  --warning-color: #f59e0b;
}

.loading-container {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  gap: 1rem;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--deep-pale);
  border-top: 4px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-container p {
  color: var(--text-secondary);
  font-size: 1.1rem;
  margin: 0;
}

.premium-required {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  padding: 2rem;
}

.premium-required-content {
  background: var(--white);
  border-radius: 16px;
  padding: 3rem;
  text-align: center;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
  max-width: 500px;
  width: 100%;
}

.premium-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #ffd700, #ffed4e);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 2rem auto;
  color: #333;
  box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
}

.premium-required-content h2 {
  color: var(--text-primary);
  font-size: 2rem;
  font-weight: 700;
  margin: 0 0 1rem 0;
}

.premium-required-content p {
  color: var(--text-secondary);
  font-size: 1.1rem;
  margin: 0 0 2rem 0;
  line-height: 1.5;
}

.premium-actions {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.premium-btn {
  background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
  color: var(--white);
  border: none;
  border-radius: 12px;
  padding: 1rem 2rem;
  font-size: 1.1rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 6px 20px rgba(65, 90, 119, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
}

.premium-btn:hover {
  background: linear-gradient(135deg, var(--primary-hover), var(--deep-navy));
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(65, 90, 119, 0.4);
}

.premium-btn svg {
  color: #ffd700;
}

.back-btn {
  background: var(--deep-light);
  color: var(--white);
  border: none;
  border-radius: 8px;
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.back-btn:hover {
  background: var(--deep-blue);
  transform: translateY(-1px);
}

/* Responsive */
@media (max-width: 768px) {
  .premium-required {
    padding: 1rem;
  }
  
  .premium-required-content {
    padding: 2rem;
  }
  
  .premium-required-content h2 {
    font-size: 1.5rem;
  }
  
  .premium-actions {
    gap: 0.75rem;
  }
  
  .premium-btn,
  .back-btn {
    width: 100%;
  }
}
</style>
