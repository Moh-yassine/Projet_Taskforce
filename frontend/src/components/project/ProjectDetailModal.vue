<template>
  <div class="modal-overlay" @click="closeModal">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h2>{{ project.name }}</h2>
        <button @click="closeModal" class="close-btn">&times;</button>
      </div>

      <div class="modal-body">
        <div class="project-info">
          <div class="info-section">
            <h3>Informations générales</h3>
            <div class="info-grid">
              <div class="info-item">
                <label>Statut:</label>
                <span :class="['status-badge', `status-${project.status}`]">
                  {{ getStatusLabel(project.status) }}
                </span>
              </div>
              <div class="info-item">
                <label>Date de début:</label>
                <span>{{ formatDate(project.startDate) }}</span>
              </div>
              <div class="info-item">
                <label>Date de fin:</label>
                <span>{{ formatDate(project.endDate) }}</span>
              </div>
              <div class="info-item">
                <label>Responsable:</label>
                <span>{{ project.projectManager?.name }}</span>
              </div>
            </div>
          </div>

          <div class="info-section">
            <h3>Description</h3>
            <p class="description">{{ project.description || 'Aucune description' }}</p>
          </div>

          <div class="info-section">
            <div class="section-header">
              <h3>Membres de l'équipe</h3>
              <button @click="showAddMemberModal = true" class="btn btn-small btn-primary">
                + Ajouter
              </button>
            </div>
            <div class="team-members">
              <div 
                v-for="member in project.teamMembers" 
                :key="member.id"
                class="member-card"
              >
                <div class="member-avatar">
                  {{ member.name.charAt(0) }}
                </div>
                <div class="member-info">
                  <span class="member-name">{{ member.name }}</span>
                  <span class="member-email">{{ member.email }}</span>
                </div>
                <button 
                  @click="removeTeamMember(member.id)"
                  class="btn btn-small btn-danger"
                >
                  Retirer
                </button>
              </div>
            </div>
          </div>

          <div class="info-section">
            <div class="section-header">
              <h3>Tâches du projet</h3>
              <button @click="showCreateTaskModal = true" class="btn btn-small btn-primary">
                + Nouvelle tâche
              </button>
            </div>
            <div class="tasks-overview">
              <div class="task-stats">
                <div class="stat-item">
                  <span class="stat-number">{{ getTasksByStatus('todo').length }}</span>
                  <span class="stat-label">À faire</span>
                </div>
                <div class="stat-item">
                  <span class="stat-number">{{ getTasksByStatus('in-progress').length }}</span>
                  <span class="stat-label">En cours</span>
                </div>
                <div class="stat-item">
                  <span class="stat-number">{{ getTasksByStatus('completed').length }}</span>
                  <span class="stat-label">Terminées</span>
                </div>
              </div>
              <div class="tasks-list">
                <div 
                  v-for="task in project.tasks" 
                  :key="task.id"
                  class="task-item"
                  @click="openTask(task)"
                >
                  <div class="task-header">
                    <h4>{{ task.title }}</h4>
                    <span :class="['priority-badge', `priority-${task.priority}`]">
                      {{ getPriorityLabel(task.priority) }}
                    </span>
                  </div>
                  <div class="task-meta">
                    <span class="due-date">{{ formatDate(task.dueDate) }}</span>
                    <span class="assigned-to" v-if="task.assignedTo">
                      Assignée à: {{ task.assignedTo.name }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button @click="closeModal" class="btn btn-secondary">
          Fermer
        </button>
        <button @click="showEditModal = true" class="btn btn-primary">
          Modifier le projet
        </button>
      </div>
    </div>

    <AddTeamMemberModal 
      v-if="showAddMemberModal"
      :project-id="project.id"
      @close="showAddMemberModal = false"
      @member-added="handleMemberAdded"
    />

    <CreateTaskModal 
      v-if="showCreateTaskModal"
      :projects="[project]"
      @close="showCreateTaskModal = false"
      @task-created="handleTaskCreated"
    />

    <EditProjectModal 
      v-if="showEditModal"
      :project="project"
      @close="showEditModal = false"
      @project-updated="handleProjectUpdated"
    />

    <TaskDetailModal 
      v-if="selectedTask"
      :task="selectedTask"
      @close="selectedTask = null"
      @task-updated="handleTaskUpdated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { authService } from '@/services/authService'
import AddTeamMemberModal from './AddTeamMemberModal.vue'
import CreateTaskModal from '../task/CreateTaskModal.vue'
import EditProjectModal from './EditProjectModal.vue'
import TaskDetailModal from '../task/TaskDetailModal.vue'

const props = defineProps<{
  project: any
}>()

const emit = defineEmits(['close', 'project-updated'])

const showAddMemberModal = ref(false)
const showCreateTaskModal = ref(false)
const showEditModal = ref(false)
const selectedTask = ref(null)

const getStatusLabel = (status: string) => {
  const labels = {
    'planning': 'Planification',
    'active': 'Actif',
    'on-hold': 'En pause',
    'completed': 'Terminé'
  }
  return labels[status] || status
}

const getPriorityLabel = (priority: string) => {
  const labels = {
    'low': 'Basse',
    'medium': 'Moyenne',
    'high': 'Haute',
    'urgent': 'Urgente'
  }
  return labels[priority] || priority
}

const getTasksByStatus = (status: string) => {
  return props.project.tasks?.filter((t: any) => t.status === status) || []
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('fr-FR')
}

const removeTeamMember = async (memberId: number) => {
  if (!confirm('Êtes-vous sûr de vouloir retirer ce membre du projet ?')) {
    return
  }

  try {
    const response = await fetch(`http://localhost:8000/api/projects/${props.project.id}/team-members/${memberId}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${authService.getAuthToken()}`
      }
    })

    if (response.ok) {
      emit('project-updated')
    } else {
      alert('Erreur lors du retrait du membre')
    }
  } catch (error) {
    console.error('Erreur:', error)
    alert('Erreur lors du retrait du membre')
  }
}

const openTask = (task: any) => {
  selectedTask.value = task
}

const handleMemberAdded = () => {
  showAddMemberModal.value = false
  emit('project-updated')
}

const handleTaskCreated = () => {
  showCreateTaskModal.value = false
  emit('project-updated')
}

const handleProjectUpdated = () => {
  showEditModal.value = false
  emit('project-updated')
}

const handleTaskUpdated = () => {
  selectedTask.value = null
  emit('project-updated')
}

const closeModal = () => {
  emit('close')
}
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
  padding: 1rem;
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 100%;
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e1e5e9;
}

.modal-header h2 {
  color: #172b4d;
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #6b778c;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 4px;
  transition: background-color 0.2s ease;
}

.close-btn:hover {
  background: #f4f5f7;
}

.modal-body {
  padding: 1.5rem;
}

.info-section {
  margin-bottom: 2rem;
}

.info-section h3 {
  color: #172b4d;
  font-size: 1.2rem;
  font-weight: 600;
  margin-bottom: 1rem;
  border-bottom: 2px solid #f4f5f7;
  padding-bottom: 0.5rem;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: #f8f9fa;
  border-radius: 6px;
}

.info-item label {
  font-weight: 600;
  color: #6b778c;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.status-planning {
  background: #e4f0f6;
  color: #0747a6;
}

.status-active {
  background: #e3fcef;
  color: #006644;
}

.status-on-hold {
  background: #fffae6;
  color: #974f0c;
}

.status-completed {
  background: #e3fcef;
  color: #006644;
}

.description {
  color: #6b778c;
  line-height: 1.6;
  margin: 0;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.btn-small {
  padding: 0.5rem 1rem;
  font-size: 0.8rem;
}

.team-members {
  display: grid;
  gap: 0.75rem;
}

.member-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 6px;
}

.member-avatar {
  width: 40px;
  height: 40px;
  background: #0079bf;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.9rem;
}

.member-info {
  flex: 1;
}

.member-name {
  display: block;
  font-weight: 600;
  color: #172b4d;
  font-size: 0.9rem;
}

.member-email {
  display: block;
  color: #6b778c;
  font-size: 0.8rem;
}

.task-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.stat-item {
  text-align: center;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 6px;
}

.stat-number {
  display: block;
  font-size: 2rem;
  font-weight: 700;
  color: #0079bf;
}

.stat-label {
  color: #6b778c;
  font-size: 0.9rem;
}

.tasks-list {
  max-height: 300px;
  overflow-y: auto;
}

.task-item {
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 6px;
  margin-bottom: 0.75rem;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.task-item:hover {
  background: #ebecf0;
}

.task-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
}

.task-header h4 {
  color: #172b4d;
  font-size: 1rem;
  font-weight: 600;
  margin: 0;
  flex: 1;
}

.priority-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
}

.priority-low {
  background: #e3fcef;
  color: #006644;
}

.priority-medium {
  background: #fffae6;
  color: #974f0c;
}

.priority-high {
  background: #ffe8e6;
  color: #c9372c;
}

.priority-urgent {
  background: #ffebe6;
  color: #de350b;
}

.task-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.8rem;
}

.due-date {
  color: #6b778c;
}

.assigned-to {
  color: #0079bf;
  font-weight: 500;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem;
  border-top: 1px solid #e1e5e9;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 4px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-primary {
  background: #0079bf;
  color: white;
}

.btn-primary:hover {
  background: #005a8b;
}

.btn-secondary {
  background: #f4f5f7;
  color: #172b4d;
  border: 1px solid #dfe1e6;
}

.btn-secondary:hover {
  background: #ebecf0;
}

.btn-danger {
  background: #de350b;
  color: white;
}

.btn-danger:hover {
  background: #bf2600;
}

@media (max-width: 768px) {
  .modal-content {
    margin: 1rem;
    max-height: calc(100vh - 2rem);
  }
  
  .info-grid {
    grid-template-columns: 1fr;
  }
  
  .task-stats {
    grid-template-columns: 1fr;
  }
  
  .modal-footer {
    flex-direction: column;
  }
  
  .btn {
    width: 100%;
  }
}
</style>
