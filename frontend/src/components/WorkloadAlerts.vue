<template>
  <div class="workload-alerts">
    <div class="alerts-header">
      <h3>Alertes de charge de travail</h3>
      <div class="alerts-count" :class="{ 'has-alerts': alerts.length > 0 }">
        {{ alerts.length }}
      </div>
    </div>

    <div v-if="alerts.length === 0" class="no-alerts">
      <div class="no-alerts-icon">✅</div>
      <p>Aucune alerte de charge de travail</p>
    </div>

    <div v-else class="alerts-list">
      <div
        v-for="alert in alerts"
        :key="alert.id"
        class="alert-item"
        :class="['alert-' + alert.severity, { 'alert-unread': !alert.isRead }]"
        @click="markAsRead(alert)"
      >
        <div class="alert-icon">
          <svg
            v-if="alert.type === 'overload'"
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="currentColor"
          >
            <path
              d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"
            />
          </svg>
          <svg
            v-else-if="alert.type === 'delay'"
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="currentColor"
          >
            <path
              d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"
            />
          </svg>
          <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
            <path
              d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"
            />
          </svg>
        </div>

        <div class="alert-content">
          <div class="alert-message">{{ alert.message }}</div>
          <div class="alert-meta">
            <span class="alert-time">{{ formatTime(alert.createdAt) }}</span>
            <span v-if="alert.taskId" class="alert-task">Tâche #{{ alert.taskId }}</span>
          </div>
        </div>

        <div class="alert-actions">
          <button
            v-if="!alert.isRead"
            @click.stop="markAsRead(alert)"
            class="mark-read-btn"
            title="Marquer comme lu"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <div v-if="alerts.length > 0" class="alerts-footer">
      <button @click="markAllAsRead" class="btn btn-secondary">Tout marquer comme lu</button>
      <button @click="refreshAlerts" class="btn btn-primary">Actualiser</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { workloadService, type WorkloadAlert } from '@/services/workloadService'

const alerts = ref<WorkloadAlert[]>([])
const isLoading = ref(false)

// Charger les alertes
const loadAlerts = async () => {
  try {
    isLoading.value = true
    alerts.value = await workloadService.getWorkloadAlerts()
  } catch (error) {
    console.error('Erreur lors du chargement des alertes:', error)
  } finally {
    isLoading.value = false
  }
}

// Marquer une alerte comme lue
const markAsRead = async (alert: WorkloadAlert) => {
  try {
    await workloadService.markAlertAsRead(alert.id)
    alert.isRead = true
  } catch (error) {
    console.error("Erreur lors de la mise à jour de l'alerte:", error)
  }
}

// Marquer toutes les alertes comme lues
const markAllAsRead = async () => {
  try {
    const unreadAlerts = alerts.value.filter((alert) => !alert.isRead)
    await Promise.all(unreadAlerts.map((alert) => workloadService.markAlertAsRead(alert.id)))

    alerts.value.forEach((alert) => {
      alert.isRead = true
    })
  } catch (error) {
    console.error('Erreur lors de la mise à jour des alertes:', error)
  }
}

// Actualiser les alertes
const refreshAlerts = async () => {
  await loadAlerts()
}

// Vérifier les retards de tâches
const checkTaskDelays = async () => {
  try {
    const newAlerts = await workloadService.checkTaskDelays()
    if (newAlerts.length > 0) {
      // Ajouter les nouvelles alertes à la liste
      alerts.value = [...newAlerts, ...alerts.value]
    }
  } catch (error) {
    console.error('Erreur lors de la vérification des retards:', error)
  }
}

// Formater l'heure
const formatTime = (dateString: string) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60))

  if (diffInMinutes < 1) return "À l'instant"
  if (diffInMinutes < 60) return `Il y a ${diffInMinutes}min`
  if (diffInMinutes < 1440) return `Il y a ${Math.floor(diffInMinutes / 60)}h`
  return `Il y a ${Math.floor(diffInMinutes / 1440)}j`
}

// Auto-refresh des alertes toutes les 5 minutes
let refreshInterval: NodeJS.Timeout | null = null

onMounted(() => {
  loadAlerts()
  checkTaskDelays()

  // Vérifier les retards toutes les 5 minutes
  refreshInterval = setInterval(checkTaskDelays, 5 * 60 * 1000)
})

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
})
</script>

<style scoped>
.workload-alerts {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.alerts-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.alerts-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
}

.alerts-count {
  background: rgba(255, 255, 255, 0.2);
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.875rem;
}

.alerts-count.has-alerts {
  background: #ef4444;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}

.no-alerts {
  padding: 3rem 1.5rem;
  text-align: center;
  color: #6b7280;
}

.no-alerts-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.no-alerts p {
  margin: 0;
  font-size: 1rem;
}

.alerts-list {
  max-height: 400px;
  overflow-y: auto;
}

.alert-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #f3f4f6;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}

.alert-item:hover {
  background: #f9fafb;
}

.alert-item.alert-unread {
  background: #fef3c7;
  border-left: 4px solid #f59e0b;
}

.alert-item.alert-unread::before {
  content: '';
  position: absolute;
  left: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  width: 8px;
  height: 8px;
  background: #ef4444;
  border-radius: 50%;
}

.alert-icon {
  flex-shrink: 0;
  margin-top: 0.125rem;
}

.alert-high .alert-icon {
  color: #dc2626;
}

.alert-medium .alert-icon {
  color: #d97706;
}

.alert-low .alert-icon {
  color: #059669;
}

.alert-content {
  flex: 1;
  min-width: 0;
}

.alert-message {
  font-weight: 500;
  color: #1f2937;
  margin-bottom: 0.25rem;
  line-height: 1.4;
}

.alert-meta {
  display: flex;
  gap: 1rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.alert-time {
  font-weight: 500;
}

.alert-task {
  background: #e5e7eb;
  padding: 0.125rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
}

.alert-actions {
  flex-shrink: 0;
  display: flex;
  align-items: center;
}

.mark-read-btn {
  background: none;
  border: none;
  color: #6b7280;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
  transition: all 0.2s;
}

.mark-read-btn:hover {
  background: #f3f4f6;
  color: #374151;
}

.alerts-footer {
  display: flex;
  justify-content: space-between;
  padding: 1rem 1.5rem;
  background: #f9fafb;
  border-top: 1px solid #e5e7eb;
}

.btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.875rem;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

/* Responsive */
@media (max-width: 768px) {
  .alerts-header {
    padding: 1rem;
  }

  .alert-item {
    padding: 1rem;
  }

  .alerts-footer {
    flex-direction: column;
    gap: 0.5rem;
  }

  .btn {
    width: 100%;
  }
}
</style>
