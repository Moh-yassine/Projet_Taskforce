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

    <div class="content-spacing"></div>

    <!-- Kanban Board -->
    <div class="kanban-board" v-if="!isLoading">
      <div class="kanban-columns">
        <!-- À faire -->
        <div class="kanban-column" :style="{ backgroundColor: columnColors.todo }">
          <div class="column-header">
            <h3>À faire</h3>
            <div class="column-actions">
              <button @click="openCreateTaskModal('todo')" class="add-card-btn">
                + Ajouter une carte
              </button>
              <div class="column-menu">
                <button @click="toggleColorMenu('todo')" class="column-menu-btn">⋯</button>
                <div v-if="showColorMenu === 'todo'" class="color-picker">
                  <div class="color-options">
                    <button
                      v-for="color in availableColors"
                      :key="color.name"
                      :style="{ backgroundColor: color.value }"
                      @click="changeColumnColor('todo', color.value)"
                      class="color-option"
                      :title="color.name"
                    ></button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="tasks-list">
            <div
              v-for="task in tasksByStatus.todo"
              :key="task.id"
              class="task-card"
              @click="openTaskModal(task)"
            >
              <div class="task-header">
                <h4>{{ task.title }}</h4>
                <div class="task-actions">
                  <button @click.stop="editTask(task)" class="action-btn">
                    <svg
                      width="16"
                      height="16"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    >
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                  </button>
                  <button @click.stop="deleteTask(task)" class="action-btn">
                    <svg
                      width="16"
                      height="16"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    >
                      <polyline points="3,6 5,6 21,6"></polyline>
                      <path
                        d="M19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"
                      ></path>
                      <line x1="10" y1="11" x2="10" y2="17"></line>
                      <line x1="14" y1="11" x2="14" y2="17"></line>
                    </svg>
                  </button>
                </div>
              </div>
              <p class="task-description">{{ task.description }}</p>

              <!-- Compétences -->
              <div v-if="task.skills && task.skills.length > 0" class="task-skills">
                <span v-for="skill in task.skills" :key="skill.id" class="skill-tag">
                  {{ skill.name }}
                </span>
              </div>

              <div class="task-footer">
                <span class="priority" :class="task.priority">{{ task.priority }}</span>
                <span v-if="task.assignee" class="assignee">
                  {{ task.assignee.firstName }} {{ task.assignee.lastName }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- En cours -->
        <div class="kanban-column" :style="{ backgroundColor: columnColors.in_progress }">
          <div class="column-header">
            <h3>En cours</h3>
            <div class="column-actions">
              <button @click="openCreateTaskModal('in_progress')" class="add-card-btn">
                + Ajouter une carte
              </button>
              <div class="column-menu">
                <button @click="toggleColorMenu('in_progress')" class="column-menu-btn">⋯</button>
                <div v-if="showColorMenu === 'in_progress'" class="color-picker">
                  <div class="color-options">
                    <button
                      v-for="color in availableColors"
                      :key="color.name"
                      :style="{ backgroundColor: color.value }"
                      @click="changeColumnColor('in_progress', color.value)"
                      class="color-option"
                      :title="color.name"
                    ></button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="tasks-list">
            <div
              v-for="task in tasksByStatus.in_progress"
              :key="task.id"
              class="task-card"
              @click="openTaskModal(task)"
            >
              <div class="task-header">
                <h4>{{ task.title }}</h4>
                <div class="task-actions">
                  <button @click.stop="editTask(task)" class="action-btn">
                    <svg
                      width="16"
                      height="16"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    >
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                  </button>
                  <button @click.stop="deleteTask(task)" class="action-btn">
                    <svg
                      width="16"
                      height="16"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    >
                      <polyline points="3,6 5,6 21,6"></polyline>
                      <path
                        d="M19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"
                      ></path>
                      <line x1="10" y1="11" x2="10" y2="17"></line>
                      <line x1="14" y1="11" x2="14" y2="17"></line>
                    </svg>
                  </button>
                </div>
              </div>
              <p class="task-description">{{ task.description }}</p>

              <!-- Compétences -->
              <div v-if="task.skills && task.skills.length > 0" class="task-skills">
                <span v-for="skill in task.skills" :key="skill.id" class="skill-tag">
                  {{ skill.name }}
                </span>
              </div>

              <div class="task-footer">
                <span class="priority" :class="task.priority">{{ task.priority }}</span>
                <span v-if="task.assignee" class="assignee">
                  {{ task.assignee.firstName }} {{ task.assignee.lastName }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Terminé -->
        <div class="kanban-column" :style="{ backgroundColor: columnColors.completed }">
          <div class="column-header">
            <h3>Terminé</h3>
            <div class="column-actions">
              <button @click="openCreateTaskModal('completed')" class="add-card-btn">
                + Ajouter une carte
              </button>
              <div class="column-menu">
                <button @click="toggleColorMenu('completed')" class="column-menu-btn">⋯</button>
                <div v-if="showColorMenu === 'completed'" class="color-picker">
                  <div class="color-options">
                    <button
                      v-for="color in availableColors"
                      :key="color.name"
                      :style="{ backgroundColor: color.value }"
                      @click="changeColumnColor('completed', color.value)"
                      class="color-option"
                      :title="color.name"
                    ></button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="tasks-list">
            <div
              v-for="task in tasksByStatus.completed"
              :key="task.id"
              class="task-card"
              @click="openTaskModal(task)"
            >
              <div class="task-header">
                <h4>{{ task.title }}</h4>
                <div class="task-actions">
                  <button @click.stop="editTask(task)" class="action-btn">
                    <svg
                      width="16"
                      height="16"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    >
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                  </button>
                  <button @click.stop="deleteTask(task)" class="action-btn">
                    <svg
                      width="16"
                      height="16"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    >
                      <polyline points="3,6 5,6 21,6"></polyline>
                      <path
                        d="M19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"
                      ></path>
                      <line x1="10" y1="11" x2="10" y2="17"></line>
                      <line x1="14" y1="11" x2="14" y2="17"></line>
                    </svg>
                  </button>
                </div>
              </div>
              <p class="task-description">{{ task.description }}</p>

              <!-- Compétences -->
              <div v-if="task.skills && task.skills.length > 0" class="task-skills">
                <span v-for="skill in task.skills" :key="skill.id" class="skill-tag">
                  {{ skill.name }}
                </span>
              </div>

              <div class="task-footer">
                <span class="priority" :class="task.priority">{{ task.priority }}</span>
                <span v-if="task.assignee" class="assignee">
                  {{ task.assignee.firstName }} {{ task.assignee.lastName }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="loading">
      <p>Chargement des tâches...</p>
    </div>

    <!-- Task Modal -->
    <TaskModal
      v-if="showTaskModal"
      :task="selectedTask"
      @close="closeTaskModal"
      @save="handleTaskSave"
    />

    <!-- Assignment Modal -->
    <TaskAssignmentModal
      v-if="showAssignmentModal"
      :task="selectedTask"
      @close="closeAssignmentModal"
      @assign="handleTaskAssignment"
    />

    <!-- Modal de confirmation de suppression de tâche -->
    <div v-if="showDeleteTaskModal" class="delete-modal-overlay" @click="closeDeleteTaskModal">
      <div class="delete-modal" @click.stop>
        <div class="delete-modal-header">
          <h3>Supprimer la tâche</h3>
          <button @click="closeDeleteTaskModal" class="close-btn">×</button>
        </div>
        <div class="delete-modal-content">
          <div class="warning-icon">⚠️</div>
          <p>
            Êtes-vous sûr de vouloir supprimer la tâche
            <strong>"{{ taskToDelete?.title }}"</strong> ?
          </p>
          <p class="warning-text">Cette action est irréversible.</p>
        </div>
        <div class="delete-modal-actions">
          <button @click="closeDeleteTaskModal" class="btn btn-secondary">Annuler</button>
          <button @click="confirmDeleteTask" class="btn btn-danger" :disabled="isDeletingTask">
            <span v-if="isDeletingTask">Suppression...</span>
            <span v-else>Supprimer</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { projectService, type Project } from '@/services/projectService'
import { taskService, type Task, type CreateTaskData } from '@/services/taskService'
import { authService } from '@/services/authService'
import TaskModal from '@/components/task/TaskModal.vue'
import TaskAssignmentModal from '@/components/task/TaskAssignmentModal.vue'

const route = useRoute()
const router = useRouter()

// État du composant
const project = ref<Project | null>(null)
const tasks = ref<Task[]>([])
const showTaskModal = ref(false)
const showAssignmentModal = ref(false)
const selectedTask = ref<Task | null>(null)
const isEditMode = ref(false)
const showColorMenu = ref<string | null>(null)
const isLoading = ref(false)
const showDeleteTaskModal = ref(false)
const taskToDelete = ref<Task | null>(null)
const isDeletingTask = ref(false)

// État pour l'édition de la description
const isEditingDescription = ref(false)
const editedDescription = ref('')
const isSaving = ref(false)

// Couleurs des colonnes
const columnColors = ref({
  todo: '#f8f9fa',
  in_progress: '#f8f9fa',
  completed: '#f8f9fa',
})

const availableColors = [
  { name: 'Blanc', value: '#f8f9fa' },
  { name: 'Bleu clair', value: '#e3f2fd' },
  { name: 'Vert clair', value: '#e8f5e8' },
  { name: 'Jaune clair', value: '#fff8e1' },
  { name: 'Orange clair', value: '#fff3e0' },
  { name: 'Rouge clair', value: '#ffebee' },
  { name: 'Violet clair', value: '#f3e5f5' },
  { name: 'Gris clair', value: '#f5f5f5' },
]

// Computed
const tasksByStatus = computed(() => {
  return {
    todo: tasks.value.filter((task) => task.status === 'todo'),
    in_progress: tasks.value.filter((task) => task.status === 'in_progress'),
    completed: tasks.value.filter((task) => task.status === 'completed'),
  }
})

// Méthodes
const loadProject = async () => {
  const projectId = route.params.id as string
  try {
    project.value = await projectService.getProject(parseInt(projectId))
  } catch (error) {
    console.error('Erreur lors du chargement du projet:', error)
  }
}

const loadTasks = async () => {
  if (!project.value?.id) return

  isLoading.value = true
  try {
    tasks.value = await taskService.getTasksByProject(project.value.id)
  } catch (error) {
    console.error('Erreur lors du chargement des tâches:', error)
  } finally {
    isLoading.value = false
  }
}

const goBack = () => {
  router.push('/dashboard')
}

const openCreateTaskModal = (status?: string) => {
  selectedTask.value = status ? ({ status } as Task) : null
  isEditMode.value = false
  showTaskModal.value = true
}

const openTaskModal = (task: Task) => {
  selectedTask.value = task
  isEditMode.value = true
  showTaskModal.value = true
}

const editTask = (task: Task) => {
  selectedTask.value = task
  isEditMode.value = true
  showTaskModal.value = true
}

const deleteTask = (task: Task) => {
  taskToDelete.value = task
  showDeleteTaskModal.value = true
}

const closeDeleteTaskModal = () => {
  showDeleteTaskModal.value = false
  taskToDelete.value = null
  isDeletingTask.value = false
}

const confirmDeleteTask = async () => {
  if (!taskToDelete.value) return

  isDeletingTask.value = true
  try {
    await taskService.deleteTask(taskToDelete.value.id)
    tasks.value = tasks.value.filter((t) => t.id !== taskToDelete.value!.id)
    closeDeleteTaskModal()
    console.log('Tâche supprimée avec succès')
  } catch (error) {
    console.error('Erreur lors de la suppression:', error)
    alert('Erreur lors de la suppression de la tâche. Veuillez réessayer.')
  } finally {
    isDeletingTask.value = false
  }
}

const closeTaskModal = () => {
  showTaskModal.value = false
  selectedTask.value = null
  isEditMode.value = false
}

const handleTaskSave = async (taskData: CreateTaskData) => {
  try {
    if (isEditMode.value && selectedTask.value) {
      const updatedTask = await taskService.updateTask(selectedTask.value.id, taskData)
      const index = tasks.value.findIndex((t) => t.id === selectedTask.value!.id)
      if (index !== -1) {
        tasks.value[index] = updatedTask
      }
    } else {
      if (!project.value?.id) {
        throw new Error('Projet non trouvé')
      }
      const newTask = await taskService.createTask({
        ...taskData,
        projectId: project.value.id,
      })
      tasks.value.push(newTask)
    }
    closeTaskModal()
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error)
  }
}

const openAssignmentModal = (task: Task) => {
  selectedTask.value = task
  showAssignmentModal.value = true
}

const closeAssignmentModal = () => {
  showAssignmentModal.value = false
  selectedTask.value = null
}

const handleTaskAssignment = async (assignmentData: { userId: number; reason: string }) => {
  if (!selectedTask.value) return

  try {
    const updatedTask = await taskService.assignTask(selectedTask.value.id, assignmentData)
    const index = tasks.value.findIndex((t) => t.id === selectedTask.value!.id)
    if (index !== -1) {
      tasks.value[index] = updatedTask
    }
    closeAssignmentModal()
  } catch (error) {
    console.error("Erreur lors de l'assignation:", error)
  }
}

const changeColumnColor = (status: string, color: string) => {
  columnColors.value[status as keyof typeof columnColors.value] = color
  showColorMenu.value = null
}

const toggleColorMenu = (status: string) => {
  showColorMenu.value = showColorMenu.value === status ? null : status
}

// Méthodes pour l'édition de la description
const toggleEditDescription = () => {
  if (isEditingDescription.value) {
    cancelEditDescription()
  } else {
    isEditingDescription.value = true
    editedDescription.value = project.value?.description || ''
  }
}

const cancelEditDescription = () => {
  isEditingDescription.value = false
  editedDescription.value = ''
}

const saveDescription = async () => {
  if (!project.value) return

  isSaving.value = true
  try {
    // Appel au service pour mettre à jour le projet en base de données
    const response = await projectService.updateProject(project.value.id, {
      description: editedDescription.value,
    })

    // Mise à jour de l'objet projet local avec les données retournées
    project.value.description = response.project.description
    project.value.updatedAt = response.project.updatedAt

    isEditingDescription.value = false
    editedDescription.value = ''

    console.log('Description sauvegardée en base de données:', response.message)
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error)
    // Optionnel: afficher un message d'erreur à l'utilisateur
    alert('Erreur lors de la sauvegarde de la description. Veuillez réessayer.')
  } finally {
    isSaving.value = false
  }
}

// Lifecycle
onMounted(async () => {
  // Vérifier l'authentification
  if (!authService.isAuthenticated()) {
    router.push('/login')
    return
  }

  await loadProject()
  await loadTasks()

  // Fermer le menu de couleur en cliquant ailleurs
  document.addEventListener('click', (e) => {
    if (!(e.target as Element).closest('.column-menu')) {
      showColorMenu.value = null
    }
  })
})
</script>

<style scoped>
.project-tasks-view {
  padding-top: 1rem; /* Un peu de padding en haut pour l'espacement */
  padding-left: 2rem;
  padding-right: 2rem;
  padding-bottom: 2rem;
  min-height: 100vh;
  background: var(--background-light);
}

/* Header fixe et espacement */
.content-spacing {
  height: 2rem; /* Espacement réduit entre la description et le contenu */
}

/* Section description du projet */
.project-description-section {
  margin-top: 7rem; /* Espacement plus important depuis le header fixe */
  padding: 1.5rem 2rem;
  background: var(--white);
  border-radius: 12px; /* Arrondi comme les colonnes */
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin-bottom: 1rem; /* Espacement supplémentaire en bas */
  margin-left: 1rem;
  margin-right: 1rem;
}

.description-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.description-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 1.1rem;
  font-weight: 600;
}

.btn-edit {
  background: #415a77;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-edit:hover {
  background: #1b263b;
  transform: translateY(-1px);
}

.description-edit-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.description-textarea {
  width: 100%;
  padding: 1rem;
  border: 2px solid var(--border-light);
  border-radius: 8px;
  font-size: 1rem;
  font-family: inherit;
  resize: vertical;
  min-height: 100px;
  transition: border-color 0.2s ease;
}

.description-textarea:focus {
  outline: none;
  border-color: var(--primary-color);
}

.edit-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.no-description {
  color: var(--text-secondary);
  font-style: italic;
  margin: 0;
}

/* Override du style global pour le header fixe */
.app-header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  width: 100%;
}

.header-left {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.project-description {
  font-size: 1rem;
  color: var(--text-secondary);
  margin: 0;
  font-weight: 400;
  line-height: 1.5;
  max-width: 100%;
  word-wrap: break-word;
}

.kanban-board {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.kanban-columns {
  display: flex;
  gap: 1.5rem;
  justify-content: center;
  align-items: flex-start;
}

.kanban-column {
  flex: 1;
  min-width: 300px;
  background: #f8f9fa;
  border-radius: 8px;
  padding: 1rem;
  transition: background-color 0.3s;
}

.column-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  position: relative;
}

.column-header h3 {
  margin: 0;
  font-size: 1.2rem;
  font-weight: 600;
  color: #333;
}

.column-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.add-card-btn {
  background: #415a77;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.9rem;
  font-weight: 500;
  transition: all 0.2s ease;
}

.add-card-btn:hover {
  background: #1b263b;
  transform: translateY(-1px);
}

.column-menu {
  position: relative;
}

.column-menu-btn {
  background: #778da9;
  color: white;
  border: none;
  padding: 0.5rem;
  border-radius: 6px;
  cursor: pointer;
  font-size: 1.2rem;
  transition: all 0.2s ease;
}

.column-menu-btn:hover {
  background: #415a77;
  transform: translateY(-1px);
}

.color-picker {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 0.5rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 1000;
}

.color-options {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.5rem;
}

.color-option {
  width: 30px;
  height: 30px;
  border: 2px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
  transition: transform 0.2s;
}

.color-option:hover {
  transform: scale(1.1);
  border-color: #007bff;
}

.tasks-list {
  min-height: 200px;
}

.task-card {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  transition:
    transform 0.2s,
    box-shadow 0.2s;
}

.task-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.task-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
}

.task-header h4 {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  color: #333;
  flex: 1;
}

.task-actions {
  display: flex;
  gap: 0.25rem;
}

.action-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
  transition: all 0.2s ease;
  color: #415a77;
  display: flex;
  align-items: center;
  justify-content: center;
}

.action-btn:hover {
  background: rgba(65, 90, 119, 0.1);
  color: #1b263b;
  transform: translateY(-1px);
}

.action-btn svg {
  color: inherit;
}

.task-description {
  margin: 0 0 1rem 0;
  color: #666;
  font-size: 0.9rem;
  line-height: 1.4;
}

.task-skills {
  margin: 0.5rem 0;
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
}

.task-skills .skill-tag {
  background: #e3f2fd;
  color: #1976d2;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  border: 1px solid #bbdefb;
}

.task-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.priority {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: 500;
  text-transform: uppercase;
}

.priority.high {
  background: #ffebee;
  color: #c62828;
}

.priority.medium {
  background: #fff3e0;
  color: #ef6c00;
}

.priority.low {
  background: #e8f5e8;
  color: #2e7d32;
}

.assignee {
  font-size: 0.8rem;
  color: #666;
  background: #f8f9fa;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.loading {
  text-align: center;
  padding: 2rem;
  color: white;
  font-size: 1.1rem;
}

/* Responsive */
@media (max-width: 768px) {
  .kanban-columns {
    flex-direction: column;
  }

  .kanban-column {
    min-width: auto;
  }

  .project-tasks-view {
    padding: 1rem;
  }

  .header h1 {
    font-size: 2rem;
  }
}

/* Styles pour la modal de suppression de tâche */
.delete-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  animation: fadeIn 0.3s ease;
}

.delete-modal {
  background: white;
  border-radius: 12px;
  padding: 0;
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow: hidden;
  animation: slideIn 0.3s ease;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.delete-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e5e7eb;
  background: #f9fafb;
}

.delete-modal-header h3 {
  margin: 0;
  color: #1f2937;
  font-size: 1.25rem;
  font-weight: 600;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #6b7280;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background: #e5e7eb;
  color: #374151;
}

.delete-modal-content {
  padding: 2rem;
  text-align: center;
}

.warning-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.delete-modal-content p {
  margin: 0 0 1rem 0;
  color: #374151;
  line-height: 1.6;
}

.warning-text {
  color: #dc2626 !important;
  font-size: 0.9rem;
  font-weight: 500;
}

.delete-modal-actions {
  display: flex;
  gap: 1rem;
  padding: 1.5rem 2rem;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
  justify-content: flex-end;
}

.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  border: none;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-primary {
  background: #415a77;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #1b263b;
  transform: translateY(-1px);
}

.btn-secondary {
  background: #f8f9fa;
  color: #415a77;
  border: 1px solid #415a77;
}

.btn-secondary:hover {
  background: #415a77;
  color: white;
  transform: translateY(-1px);
}

.btn-danger {
  background: #dc2626;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: #b91c1c;
  transform: translateY(-1px);
}

.btn-danger:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

/* Animations */
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Responsive pour la modal */
@media (max-width: 768px) {
  .delete-modal {
    width: 95%;
    margin: 1rem;
  }

  .delete-modal-header,
  .delete-modal-content,
  .delete-modal-actions {
    padding: 1rem 1.5rem;
  }

  .delete-modal-actions {
    flex-direction: column;
  }

  .btn {
    width: 100%;
  }
}
</style>
