<template>
  <div v-if="isOpen" class="modal-overlay" @click="closeModal">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h2>Assigner la tâche</h2>
        <button @click="closeModal" class="close-btn">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
          </svg>
        </button>
      </div>

      <div class="modal-body">
        <!-- Informations de la tâche -->
        <div class="task-info">
          <h3>{{ task?.title }}</h3>
          <p>{{ task?.description }}</p>
          <div class="task-meta">
            <span class="priority" :class="'priority-' + task?.priority">
              {{ getPriorityLabel(task?.priority) }}
            </span>
            <span class="estimated-hours">
              {{ task?.estimatedHours }}h estimées
            </span>
          </div>
        </div>

        <!-- Sélection de l'utilisateur -->
        <div class="assignment-section">
          <h4>Assigner à</h4>
          <div class="user-selection">
            <select v-model="selectedUserId" @change="onUserChange" class="user-select" :disabled="isLoading">
              <option value="">Sélectionner un utilisateur</option>
              <option 
                v-for="user in availableUsers" 
                :key="user.id" 
                :value="user.id"
                :disabled="!canAssignToUser(user)"
              >
                {{ user.firstName }} {{ user.lastName }} ({{ userService.getDisplayRole(user) }})
                <span v-if="!canAssignToUser(user)">- Surcharge</span>
                <span v-else>- {{ user.remainingCapacity }}h disponibles</span>
              </option>
            </select>
          </div>

          <!-- Informations de charge de travail de l'utilisateur sélectionné -->
          <div v-if="selectedUserWorkload" class="workload-info">
            <div class="workload-header">
              <h5>Charge de travail actuelle</h5>
              <div class="utilization-badge" :style="{ backgroundColor: getUtilizationColor(selectedUserWorkload.utilizationPercentage) }">
                {{ selectedUserWorkload.utilizationPercentage.toFixed(1) }}%
              </div>
            </div>
            
            <div class="workload-details">
              <div class="workload-bar">
                <div 
                  class="workload-fill" 
                  :style="{ 
                    width: `${Math.min(selectedUserWorkload.utilizationPercentage, 100)}%`,
                    backgroundColor: getUtilizationColor(selectedUserWorkload.utilizationPercentage)
                  }"
                ></div>
              </div>
              <div class="workload-stats">
                <span>{{ selectedUserWorkload.currentWeekHours }}h / {{ selectedUserWorkload.maxWeekHours }}h</span>
                <span v-if="newTotalHours > selectedUserWorkload.maxWeekHours" class="overload-warning">
                  ⚠️ Surcharge: {{ newTotalHours }}h
                </span>
              </div>
            </div>

            <!-- Tâches actuelles -->
            <div class="current-tasks">
              <h6>Tâches en cours ({{ selectedUserWorkload.tasks.length }})</h6>
              <div class="task-list">
                <div 
                  v-for="userTask in selectedUserWorkload.tasks" 
                  :key="userTask.id"
                  class="task-item"
                >
                  <span class="task-title">{{ userTask.title }}</span>
                  <span class="task-hours">{{ userTask.estimatedHours }}h</span>
                  <span class="task-status" :class="'status-' + userTask.status">
                    {{ getStatusLabel(userTask.status) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Configuration de l'assignation -->
        <div class="assignment-config">
          <div class="form-group">
            <label>Heures estimées</label>
            <input 
              v-model.number="estimatedHours" 
              type="number" 
              min="0.5" 
              max="40" 
              step="0.5"
              class="form-input"
            >
          </div>

          <div class="form-group">
            <label>Date d'échéance</label>
            <input 
              v-model="dueDate" 
              type="date" 
              :min="today"
              class="form-input"
            >
          </div>

          <div class="form-group">
            <label>Priorité</label>
            <select v-model="priority" class="form-input">
              <option value="low">Faible</option>
              <option value="medium">Moyenne</option>
              <option value="high">Élevée</option>
              <option value="urgent">Urgente</option>
            </select>
          </div>
        </div>

        <!-- Alertes et recommandations -->
        <div v-if="assignmentAlerts.length > 0" class="alerts-section">
          <h4>Alertes</h4>
          <div 
            v-for="alert in assignmentAlerts" 
            :key="alert.id"
            class="alert" 
            :class="'alert-' + alert.severity"
          >
            <div class="alert-icon">
              <svg v-if="alert.severity === 'high'" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
              </svg>
              <svg v-else-if="alert.severity === 'medium'" width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
              </svg>
              <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
              </svg>
            </div>
            <span>{{ alert.message }}</span>
          </div>
        </div>

        <!-- Recommandations d'assignation -->
        <div v-if="recommendations.length > 0" class="recommendations-section">
          <h4>Recommandations</h4>
          <div class="recommendations-list">
            <div 
              v-for="rec in recommendations" 
              :key="rec.id"
              class="recommendation-item"
              @click="selectRecommendedUser(rec.id)"
            >
              <div class="rec-user-info">
                <span class="rec-name">{{ rec.firstName }} {{ rec.lastName }}</span>
                <span class="rec-role">{{ userService.getDisplayRole(rec) }}</span>
              </div>
              <div class="rec-workload">
                {{ rec.currentWeekHours }}h / {{ rec.maxWeekHours }}h
                <span class="rec-percentage">({{ rec.utilizationPercentage.toFixed(1) }}%)</span>
              </div>
              <div class="rec-capacity">
                {{ rec.remainingCapacity }}h disponibles
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button @click="closeModal" class="btn btn-secondary">
          Annuler
        </button>
        <button 
          @click="assignTask" 
          :disabled="!canAssign"
          class="btn btn-primary"
        >
          Assigner la tâche
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { workloadService, type UserWorkload, type TaskAssignment, type WorkloadAlert } from '@/services/workloadService'
import { userService, type AssignableUser } from '@/services/userService'

interface Task {
  id: number
  title: string
  description: string
  priority: string
  estimatedHours: number
}

interface User {
  id: number
  firstName: string
  lastName: string
  roles: string[]
  skills: any[]
}

interface Props {
  isOpen: boolean
  task: Task | null
}

interface Emits {
  (e: 'close'): void
  (e: 'assigned', assignment: TaskAssignment): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const selectedUserId = ref<number | null>(null)
const estimatedHours = ref<number>(0)
const dueDate = ref<string>('')
const priority = ref<string>('medium')

const selectedUserWorkload = ref<UserWorkload | null>(null)
const assignmentAlerts = ref<WorkloadAlert[]>([])
const recommendations = ref<AssignableUser[]>([])
const availableUsers = ref<AssignableUser[]>([])
const isLoading = ref(false)

const today = computed(() => {
  return new Date().toISOString().split('T')[0]
})

const newTotalHours = computed(() => {
  if (!selectedUserWorkload.value) return 0
  return selectedUserWorkload.value.currentWeekHours + estimatedHours.value
})

const canAssign = computed(() => {
  return selectedUserId.value && 
         estimatedHours.value > 0 && 
         dueDate.value && 
         newTotalHours.value <= selectedUserWorkload.value?.maxWeekHours
})

// Initialiser les valeurs par défaut
watch(() => props.task, (newTask) => {
  if (newTask) {
    estimatedHours.value = newTask.estimatedHours || 0
    priority.value = newTask.priority || 'medium'
    
    // Date d'échéance par défaut : dans 1 semaine
    const nextWeek = new Date()
    nextWeek.setDate(nextWeek.getDate() + 7)
    dueDate.value = nextWeek.toISOString().split('T')[0]
  }
})

// Charger la charge de travail de l'utilisateur sélectionné
const onUserChange = async () => {
  if (selectedUserId.value) {
    try {
      selectedUserWorkload.value = await userService.getUserWorkload(selectedUserId.value)
      checkAssignmentAlerts()
    } catch (error) {
      console.error('Erreur lors du chargement de la charge de travail:', error)
    }
  } else {
    selectedUserWorkload.value = null
    assignmentAlerts.value = []
  }
}

// Vérifier les alertes d'assignation
const checkAssignmentAlerts = () => {
  assignmentAlerts.value = []
  
  if (!selectedUserWorkload.value) return

  const utilizationPercentage = workloadService.calculateUtilizationPercentage(newTotalHours.value)
  
  if (newTotalHours.value > selectedUserWorkload.value.maxWeekHours) {
    assignmentAlerts.value.push({
      id: Date.now(),
      userId: selectedUserId.value!,
      type: 'overload',
      message: `Surcharge de travail : ${newTotalHours.value}h > ${selectedUserWorkload.value.maxWeekHours}h`,
      severity: 'high',
      isRead: false,
      createdAt: new Date().toISOString()
    })
  } else if (utilizationPercentage >= 90) {
    assignmentAlerts.value.push({
      id: Date.now(),
      userId: selectedUserId.value!,
      type: 'overload',
      message: `Charge de travail élevée : ${utilizationPercentage.toFixed(1)}%`,
      severity: 'medium',
      isRead: false,
      createdAt: new Date().toISOString()
    })
  }
}

// Vérifier si on peut assigner à un utilisateur
const canAssignToUser = (user: AssignableUser) => {
  return userService.canAssignToUser(user, estimatedHours.value)
}

// Charger les utilisateurs disponibles
const loadAvailableUsers = async () => {
  try {
    isLoading.value = true
    availableUsers.value = await userService.getAssignableUsers()
    generateRecommendations()
  } catch (error) {
    console.error('Erreur lors du chargement des utilisateurs:', error)
  } finally {
    isLoading.value = false
  }
}

// Générer des recommandations d'assignation
const generateRecommendations = async () => {
  if (!props.task || availableUsers.value.length === 0) return
  
  try {
    recommendations.value = userService.getAssignmentRecommendations(
      availableUsers.value, 
      estimatedHours.value,
      3
    )
  } catch (error) {
    console.error('Erreur lors de la génération des recommandations:', error)
  }
}

// Sélectionner un utilisateur recommandé
const selectRecommendedUser = (userId: number) => {
  selectedUserId.value = userId
  onUserChange()
}

// Assigner la tâche
const assignTask = async () => {
  if (!props.task || !selectedUserId.value) return

  try {
    const assignment: TaskAssignment = {
      taskId: props.task.id,
      userId: selectedUserId.value,
      estimatedHours: estimatedHours.value,
      dueDate: dueDate.value,
      priority: priority.value as any
    }

    const result = await workloadService.assignTask(assignment)
    
    if (result.success) {
      emit('assigned', assignment)
      closeModal()
      
      // Afficher une notification si il y a une alerte
      if (result.alert) {
        // TODO: Afficher notification
        console.log('Alerte générée:', result.alert)
      }
    } else {
      // TODO: Afficher erreur
      console.error('Erreur d\'assignation:', result.message)
    }
  } catch (error) {
    console.error('Erreur lors de l\'assignation:', error)
  }
}

// Fermer le modal
const closeModal = () => {
  selectedUserId.value = null
  selectedUserWorkload.value = null
  assignmentAlerts.value = []
  recommendations.value = []
  emit('close')
}

// Utilitaires
const getPriorityLabel = (priority: string) => {
  const labels: { [key: string]: string } = {
    low: 'Faible',
    medium: 'Moyenne',
    high: 'Élevée',
    urgent: 'Urgente'
  }
  return labels[priority] || priority
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

const getUtilizationColor = (percentage: number) => {
  return workloadService.getUtilizationColor(percentage)
}

// Charger les données au montage
onMounted(() => {
  loadAvailableUsers()
})
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  margin: 0;
  color: #1f2937;
  font-size: 1.5rem;
  font-weight: 600;
}

.close-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: #6b7280;
  padding: 0.5rem;
  border-radius: 6px;
  transition: all 0.2s;
}

.close-btn:hover {
  background: #f3f4f6;
  color: #374151;
}

.modal-body {
  padding: 1.5rem;
}

.task-info {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

.task-info h3 {
  margin: 0 0 0.5rem 0;
  color: #1f2937;
  font-size: 1.25rem;
}

.task-info p {
  margin: 0 0 1rem 0;
  color: #6b7280;
}

.task-meta {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.priority {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 500;
}

.priority-low { background: #d1fae5; color: #065f46; }
.priority-medium { background: #fef3c7; color: #92400e; }
.priority-high { background: #fed7aa; color: #9a3412; }
.priority-urgent { background: #fecaca; color: #991b1b; }

.estimated-hours {
  color: #6b7280;
  font-size: 0.875rem;
}

.assignment-section {
  margin-bottom: 1.5rem;
}

.assignment-section h4 {
  margin: 0 0 1rem 0;
  color: #1f2937;
  font-size: 1.125rem;
}

.user-select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
  background: white;
}

.workload-info {
  background: #f8fafc;
  padding: 1rem;
  border-radius: 8px;
  margin-top: 1rem;
}

.workload-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.workload-header h5 {
  margin: 0;
  color: #1f2937;
  font-size: 1rem;
}

.utilization-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  color: white;
  font-size: 0.875rem;
  font-weight: 600;
}

.workload-bar {
  width: 100%;
  height: 8px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.workload-fill {
  height: 100%;
  transition: width 0.3s ease;
}

.workload-stats {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
  color: #6b7280;
}

.overload-warning {
  color: #dc2626;
  font-weight: 500;
}

.current-tasks {
  margin-top: 1rem;
}

.current-tasks h6 {
  margin: 0 0 0.5rem 0;
  color: #374151;
  font-size: 0.875rem;
}

.task-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.task-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem;
  background: white;
  border-radius: 6px;
  font-size: 0.875rem;
}

.task-title {
  flex: 1;
  color: #1f2937;
}

.task-hours {
  color: #6b7280;
  font-weight: 500;
}

.task-status {
  padding: 0.125rem 0.5rem;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 500;
}

.status-todo { background: #e0e7ff; color: #3730a3; }
.status-in_progress { background: #fef3c7; color: #92400e; }
.status-completed { background: #d1fae5; color: #065f46; }
.status-on_hold { background: #f3f4f6; color: #374151; }

.assignment-config {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  margin-bottom: 0.5rem;
  color: #374151;
  font-weight: 500;
  font-size: 0.875rem;
}

.form-input {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.alerts-section {
  margin-bottom: 1.5rem;
}

.alerts-section h4 {
  margin: 0 0 1rem 0;
  color: #1f2937;
  font-size: 1.125rem;
}

.alert {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 0.5rem;
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

.recommendations-section {
  margin-bottom: 1.5rem;
}

.recommendations-section h4 {
  margin: 0 0 1rem 0;
  color: #1f2937;
  font-size: 1.125rem;
}

.recommendations-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.recommendation-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.recommendation-item:hover {
  background: #e2e8f0;
  border-color: #cbd5e0;
}

.rec-user-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.rec-name {
  font-weight: 500;
  color: #1f2937;
}

.rec-score {
  font-size: 0.875rem;
  color: #6b7280;
}

.rec-workload {
  text-align: right;
  font-size: 0.875rem;
  color: #6b7280;
}

.rec-percentage {
  display: block;
  font-weight: 500;
  color: #374151;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
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

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
  background: #9ca3af;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}
</style>
