<template>
  <PremiumGuard>
    <div class="observer-mode-container">
    <!-- Header -->
    <div class="observer-header">
      <button @click="goBack" class="back-btn">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
          <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        Retour au dashboard
      </button>
      <div class="header-content">
        <div class="premium-badge">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
          </svg>
          <span>PREMIUM</span>
        </div>
        <h1>Mode Observateur</h1>
        <p>Limitez les actions de certains utilisateurs sur vos tableaux de projet</p>
      </div>
    </div>

    <!-- Contenu principal -->
    <div class="observer-content">
      <!-- Section de démonstration -->
      <div class="demo-section">
        <div class="demo-card">
          <div class="demo-icon">🎯</div>
          <div class="demo-content">
            <h3>Voir le Mode Observateur en Action</h3>
            <p>Découvrez comment le mode observateur limite les actions des utilisateurs observés dans une démonstration interactive.</p>
            <button @click="router.push('/observer-demo')" class="demo-btn">
              Voir la démonstration
            </button>
          </div>
        </div>
      </div>

      <!-- Contrôles d'observation -->
      <div class="observer-controls">
        <div class="control-groups">
          <!-- Gestion des permissions -->
          <div class="control-group">
            <h3>Gestion des permissions</h3>
            <div class="permission-list">
              <div v-for="permission in observerPermissions" :key="permission.id" class="permission-item">
                <div class="permission-info">
                  <h4>{{ permission.name }}</h4>
                  <p>{{ permission.description }}</p>
                </div>
                <div class="permission-controls">
                  <label class="toggle-switch">
                    <input 
                      type="checkbox" 
                      v-model="permission.enabled"
                      @change="updatePermission(permission)"
                    >
                    <span class="slider"></span>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Utilisateurs observés -->
          <div class="control-group">
            <h3>Utilisateurs observés</h3>
            <div class="user-list">
              <div v-for="user in observedUsers" :key="user.id" class="user-item">
                <div class="user-info">
                  <div class="user-avatar">
                    {{ getUserInitials(user.firstName, user.lastName) }}
                  </div>
                  <div class="user-details">
                    <h4>{{ user.firstName }} {{ user.lastName }}</h4>
                    <p>{{ user.email }}</p>
                  </div>
                </div>
                <div class="user-actions">
                  <button 
                    @click="toggleUserObservation(user)"
                    :class="['observe-btn', { active: user.isObserved }]"
                  >
                    {{ user.isObserved ? 'Observer' : 'Ne pas observer' }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Messages de succès/erreur -->
    <div v-if="successMessage" class="success-message">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
      </svg>
      <span>{{ successMessage }}</span>
    </div>

    <div v-if="errorMessage" class="error-message">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
      </svg>
      <span>{{ errorMessage }}</span>
    </div>
    </div>
  </PremiumGuard>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { observerService, type ObservedUser, type ObserverPermission } from '@/services/observerService'
import { paymentService } from '@/services/paymentService'
import PremiumGuard from '@/components/PremiumGuard.vue'

const router = useRouter()

// État du composant
const observedUsers = ref<ObservedUser[]>([])
const observerPermissions = ref<ObserverPermission[]>([])
const successMessage = ref('')
const errorMessage = ref('')

// Initialisation
onMounted(async () => {
  try {
    // Charger les paramètres d'observation (PremiumGuard s'occupe de vérifier le statut premium)
    await loadObserverSettings()
  } catch (error) {
    console.error('Erreur lors du chargement:', error)
    errorMessage.value = error instanceof Error ? error.message : 'Erreur lors du chargement du mode observateur'
  }
})

const loadObserverSettings = async () => {
  try {
    const settings = await observerService.getObserverSettings()
    observedUsers.value = settings.observedUsers
    observerPermissions.value = settings.permissions
  } catch (error) {
    console.error('Erreur lors du chargement des paramètres d\'observation:', error)
    errorMessage.value = error instanceof Error ? error.message : 'Erreur lors du chargement des paramètres d\'observation'
  }
}

const updatePermission = async (permission: ObserverPermission) => {
  try {
    await observerService.updatePermission(permission.id, permission.enabled)
    showSuccessMessage(`Permission "${permission.name}" ${permission.enabled ? 'activée' : 'désactivée'}`)
  } catch (error) {
    console.error('Erreur lors de la mise à jour de la permission:', error)
    errorMessage.value = error instanceof Error ? error.message : 'Erreur lors de la mise à jour de la permission'
  }
}

const toggleUserObservation = async (user: ObservedUser) => {
  try {
    const newState = !user.isObserved
    await observerService.toggleUserObservation(user.id, newState)
    user.isObserved = newState
    showSuccessMessage(`Utilisateur ${user.isObserved ? 'mis en observation' : 'retiré de l\'observation'}`)
  } catch (error) {
    console.error('Erreur lors de la mise à jour de l\'observation:', error)
    errorMessage.value = error instanceof Error ? error.message : 'Erreur lors de la mise à jour de l\'observation'
  }
}

const getUserInitials = (firstName?: string, lastName?: string): string => {
  if (!firstName || !lastName) return '?'
  return (firstName.charAt(0) + lastName.charAt(0)).toUpperCase()
}

const showSuccessMessage = (message: string) => {
  successMessage.value = message
  setTimeout(() => {
    successMessage.value = ''
  }, 3000)
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

.observer-mode-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  padding: 2rem;
}

.observer-header {
  max-width: 1200px;
  margin: 0 auto 3rem auto;
}

.back-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: var(--white);
  color: var(--text-primary);
  border: 2px solid var(--deep-light);
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  margin-bottom: 2rem;
}

.back-btn:hover {
  background: var(--deep-light);
  color: var(--white);
  transform: translateY(-1px);
}

.header-content {
  text-align: center;
}

.premium-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #ffd700, #ffed4e);
  color: #333;
  padding: 0.75rem 1.5rem;
  border-radius: 25px;
  font-weight: 800;
  font-size: 0.9rem;
  letter-spacing: 1px;
  margin-bottom: 1.5rem;
  box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
}

.premium-badge svg {
  color: #333;
}

.header-content h1 {
  color: var(--text-primary);
  font-size: 2.5rem;
  font-weight: 800;
  margin: 0 0 1rem 0;
}

.header-content p {
  color: var(--text-secondary);
  font-size: 1.2rem;
  margin: 0;
}

.observer-content {
  max-width: 1200px;
  margin: 0 auto;
}

.demo-section {
  margin-bottom: 3rem;
}

.demo-card {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  border-radius: 12px;
  padding: 2rem;
  display: flex;
  align-items: center;
  gap: 1.5rem;
  border: 2px solid var(--primary-color);
  box-shadow: 0 4px 20px rgba(65, 90, 119, 0.1);
}

.demo-icon {
  font-size: 3rem;
  flex-shrink: 0;
}

.demo-content {
  flex: 1;
}

.demo-content h3 {
  color: var(--text-primary);
  font-size: 1.3rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
}

.demo-content p {
  color: var(--text-secondary);
  margin: 0 0 1.5rem 0;
  line-height: 1.5;
}

.demo-btn {
  padding: 0.75rem 1.5rem;
  background: var(--primary-color);
  color: var(--white);
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.demo-btn:hover {
  background: var(--primary-hover);
  transform: translateY(-1px);
}

.observer-controls {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.control-groups {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}

.control-group h3 {
  color: var(--text-primary);
  font-size: 1.3rem;
  font-weight: 600;
  margin: 0 0 1.5rem 0;
}

.permission-list,
.user-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.permission-item,
.user-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  background: var(--white);
  border-radius: 8px;
  border: 2px solid var(--deep-pale);
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(27, 38, 59, 0.1);
}

.permission-item:hover,
.user-item:hover {
  border-color: var(--primary-color);
  transform: translateY(-1px);
  box-shadow: 0 4px 15px rgba(27, 38, 59, 0.15);
}

.permission-info h4,
.user-details h4 {
  color: var(--text-primary);
  font-size: 1rem;
  font-weight: 600;
  margin: 0 0 0.25rem 0;
}

.permission-info p,
.user-details p {
  color: var(--text-secondary);
  font-size: 0.9rem;
  margin: 0;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.user-avatar {
  width: 40px;
  height: 40px;
  background: var(--primary-color);
  color: var(--white);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.9rem;
}

.toggle-switch {
  position: relative;
  display: inline-block;
  width: 50px;
  height: 24px;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  transition: .4s;
  border-radius: 24px;
}

.slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: .4s;
  border-radius: 50%;
}

input:checked + .slider {
  background-color: var(--success-color);
}

input:checked + .slider:before {
  transform: translateX(26px);
}

.observe-btn {
  padding: 0.5rem 1rem;
  border: 2px solid var(--deep-light);
  background: var(--white);
  color: var(--text-primary);
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.observe-btn.active {
  background: var(--success-color);
  color: var(--white);
  border-color: var(--success-color);
}

.observe-btn:hover {
  transform: translateY(-1px);
}

.success-message,
.error-message {
  position: fixed;
  top: 2rem;
  right: 2rem;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
  z-index: 1000;
  animation: slideIn 0.3s ease;
}

.success-message {
  background: var(--success-color);
  color: var(--white);
}

.error-message {
  background: var(--error-color);
  color: var(--white);
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .observer-mode-container {
    padding: 1rem;
  }
  
  .observer-header {
    margin-bottom: 2rem;
  }
  
  .header-content h1 {
    font-size: 2rem;
  }
  
  .demo-card {
    flex-direction: column;
    text-align: center;
  }
  
  .control-groups {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .permission-item,
  .user-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .permission-controls,
  .user-actions {
    align-self: stretch;
  }
  
  .observe-btn {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .header-content h1 {
    font-size: 1.5rem;
  }
  
  .demo-card {
    padding: 1.5rem;
  }
}
</style>
