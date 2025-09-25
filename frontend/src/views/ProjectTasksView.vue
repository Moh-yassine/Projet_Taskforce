<template>
  <div class="project-tasks-view">
    <!-- Header -->
    <header class="app-header">
      <div class="header-content">
        <div class="header-left">
          <h1 class="header-title" v-if="project">{{ project.name }}</h1>
        </div>
        <button @click="goBack" class="btn btn-primary">
          <svg
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <path d="M19 12H5M12 19l-7-7 7-7" />
          </svg>
          Retour
        </button>
      </div>
    </header>

    <!-- Description du projet -->
    <div class="project-description-section" v-if="project">
      <div class="description-header">
        <h3>Description du projet</h3>
        <button @click="toggleEditDescription" class="btn btn-edit">
          <svg
            width="16"
            height="16"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
          </svg>
          {{ isEditingDescription ? 'Annuler' : 'Modifier' }}
        </button>
      </div>

      <div v-if="!isEditingDescription" class="project-description">
        <p v-if="project.description">{{ project.description }}</p>
        <p v-else class="no-description">Aucune description disponible</p>
      </div>

      <div v-else class="description-edit-form">
        <textarea
          v-model="editedDescription"
          class="description-textarea"
          placeholder="Entrez la description du projet..."
          rows="4"
        ></textarea>
        <div class="edit-actions">
          <button @click="saveDescription" class="btn btn-primary" :disabled="isSaving">
            <span v-if="isSaving" class="loading-spinner"></span>
            {{ isSaving ? 'Sauvegarde...' : 'Enregistrer' }}
          </button>
          <button @click="cancelEditDescription" class="btn btn-secondary">Annuler</button>
        </div>
      </div>
    </div>

    <!-- Kanban Board avec Drag & Drop -->
    <div class="kanban-container" v-if="!isLoading && project">
        <KanbanBoard 
          ref="kanbanBoardRef"
          :project-id="project.id"
          :can-manage-project="canManageProject"
          @task-updated="handleTaskUpdated"
          @column-updated="handleColumnUpdated"
          @add-task="handleAddTask"
          @edit-task="handleEditTask"
        />

        <!-- Modal de création/édition de tâche -->
    <TaskModal
      v-if="showTaskModal"
          :task="editingTask"
          :project="project"
      @close="closeTaskModal"
          @save="handleTaskSaved"
        />
    </div>

    <!-- Loading state -->
    <div v-if="isLoading" class="loading-container">
      <div class="loading-spinner-large"></div>
      <p>Chargement du projet...</p>
    </div>

    <!-- Error state -->
    <div v-if="error" class="error-container">
      <div class="error-content">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
          <circle cx="12" cy="12" r="10"/>
          <line x1="15" y1="9" x2="9" y2="15"/>
          <line x1="9" y1="9" x2="15" y2="15"/>
        </svg>
        <h3>Erreur de chargement</h3>
        <p>{{ error }}</p>
        <button @click="loadProject" class="btn btn-primary">Réessayer</button>
        </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { projectService } from '@/services/projectService'
import { authService } from '@/services/authService'
import { taskService } from '@/services/taskService'
import KanbanBoard from '@/components/KanbanBoard.vue'
import TaskModal from '@/components/task/TaskModal.vue'

const route = useRoute()
const router = useRouter()

// État réactif
const project = ref<any>(null)
const isLoading = ref(false)
const error = ref<string | null>(null)
const isEditingDescription = ref(false)
const editedDescription = ref('')
const isSaving = ref(false)

// État pour le modal de tâche
const showTaskModal = ref(false)
const editingTask = ref<any>(null)
const selectedColumnId = ref<number | null>(null)

// Référence au composant KanbanBoard
const kanbanBoardRef = ref<any>(null)

// Computed properties
const canManageProject = computed(() => {
  const user = authService.getCurrentUser()
  return user?.roles?.includes('ROLE_PROJECT_MANAGER') || false
})

// Méthodes
const loadProject = async () => {
  try {
    isLoading.value = true
    error.value = null
    
    const projectId = parseInt(route.params.id as string)
    if (isNaN(projectId)) {
      throw new Error('ID de projet invalide')
    }
    
    project.value = await projectService.getProject(projectId)
    editedDescription.value = project.value.description || ''
  } catch (err: any) {
    console.error('Erreur lors du chargement du projet:', err)
    error.value = err.message || 'Erreur lors du chargement du projet'
  } finally {
    isLoading.value = false
  }
}

const goBack = () => {
  router.back()
}

const toggleEditDescription = () => {
  isEditingDescription.value = !isEditingDescription.value
  if (isEditingDescription.value) {
    editedDescription.value = project.value?.description || ''
  }
}

const saveDescription = async () => {
  if (!project.value) return

  try {
    isSaving.value = true
    await projectService.updateProject(project.value.id, {
      description: editedDescription.value
    })
    
    project.value.description = editedDescription.value
    isEditingDescription.value = false
  } catch (err: any) {
    console.error('Erreur lors de la sauvegarde:', err)
    alert('Erreur lors de la sauvegarde de la description')
  } finally {
    isSaving.value = false
  }
}

const cancelEditDescription = () => {
  isEditingDescription.value = false
  editedDescription.value = project.value?.description || ''
}

// Méthode pour recharger le tableau Kanban
const refreshKanbanBoard = async () => {
  if (kanbanBoardRef.value && typeof kanbanBoardRef.value.loadColumns === 'function') {
    await kanbanBoardRef.value.loadColumns()
  }
}

// Handlers pour les événements du KanbanBoard
const handleTaskUpdated = () => {
  console.log('Tâche mise à jour')
  // Optionnel: recharger les données si nécessaire
}

const handleColumnUpdated = () => {
  console.log('Colonne mise à jour')
  // Optionnel: recharger les données si nécessaire
}

const handleAddTask = (columnId: number) => {
  console.log('Ajouter une tâche à la colonne:', columnId)
  selectedColumnId.value = columnId
  editingTask.value = null
  showTaskModal.value = true
}

const handleEditTask = (task: any) => {
  console.log('Modifier la tâche:', task)
  editingTask.value = task
  selectedColumnId.value = task.column?.id || null
  showTaskModal.value = true
}

const closeTaskModal = () => {
  showTaskModal.value = false
  editingTask.value = null
  selectedColumnId.value = null
}

const handleTaskSaved = async (taskData: any) => {
  try {
    console.log('Sauvegarde de la tâche:', taskData)
    
    if (editingTask.value) {
      // Mise à jour d'une tâche existante
      await taskService.updateTask(editingTask.value.id, {
        ...taskData,
        columnId: selectedColumnId.value
      })
    } else {
      // Création d'une nouvelle tâche
      await taskService.createTask({
        ...taskData,
        projectId: project.value.id,
        columnId: selectedColumnId.value
      })
    }
    
    console.log('Tâche sauvegardée avec succès')
    closeTaskModal()
    
    // Recharger le tableau Kanban pour afficher la nouvelle tâche
    await refreshKanbanBoard()
    
    // Optionnel: recharger les données du Kanban
    // Le KanbanBoard se rechargera automatiquement via les événements
  } catch (error) {
    console.error('Erreur lors de la sauvegarde de la tâche:', error)
    alert('Erreur lors de la sauvegarde de la tâche')
  }
}

// Lifecycle
onMounted(() => {
  loadProject()
})
</script>

<style scoped>
.project-tasks-view {
  min-height: 100vh;
  background: #f8fafc;
}

/* Header */
.app-header {
  background: white;
  border-bottom: 1px solid #e2e8f0;
  padding: 0;
}

.header-content {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-left {
  flex: 1;
}

.header-title {
  margin: 0;
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
  transform: translateY(-1px);
}

.btn-secondary {
  background: #f1f5f9;
  color: #64748b;
}

.btn-secondary:hover {
  background: #e2e8f0;
}

.btn-edit {
  background: #f1f5f9;
  color: #64748b;
  padding: 8px 16px;
  font-size: 14px;
}

.btn-edit:hover {
  background: #e2e8f0;
  color: #475569;
}

/* Project Description */
.project-description-section {
  max-width: 1400px;
  margin: 0 auto;
  padding: 24px;
}

.description-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.description-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: #1e293b;
}

.project-description {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.project-description p {
  margin: 0;
  color: #64748b;
  line-height: 1.6;
}

.no-description {
  font-style: italic;
  color: #94a3b8;
}

.description-edit-form {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.description-textarea {
  width: 100%;
  min-height: 120px;
  padding: 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-family: inherit;
  font-size: 14px;
  resize: vertical;
  transition: border-color 0.2s;
}

.description-textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.edit-actions {
  display: flex;
  gap: 12px;
  margin-top: 16px;
}

/* Kanban Container */
.kanban-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 24px 24px;
}

/* Loading */
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 400px;
  color: #64748b;
}

.loading-spinner-large {
  width: 48px;
  height: 48px;
  border: 4px solid #e2e8f0;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Error */
.error-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  padding: 24px;
}

.error-content {
  text-align: center;
  max-width: 400px;
}

.error-content svg {
  color: #ef4444;
  margin-bottom: 16px;
}

.error-content h3 {
  margin: 0 0 8px 0;
  color: #1e293b;
  font-size: 20px;
  font-weight: 600;
}

.error-content p {
  margin: 0 0 24px 0;
  color: #64748b;
  line-height: 1.5;
}

/* Responsive */
@media (max-width: 768px) {
  .header-content {
    padding: 16px 20px;
  }
  
  .header-title {
    font-size: 24px;
  }
  
  .project-description-section {
    padding: 16px 20px;
  }
  
  .kanban-container {
    padding: 0 20px 20px;
  }
  
  .edit-actions {
    flex-direction: column;
  }
}
</style>