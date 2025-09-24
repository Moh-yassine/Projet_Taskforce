<template>
  <div v-if="isLoading" class="loading-container">
    <div class="loading-spinner"></div>
    <p>Vérification de l'authentification...</p>
  </div>
  
  <div v-else-if="!isAuthenticated" class="auth-required">
    <div class="auth-required-content">
      <div class="auth-icon">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
        </svg>
      </div>
      <h2>Authentification requise</h2>
      <p>Vous devez être connecté pour accéder à cette page.</p>
      <div class="auth-actions">
        <button @click="goToLogin" class="login-btn">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
            <path d="M11 7L9.6 8.4l2.6 2.6H2v2h10.2l-2.6 2.6L11 17l5-5-5-5zm9 12h-8v2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-8v2h8v14z"/>
          </svg>
          Se connecter
        </button>
      </div>
    </div>
  </div>
  
  <slot v-else />
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { authService } from '@/services/authService'

const router = useRouter()
const isLoading = ref(true)
const isAuthenticated = ref(false)

onMounted(async () => {
  try {
    // Vérifier l'authentification et le token JWT
    const hasAuth = authService.isAuthenticated()
    const hasToken = authService.getAuthToken()
    
    isAuthenticated.value = hasAuth && hasToken
    
    if (!isAuthenticated.value) {
      console.log('Utilisateur non authentifié ou token manquant')
      // Ne pas rediriger automatiquement, laisser l'utilisateur choisir
    }
  } catch (error) {
    console.error('Erreur lors de la vérification de l\'authentification:', error)
    isAuthenticated.value = false
  } finally {
    isLoading.value = false
  }
})

const goToLogin = () => {
  router.push('/login')
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

.auth-required {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  padding: 2rem;
}

.auth-required-content {
  background: var(--white);
  border-radius: 16px;
  padding: 3rem;
  text-align: center;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
  max-width: 500px;
  width: 100%;
}

.auth-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 2rem auto;
  color: var(--white);
  box-shadow: 0 8px 25px rgba(65, 90, 119, 0.3);
}

.auth-required-content h2 {
  color: var(--text-primary);
  font-size: 2rem;
  font-weight: 700;
  margin: 0 0 1rem 0;
}

.auth-required-content p {
  color: var(--text-secondary);
  font-size: 1.1rem;
  margin: 0 0 2rem 0;
  line-height: 1.5;
}

.auth-actions {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.login-btn {
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

.login-btn:hover {
  background: linear-gradient(135deg, var(--primary-hover), var(--deep-navy));
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(65, 90, 119, 0.4);
}

/* Responsive */
@media (max-width: 768px) {
  .auth-required {
    padding: 1rem;
  }
  
  .auth-required-content {
    padding: 2rem;
  }
  
  .auth-required-content h2 {
    font-size: 1.5rem;
  }
  
  .auth-actions {
    gap: 0.75rem;
  }
  
  .login-btn {
    width: 100%;
  }
}
</style>
