<template>
  <div class="workload-dashboard">
    <div class="dashboard-header">
      <h2>Charge de travail</h2>
      <div class="header-actions">
        <button @click="refreshData" class="btn btn-secondary" :disabled="isLoading">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
            <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
          </svg>
          Actualiser
        </button>
        <button @click="checkDelays" class="btn btn-primary">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
          </svg>
          Vérifier les retards
        </button>
      </div>
    </div>

    <div v-if="isLoading" class="loading-state">
      <div class="loading-spinner"></div>
      <p>Chargement des données de charge de travail...</p>
    </div>

    <div v-else class="dashboard-content">
      <!-- Vue d'ensemble -->
      <div class="overview-section">
        <div class="overview-cards">
          <div class="overview-card">
            <div class="card-icon">👥</div>
            <div class="card-content">
              <h3>{{ usersWorkload.length }}</h3>
              <p>Utilisateurs actifs</p>
            </div>
          </div>
          
          <div class="overview-card">
            <div class="card-icon">⚠️</div>
            <div class="card-content">
              <h3>{{ overloadedUsers.length }}</h3>
              <p>En surcharge</p>
            </div>
          </div>
          
          <div class="overview-card">
            <div class="card-icon">📊</div>
            <div class="card-content">
              <h3>{{ averageUtilization.toFixed(1) }}%</h3>
              <p>Utilisation moyenne</p>
            </div>
          </div>
          
          <div class="overview-card">
            <div class="card-icon">🔔</div>
            <div class="card-content">
              <h3>{{ totalAlerts }}</h3>
              <p>Alertes actives</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Liste des utilisateurs -->
      <div class="users-section">
        <h3>Charge de travail par utilisateur</h3>
        
        <div class="users-list">
          <div 
            v-for="userWorkload in usersWorkload" 
            :key="userWorkload.userId"
            class="user-card"
            :class="{ 'user-overloaded': userWorkload.utilizationPercentage >= 100 }"
          >
            <div class="user-header">
              <div class="user-info">
                <h4>{{ userWorkload.userName }}</h4>
                <div class="user-utilization">
                  <span class="utilization-percentage" :style="{ color: getUtilizationColor(userWorkload.utilizationPercentage) }">
                    {{ userWorkload.utilizationPercentage.toFixed(1) }}%
                  </span>
                  <span class="utilization-hours">
                    {{ userWorkload.currentWeekHours }}h / {{ userWorkload.maxWeekHours }}h
                  </span>
                </div>
              </div>
              
              <div class="user-actions">
                <button 
                  @click="viewUserDetails(userWorkload)"
                  class="btn btn-sm btn-secondary"
                >
                  Détails
                </button>
                <button 
                  @click="assignTaskToUser(userWorkload)"
                  class="btn btn-sm btn-primary"
                  :disabled="userWorkload.utilizationPercentage >= 100"
                >
                  Assigner
                </button>
              </div>
            </div>

            <!-- Barre de progression -->
            <div class="user-progress">
              <div class="progress-bar">
                <div 
                  class="progress-fill" 
                  :style="{ 
                    width: `${Math.min(userWorkload.utilizationPercentage, 100)}%`,
                    backgroundColor: getUtilizationColor(userWorkload.utilizationPercentage)
                  }"
                ></div>
              </div>
            </div>

            <!-- Tâches de l'utilisateur -->
            <div class="user-tasks">
              <div class="tasks-header">
                <span>Tâches ({{ userWorkload.tasks.length }})</span>
                <span class="tasks-hours">
                  {{ userWorkload.tasks.reduce((sum, task) => sum + task.estimatedHours, 0) }}h total
                </span>
              </div>
              
              <div class="tasks-list">
                <div 
                  v-for="task in userWorkload.tasks.slice(0, 3)" 
                  :key="task.id"
                  class="task-item"
                >
                  <span class="task-title">{{ task.title }}</span>
                  <span class="task-hours">{{ task.estimatedHours }}h</span>
                  <span class="task-status" :class="'status-' + task.status">
                    {{ getStatusLabel(task.status) }}
                  </span>
                </div>
                
                <div v-if="userWorkload.tasks.length > 3" class="more-tasks">
                  +{{ userWorkload.tasks.length - 3 }} autres tâches
                </div>
              </div>
            </div>

            <!-- Alertes de l'utilisateur -->
            <div v-if="userWorkload.alerts.length > 0" class="user-alerts">
              <div 
                v-for="alert in userWorkload.alerts" 
                :key="alert.id"
                class="user-alert"
                :class="'alert-' + alert.severity"
              >
                <div class="alert-icon">
                  <svg v-if="alert.type === 'overload'" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                  </svg>
                  <svg v-else-if="alert.type === 'delay'" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                  </svg>
                </div>
                <span class="alert-message">{{ alert.message }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { workloadService, type UserWorkload, type WorkloadAlert } from '@/services/workloadService'

const usersWorkload = ref<UserWorkload[]>([])
const isLoading = ref(false)

// Computed properties
const overloadedUsers = computed(() => {
  return usersWorkload.value.filter(user => user.utilizationPercentage >= 100)
})

const averageUtilization = computed(() => {
  if (usersWorkload.value.length === 0) return 0
  const total = usersWorkload.value.reduce((sum, user) => sum + user.utilizationPercentage, 0)
  return total / usersWorkload.value.length
})

const totalAlerts = computed(() => {
  return usersWorkload.value.reduce((sum, user) => sum + user.alerts.length, 0)
})

// Charger les données
const loadData = async () => {
  try {
    isLoading.value = true
    usersWorkload.value = await workloadService.getAllUsersWorkload()
  } catch (error) {
    console.error('Erreur lors du chargement des données:', error)
  } finally {
    isLoading.value = false
  }
}

// Actualiser les données
const refreshData = async () => {
  await loadData()
}

// Vérifier les retards
const checkDelays = async () => {
  try {
    const newAlerts = await workloadService.checkTaskDelays()
    if (newAlerts.length > 0) {
      // Recharger les données pour voir les nouvelles alertes
      await loadData()
    }
  } catch (error) {
    console.error('Erreur lors de la vérification des retards:', error)
  }
}

// Voir les détails d'un utilisateur
const viewUserDetails = (userWorkload: UserWorkload) => {
  // TODO: Ouvrir un modal avec les détails de l'utilisateur
  console.log('Détails utilisateur:', userWorkload)
}

// Assigner une tâche à un utilisateur
const assignTaskToUser = (userWorkload: UserWorkload) => {
  // TODO: Ouvrir le modal d'assignation de tâche
  console.log('Assigner tâche à:', userWorkload.userName)
}

// Utilitaires
const getUtilizationColor = (percentage: number) => {
  return workloadService.getUtilizationColor(percentage)
}

const getStatusLabel = (status: string) => {
  const labels: { [key: string]: string } = {
    todo: 'À faire',
    in_progress: 'En cours',
    completed: 'Terminé',
    on_hold: 'En attente'
  }
  return labels[status] || status
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.workload-dashboard {
  padding: 1.5rem;
  background: #f8fafc;
  min-height: 100vh;
}

.dashboard-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 2rem;
}

.dashboard-header h2 {
  margin: 0;
  color: #1f2937;
  font-size: 1.875rem;
  font-weight: 700;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem;
  color: #6b7280;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e5e7eb;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.overview-section {
  margin-bottom: 2rem;
}

.overview-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.overview-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.card-icon {
  font-size: 2rem;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f3f4f6;
  border-radius: 12px;
}

.card-content h3 {
  margin: 0 0 0.25rem 0;
  color: #1f2937;
  font-size: 2rem;
  font-weight: 700;
}

.card-content p {
  margin: 0;
  color: #6b7280;
  font-size: 0.875rem;
}

.users-section h3 {
  margin: 0 0 1.5rem 0;
  color: #1f2937;
  font-size: 1.5rem;
  font-weight: 600;
}

.users-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.user-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  padding: 1.5rem;
  transition: all 0.2s;
}

.user-card:hover {
  box-shadow: 0 8px 15px -3px rgba(0, 0, 0, 0.1);
}

.user-card.user-overloaded {
  border-left: 4px solid #ef4444;
  background: #fef2f2;
}

.user-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.user-info h4 {
  margin: 0 0 0.5rem 0;
  color: #1f2937;
  font-size: 1.25rem;
  font-weight: 600;
}

.user-utilization {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.utilization-percentage {
  font-size: 1.125rem;
  font-weight: 700;
}

.utilization-hours {
  color: #6b7280;
  font-size: 0.875rem;
}

.user-actions {
  display: flex;
  gap: 0.5rem;
}

.user-progress {
  margin-bottom: 1rem;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  transition: width 0.3s ease;
}

.user-tasks {
  margin-bottom: 1rem;
}

.tasks-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.tasks-hours {
  font-weight: 500;
}

.tasks-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.task-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem;
  background: #f9fafb;
  border-radius: 8px;
  font-size: 0.875rem;
}

.task-title {
  flex: 1;
  color: #1f2937;
  font-weight: 500;
}

.task-hours {
  color: #6b7280;
  font-weight: 500;
  min-width: 40px;
}

.task-status {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

.status-todo { background: #e0e7ff; color: #3730a3; }
.status-in_progress { background: #fef3c7; color: #92400e; }
.status-completed { background: #d1fae5; color: #065f46; }
.status-on_hold { background: #f3f4f6; color: #374151; }

.more-tasks {
  text-align: center;
  color: #6b7280;
  font-size: 0.875rem;
  font-style: italic;
  padding: 0.5rem;
}

.user-alerts {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.user-alert {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  border-radius: 8px;
  font-size: 0.875rem;
}

.alert-high {
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

.alert-medium {
  background: #fffbeb;
  color: #92400e;
  border: 1px solid #fed7aa;
}

.alert-low {
  background: #f0f9ff;
  color: #0c4a6e;
  border: 1px solid #bae6fd;
}

.alert-icon {
  flex-shrink: 0;
}

.alert-message {
  flex: 1;
}

.btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover:not(:disabled) {
  background: #e5e7eb;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn:disabled {
  background: #9ca3af;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

/* Responsive */
@media (max-width: 768px) {
  .workload-dashboard {
    padding: 1rem;
  }
  
  .dashboard-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .header-actions {
    justify-content: stretch;
  }
  
  .btn {
    flex: 1;
    justify-content: center;
  }
  
  .overview-cards {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .user-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .user-actions {
    justify-content: stretch;
  }
}
</style>
