<template>
  <div class="task-card" :class="{ 'task-completed': task.status === 'completed' }">
    <div class="task-header">
      <div class="task-title-section">
        <h3 class="task-title">{{ task.title }}</h3>
        <span class="task-status" :class="`status-${task.status}`">
          {{ getStatusLabel(task.status) }}
        </span>
      </div>
      <div class="task-actions">
        <!-- Bouton d'édition avec protection observateur -->
        <ObserverGuard 
          action="edit" 
          :target-user-id="task.assignedTo?.id"
          :custom-message="`La modification de cette tâche est restreinte pour ${task.assignedTo?.firstName} ${task.assignedTo?.lastName}.`"
        >
          <button @click="editTask" class="btn btn-edit" title="Modifier la tâche">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
          </button>
        </ObserverGuard>

        <!-- Bouton de suppression avec protection observateur -->
        <ObserverGuard 
          action="delete" 
          :target-user-id="task.assignedTo?.id"
          :custom-message="`La suppression de cette tâche est restreinte pour ${task.assignedTo?.firstName} ${task.assignedTo?.lastName}.`"
        >
          <button @click="deleteTask" class="btn btn-delete" title="Supprimer la tâche">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="3,6 5,6 21,6"/>
              <path d="M19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"/>
            </svg>
          </button>
        </ObserverGuard>
      </div>
    </div>

    <div class="task-content">
      <p v-if="task.description" class="task-description">{{ task.description }}</p>
      <p v-else class="task-description no-description">Aucune description</p>
    </div>

    <div class="task-meta">
      <div class="task-assignee" v-if="task.assignedTo">
        <div class="assignee-avatar">
          {{ getUserInitials(task.assignedTo.firstName, task.assignedTo.lastName) }}
        </div>
        <div class="assignee-info">
          <span class="assignee-name">{{ task.assignedTo.firstName }} {{ task.assignedTo.lastName }}</span>
          <span class="assignee-email">{{ task.assignedTo.email }}</span>
        </div>
        <!-- Indicateur d'observation -->
        <div v-if="isUserObserved(task.assignedTo.id)" class="observer-indicator" title="Utilisateur en observation">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
          </svg>
        </div>
      </div>

      <div class="task-dates">
        <div v-if="task.dueDate" class="task-due-date">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
            <line x1="16" y1="2" x2="16" y2="6"/>
            <line x1="8" y1="2" x2="8" y2="6"/>
            <line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
          <span>{{ formatDate(task.dueDate) }}</span>
        </div>
        <div class="task-created-date">
          Créée le {{ formatDate(task.createdAt) }}
        </div>
      </div>
    </div>

    <div class="task-priority" v-if="task.priority">
      <span class="priority-badge" :class="`priority-${task.priority}`">
        {{ getPriorityLabel(task.priority) }}
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import ObserverGuard from './ObserverGuard.vue'
import { useObserver } from '@/composables/useObserver'

interface Task {
  id: number
  title: string
  description?: string
  status: string
  priority?: string
  dueDate?: string
  createdAt: string
  assignedTo?: {
    id: number
    firstName: string
    lastName: string
    email: string
  }
}

interface Props {
  task: Task
}

const props = defineProps<Props>()

const emit = defineEmits<{
  edit: [task: Task]
  delete: [task: Task]
}>()

const { isUserObserved } = useObserver()

// Méthodes
const editTask = () => {
  emit('edit', props.task)
}

const deleteTask = () => {
  emit('delete', props.task)
}

const getUserInitials = (firstName?: string, lastName?: string): string => {
  if (!firstName || !lastName) return '?'
  return (firstName.charAt(0) + lastName.charAt(0)).toUpperCase()
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('fr-FR')
}

const getStatusLabel = (status: string): string => {
  const labels: Record<string, string> = {
    todo: 'À faire',
    in_progress: 'En cours',
    completed: 'Terminé',
    cancelled: 'Annulé'
  }
  return labels[status] || status
}

const getPriorityLabel = (priority: string): string => {
  const labels: Record<string, string> = {
    low: 'Faible',
    medium: 'Moyenne',
    high: 'Élevée',
    urgent: 'Urgente'
  }
  return labels[priority] || priority
}
</script>

<style scoped>
.task-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  border: 2px solid transparent;
  transition: all 0.2s ease;
  position: relative;
}

.task-card:hover {
  border-color: #415A77;
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.task-card.task-completed {
  opacity: 0.7;
  background: #f8f9fa;
}

.task-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.task-title-section {
  flex: 1;
  min-width: 0;
}

.task-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 0.5rem 0;
  line-height: 1.3;
}

.task-status {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-todo {
  background: #f3f4f6;
  color: #6b7280;
}

.status-in_progress {
  background: #dbeafe;
  color: #1d4ed8;
}

.status-completed {
  background: #d1fae5;
  color: #059669;
}

.status-cancelled {
  background: #fee2e2;
  color: #dc2626;
}

.task-actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

.btn {
  padding: 0.5rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-edit {
  background: #f3f4f6;
  color: #6b7280;
}

.btn-edit:hover {
  background: #e5e7eb;
  color: #374151;
}

.btn-delete {
  background: #fee2e2;
  color: #dc2626;
}

.btn-delete:hover {
  background: #fecaca;
  color: #b91c1c;
}

.task-content {
  margin-bottom: 1rem;
}

.task-description {
  color: #6b7280;
  line-height: 1.5;
  margin: 0;
}

.task-description.no-description {
  font-style: italic;
  color: #9ca3af;
}

.task-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.task-assignee {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  position: relative;
}

.assignee-avatar {
  width: 32px;
  height: 32px;
  background: #415A77;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.8rem;
}

.assignee-info {
  display: flex;
  flex-direction: column;
}

.assignee-name {
  font-weight: 600;
  color: #1f2937;
  font-size: 0.9rem;
}

.assignee-email {
  color: #6b7280;
  font-size: 0.8rem;
}

.observer-indicator {
  position: absolute;
  top: -4px;
  right: -4px;
  width: 20px;
  height: 20px;
  background: #f59e0b;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.task-dates {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  text-align: right;
}

.task-due-date {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  color: #6b7280;
  font-size: 0.8rem;
}

.task-created-date {
  color: #9ca3af;
  font-size: 0.75rem;
}

.task-priority {
  display: flex;
  justify-content: flex-end;
}

.priority-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.priority-low {
  background: #d1fae5;
  color: #059669;
}

.priority-medium {
  background: #fef3c7;
  color: #d97706;
}

.priority-high {
  background: #fed7d7;
  color: #e53e3e;
}

.priority-urgent {
  background: #fecaca;
  color: #dc2626;
  animation: urgentPulse 2s infinite;
}

@keyframes urgentPulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

/* Responsive */
@media (max-width: 768px) {
  .task-card {
    padding: 1rem;
  }
  
  .task-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .task-actions {
    align-self: flex-end;
  }
  
  .task-meta {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }
  
  .task-dates {
    text-align: left;
  }
}
</style>
