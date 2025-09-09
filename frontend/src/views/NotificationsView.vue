<template>
  <div class="notifications-view">
    <div class="page-header">
      <h1>Notifications</h1>
      <p>Gérez vos notifications et alertes</p>
    </div>

    <div class="notifications-content">
      <!-- Section pour les managers et responsables de projet -->
      <div v-if="canManageNotifications" class="admin-section">
        <h2>Gestion des Notifications</h2>
        <div class="notification-controls">
          <button @click="markAllAsRead" class="btn btn-secondary" :disabled="loading">
            <i class="icon">✓</i>
            Marquer tout comme lu
          </button>
          <button @click="clearAllNotifications" class="btn btn-danger" :disabled="loading">
            <i class="icon">🗑️</i>
            Supprimer tout
          </button>
        </div>
      </div>

      <!-- Liste des notifications -->
      <div class="notifications-list">
        <div v-if="loading" class="loading">
          <div class="spinner"></div>
          <p>Chargement des notifications...</p>
        </div>

        <div v-else-if="error" class="error">
          <p>{{ error }}</p>
          <button @click="loadNotifications" class="btn btn-primary">Réessayer</button>
        </div>

        <div v-else-if="notifications.length === 0" class="empty-state">
          <div class="empty-icon">🔔</div>
          <h3>Aucune notification</h3>
          <p>Vous n'avez actuellement aucune notification.</p>
        </div>

        <div v-else class="notifications-grid">
          <div v-for="notification in notifications" :key="notification.id" 
               class="notification-card" :class="{ 'unread': !notification.isRead }">
            <div class="notification-header">
              <div class="notification-type" :class="getTypeClass(notification.type)">
                <i class="icon">{{ getTypeIcon(notification.type) }}</i>
              </div>
              <div class="notification-meta">
                <span class="notification-title">{{ notification.title }}</span>
                <span class="notification-date">{{ formatDate(notification.createdAt) }}</span>
              </div>
              <div class="notification-actions">
                <button v-if="!notification.isRead" @click="markAsRead(notification.id)" 
                        class="btn btn-sm btn-primary">
                  <i class="icon">✓</i>
                </button>
                <button @click="deleteNotification(notification.id)" 
                        class="btn btn-sm btn-danger">
                  <i class="icon">🗑️</i>
                </button>
              </div>
            </div>
            <div class="notification-content">
              <p>{{ notification.message }}</p>
            </div>
            <div v-if="notification.relatedEntity" class="notification-entity">
              <span class="entity-label">Lié à :</span>
              <span class="entity-name">{{ notification.relatedEntity.name }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { authService, type User } from '@/services/authService'

interface Notification {
  id: number
  title: string
  message: string
  type: string
  isRead: boolean
  createdAt: string
  relatedEntity?: {
    name: string
    type: string
  }
}

const currentUser = ref<User | null>(null)
const notifications = ref<Notification[]>([])
const loading = ref(false)
const error = ref('')

const canManageNotifications = computed(() => {
  return currentUser.value?.permissions?.canManageNotifications || false
})

const loadNotifications = async () => {
  try {
    loading.value = true
    error.value = ''
    
    const notificationsData = await notificationService.getNotifications()
    notifications.value = notificationsData
    
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Erreur lors du chargement des notifications'
  } finally {
    loading.value = false
  }
}

const markAsRead = async (notificationId: number) => {
  try {
    await notificationService.markAsRead(notificationId)
    
    const notification = notifications.value.find(n => n.id === notificationId)
    if (notification) {
      notification.isRead = true
    }
  } catch (err) {
    console.error('Erreur lors du marquage comme lu:', err)
  }
}

const markAllAsRead = async () => {
  try {
    await notificationService.markAllAsRead()
    
    notifications.value.forEach(notification => {
      notification.isRead = true
    })
  } catch (err) {
    console.error('Erreur lors du marquage de toutes les notifications:', err)
  }
}

const deleteNotification = async (notificationId: number) => {
  try {
    await notificationService.deleteNotification(notificationId)
    
    notifications.value = notifications.value.filter(n => n.id !== notificationId)
  } catch (err) {
    console.error('Erreur lors de la suppression:', err)
  }
}

const clearAllNotifications = async () => {
  try {
    // Supprimer toutes les notifications une par une
    for (const notification of notifications.value) {
      await notificationService.deleteNotification(notification.id)
    }
    
    notifications.value = []
  } catch (err) {
    console.error('Erreur lors de la suppression de toutes les notifications:', err)
  }
}

const getTypeIcon = (type: string): string => {
  const typeIcons: Record<string, string> = {
    'task_assigned': '📋',
    'task_completed': '✅',
    'task_updated': '📝',
    'project_updated': '📁',
    'user_joined': '👤',
    'deadline_approaching': '⏰',
    'system': '⚙️',
    'workload_alert': '🚨',
    'delay_alert': '⏰',
    'alert': '🚨'
  }
  return typeIcons[type] || '🔔'
}

const getTypeClass = (type: string): string => {
  const typeClasses: Record<string, string> = {
    'task_assigned': 'type-task',
    'task_completed': 'type-success',
    'task_updated': 'type-info',
    'project_updated': 'type-project',
    'user_joined': 'type-user',
    'deadline_approaching': 'type-warning',
    'system': 'type-system',
    'workload_alert': 'type-danger',
    'delay_alert': 'type-warning',
    'alert': 'type-danger'
  }
  return typeClasses[type] || 'type-default'
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const loadUser = () => {
  currentUser.value = authService.getCurrentUser()
}

onMounted(() => {
  loadUser()
  loadNotifications()
})
</script>

<style scoped>
.notifications-view {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.page-header {
  margin-bottom: 30px;
  text-align: center;
}

.page-header h1 {
  color: #2c3e50;
  margin-bottom: 10px;
}

.page-header p {
  color: #7f8c8d;
  font-size: 16px;
}

.admin-section {
  background: white;
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 30px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.admin-section h2 {
  color: #2c3e50;
  margin: 0 0 20px 0;
  font-size: 20px;
}

.notification-controls {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.notifications-grid {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.notification-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  border: 1px solid #e1e8ed;
  transition: all 0.2s ease;
}

.notification-card.unread {
  border-left: 4px solid #3498db;
  background: #f8f9ff;
}

.notification-card:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.notification-header {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 12px;
}

.notification-type {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}

.type-task {
  background: #e3f2fd;
  color: #1976d2;
}

.type-success {
  background: #e8f5e8;
  color: #27ae60;
}

.type-info {
  background: #e1f5fe;
  color: #0288d1;
}

.type-project {
  background: #fff3e0;
  color: #f57c00;
}

.type-user {
  background: #f3e5f5;
  color: #7b1fa2;
}

.type-warning {
  background: #fff8e1;
  color: #f9a825;
}

.type-system {
  background: #f5f5f5;
  color: #616161;
}

.type-danger {
  background: #ffebee;
  color: #c62828;
}

.notification-meta {
  flex: 1;
}

.notification-title {
  display: block;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 4px;
}

.notification-date {
  font-size: 12px;
  color: #7f8c8d;
}

.notification-actions {
  display: flex;
  gap: 8px;
}

.notification-content {
  margin-bottom: 12px;
}

.notification-content p {
  color: #2c3e50;
  line-height: 1.5;
  margin: 0;
}

.notification-entity {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: #f8f9fa;
  border-radius: 6px;
  font-size: 12px;
}

.entity-label {
  color: #7f8c8d;
  font-weight: 500;
}

.entity-name {
  color: #2c3e50;
  font-weight: 600;
}

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  text-decoration: none;
}

.btn-primary {
  background: #3498db;
  color: white;
}

.btn-primary:hover {
  background: #2980b9;
}

.btn-secondary {
  background: #95a5a6;
  color: white;
}

.btn-secondary:hover {
  background: #7f8c8d;
}

.btn-danger {
  background: #e74c3c;
  color: white;
}

.btn-danger:hover {
  background: #c0392b;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 12px;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.loading {
  text-align: center;
  padding: 40px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3498db;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error {
  text-align: center;
  padding: 40px;
  color: #e74c3c;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #7f8c8d;
}

.empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
}

.empty-state h3 {
  margin: 0 0 8px 0;
  color: #2c3e50;
}

.empty-state p {
  margin: 0;
}

@media (max-width: 768px) {
  .notification-controls {
    flex-direction: column;
  }
  
  .notification-header {
    flex-direction: column;
    gap: 12px;
  }
  
  .notification-actions {
    align-self: flex-end;
  }
}
</style>
