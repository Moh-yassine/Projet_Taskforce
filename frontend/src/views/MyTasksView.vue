<template>
  <div class="my-tasks-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <h2>TaskForce</h2>
      </div>

      <nav class="sidebar-nav">
        <div class="nav-section">
          <h3 class="nav-title">Navigation</h3>
          <ul class="nav-list">
            <li class="nav-item">
              <router-link to="/dashboard" class="nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" />
                </svg>
                <span>Tableau de bord</span>
              </router-link>
            </li>
            <li class="nav-item active">
              <router-link to="/my-tasks" class="nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                  <path
                    d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"
                  />
                </svg>
                <span>Mes tâches</span>
              </router-link>
            </li>
            <!-- Projets - Visible seulement pour le Responsable de Projet -->
            <li v-if="canManageProjects" class="nav-item">
              <a href="#" class="nav-link" @click.prevent="goToProjects">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                  <path
                    d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"
                  />
                </svg>
                <span>Projets</span>
              </a>
            </li>
            <!-- Admin - Visible pour le Responsable de Projet et le Manager -->
            <li v-if="canAccessAdmin" class="nav-item">
              <router-link to="/admin" class="nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                  <path
                    d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 1H5C3.89 1 3 1.89 3 3V21C3 22.11 3.89 23 5 23H19C20.11 23 21 22.11 21 21V9M19 9H14V4H5V21H19V9Z"
                  />
                </svg>
                <span>Admin</span>
              </router-link>
            </li>
          </ul>
        </div>

        <!-- Section Compte -->
        <div class="nav-section account-section">
          <h3 class="nav-title">Compte</h3>

          <!-- Informations du compte -->
          <div class="account-info">
            <div class="account-avatar-container">
              <AvatarSelector
                :model-value="user?.avatar"
                @update:model-value="updateUserAvatar"
                class="account-avatar-selector"
              />
            </div>
            <div class="account-details">
              <div class="account-name">{{ user?.firstName }} {{ user?.lastName }}</div>
              <div class="account-email">{{ user?.email }}</div>
              <div class="account-member-since">
                Membre depuis: {{ user?.createdAt ? formatDate(user.createdAt) : 'N/A' }}
              </div>
            </div>
          </div>

          <!-- Bouton de déconnexion -->
          <button @click="handleLogout" class="account-logout-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <path
                d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"
              />
            </svg>
            <span>Se déconnecter</span>
          </button>
        </div>
      </nav>
    </aside>

    <!-- Main content area -->
    <div class="main-content">
      <div v-if="isLoading" class="loading">
        <p>Chargement de vos tâches...</p>
      </div>

      <div v-else-if="tasks.length === 0" class="no-tasks">
        <div class="no-tasks-content">
          <svg
            width="64"
            height="64"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1"
          >
            <path
              d="M9 11H5a2 2 0 0 0-2 2v3c0 1.1.9 2 2 2h4m0-7v7m0-7h10a2 2 0 0 1 2 2v3c0 1.1-.9 2-2 2H9m0-7V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"
            />
          </svg>
          <h3>Aucune tâche trouvée</h3>
          <p>Vous n'avez pas encore de tâches assignées.</p>
        </div>
      </div>

      <div v-else class="tasks-content">
        <!-- Filtres -->
        <div class="filters-section">
          <div class="filter-group">
            <label>Projet:</label>
            <select v-model="selectedProject" @change="loadTasksForProject">
              <option value="">Tous les projets</option>
              <option v-for="project in projects" :key="project.id" :value="project.id">
                {{ project.name }}
              </option>
            </select>
          </div>
          <div class="filter-group">
            <label>Statut:</label>
            <select v-model="selectedStatus" @change="filterTasks">
              <option value="">Tous</option>
              <option value="todo">À faire</option>
              <option value="in_progress">En cours</option>
              <option value="completed">Terminé</option>
            </select>
          </div>
          <div class="filter-group">
            <label>Priorité:</label>
            <select v-model="selectedPriority" @change="filterTasks">
              <option value="">Toutes</option>
              <option value="low">Faible</option>
              <option value="medium">Moyenne</option>
              <option value="high">Élevée</option>
              <option value="urgent">Urgente</option>
            </select>
          </div>
        </div>

        <!-- Liste des tâches -->
        <div class="tasks-list">
          <div v-for="task in filteredTasks" :key="task.id" class="task-item">
            <div class="task-main">
              <div class="task-header">
                <h3 class="task-title">{{ task.title }}</h3>
                <div class="task-actions">
                  <button @click="editTask(task)" class="action-btn">
                    <svg
                      width="16"
                      height="16"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                    >
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                  </button>
                  <button v-if="canDeleteTasks" @click="deleteTask(task)" class="action-btn">
                    <svg
                      width="16"
                      height="16"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
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
            </div>

            <div class="task-details">
              <div class="task-meta">
                <span class="task-status" :class="`status-${task.status}`">
                  {{ getStatusLabel(task.status) }}
                </span>
                <span class="task-priority" :class="`priority-${task.priority}`">
                  {{ getPriorityLabel(task.priority) }}
                </span>
              </div>

              <div class="task-info">
                <div v-if="task.project" class="task-project">
                  <strong>Projet:</strong> {{ task.project.name }}
                </div>
                <div v-if="task.assignee" class="task-assignee">
                  <strong>Assigné à:</strong> {{ task.assignee.firstName }}
                  {{ task.assignee.lastName }}
                </div>
                <div class="task-dates">
                  <span v-if="task.createdAt">
                    <strong>Créé le:</strong> {{ formatDate(task.createdAt) }}
                  </span>
                  <span v-if="task.updatedAt && task.updatedAt !== task.createdAt">
                    <strong>Modifié le:</strong> {{ formatDate(task.updatedAt) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Task Modal -->
      <TaskModal
        v-if="showTaskModal"
        :task="selectedTask"
        @close="closeTaskModal"
        @save="handleTaskSave"
      />

      <!-- Modal de confirmation de suppression -->
      <div v-if="showDeleteModal" class="delete-modal-overlay" @click="closeDeleteModal">
        <div class="delete-modal" @click.stop>
          <div class="delete-modal-header">
            <h3>Supprimer la tâche</h3>
            <button @click="closeDeleteModal" class="close-btn">×</button>
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
            <button @click="closeDeleteModal" class="btn btn-secondary">Annuler</button>
            <button @click="confirmDeleteTask" class="btn btn-danger" :disabled="isDeleting">
              <span v-if="isDeleting">Suppression...</span>
              <span v-else>Supprimer</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { taskService } from '@/services/taskService'
import { authService, type User } from '@/services/authService'
import AvatarSelector from '@/components/AvatarSelector.vue'
import { projectService } from '@/services/projectService'
import TaskModal from '../components/task/TaskModal.vue'

interface Task {
  id: number
  title: string
  description: string
  status: 'todo' | 'in_progress' | 'completed'
  priority: 'low' | 'medium' | 'high' | 'urgent'
  createdAt: string
  updatedAt: string
  project?: {
    id: number
    name: string
  }
  assignee?: {
    id: number
    firstName: string
    lastName: string
  }
  skills: Array<{
    id: number
    name: string
  }>
}

interface Project {
  id: number
  name: string
  description: string
  status: string
  createdAt: string
}

const router = useRouter()
const tasks = ref<Task[]>([])
const projects = ref<Project[]>([])
const isLoading = ref(false)
const selectedProject = ref('')
const selectedStatus = ref('')
const selectedPriority = ref('')
const showTaskModal = ref(false)
const selectedTask = ref<Task | null>(null)
const showDeleteModal = ref(false)
const taskToDelete = ref<Task | null>(null)
const isDeleting = ref(false)
const user = ref<User | null>(null)

const canManageProjects = computed(() => {
  return user.value?.permissions?.canManageProjects || false
})

const canAccessAdmin = computed(() => {
  return user.value?.permissions?.canAccessAdmin || false
})

const canDeleteTasks = computed(() => {
  return user.value?.permissions?.canManageProjects || false
})

// Computed
const filteredTasks = computed(() => {
  let filtered = tasks.value

  if (selectedProject.value) {
    filtered = filtered.filter((task) => task.project?.id === parseInt(selectedProject.value))
  }

  if (selectedStatus.value) {
    filtered = filtered.filter((task) => task.status === selectedStatus.value)
  }

  if (selectedPriority.value) {
    filtered = filtered.filter((task) => task.priority === selectedPriority.value)
  }

  return filtered
})

// Méthodes
const goBack = () => {
  router.push('/dashboard')
}

const loadProjects = async () => {
  try {
    projects.value = await projectService.getProjects()
  } catch (error) {
    console.error('Erreur lors du chargement des projets:', error)
  }
}

const loadTasksForProject = () => {
  // Le filtrage est géré par le computed filteredTasks
  // Pas besoin de recharger les données, juste filtrer
}

const loadAllTasks = async () => {
  isLoading.value = true
  try {
    // Charger uniquement les tâches assignées à l'utilisateur connecté
    const { userService } = await import('@/services/userService')
    const myTasks = await userService.getMyTasks()
    tasks.value = myTasks

    // Charger les projets pour le filtre (uniquement les projets des tâches assignées)
    const uniqueProjects = new Map()
    myTasks.forEach((task) => {
      if (task.project) {
        uniqueProjects.set(task.project.id, task.project)
      }
    })
    projects.value = Array.from(uniqueProjects.values())
  } catch (error) {
    console.error('Erreur lors du chargement de mes tâches:', error)
    tasks.value = []
    projects.value = []
  } finally {
    isLoading.value = false
  }
}

const filterTasks = () => {
  // Le filtrage est géré par le computed filteredTasks
}

const editTask = (task: Task) => {
  selectedTask.value = task
  showTaskModal.value = true
}

const deleteTask = (task: Task) => {
  taskToDelete.value = task
  showDeleteModal.value = true
}

const confirmDeleteTask = async () => {
  if (!taskToDelete.value) return

  isDeleting.value = true
  try {
    await taskService.deleteTask(taskToDelete.value.id)
    tasks.value = tasks.value.filter((t) => t.id !== taskToDelete.value!.id)
    closeDeleteModal()
    console.log('Tâche supprimée avec succès')
  } catch (error) {
    console.error('Erreur lors de la suppression:', error)
    alert('Erreur lors de la suppression de la tâche. Veuillez réessayer.')
  } finally {
    isDeleting.value = false
  }
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  taskToDelete.value = null
}

const closeTaskModal = () => {
  showTaskModal.value = false
  selectedTask.value = null
}

const handleTaskSave = async (taskData: any) => {
  try {
    if (selectedTask.value) {
      // Mise à jour
      const updatedTask = await taskService.updateTask(selectedTask.value.id, taskData)
      const index = tasks.value.findIndex((t) => t.id === selectedTask.value!.id)
      if (index !== -1) {
        tasks.value[index] = updatedTask
      }
    }
    closeTaskModal()
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error)
  }
}

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    todo: 'À faire',
    in_progress: 'En cours',
    completed: 'Terminé',
  }
  return labels[status] || status
}

const getPriorityLabel = (priority: string) => {
  const labels: Record<string, string> = {
    low: 'Faible',
    medium: 'Moyenne',
    high: 'Élevée',
    urgent: 'Urgente',
  }
  return labels[priority] || priority
}

const formatDate = (dateString: string) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

// Fonctions manquantes
const goToProjects = () => {
  router.push('/dashboard')
}

const updateUserAvatar = (newAvatar: string) => {
  if (user.value) {
    user.value.avatar = newAvatar
    // Optionnel: sauvegarder en base de données
  }
}

const handleLogout = () => {
  authService.logout()
  router.push('/login')
}

// Initialisation
onMounted(async () => {
  // Vérifier l'authentification
  if (!authService.isAuthenticated()) {
    router.push('/login')
    return
  }

  // Charger les informations utilisateur
  const userData = authService.getCurrentUser()
  if (userData) {
    user.value = userData
  }

  await loadProjects()
  await loadAllTasks()
})
</script>

<style scoped>
.my-tasks-container {
  min-height: 100vh;
  background: var(--background-light);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  display: flex;
  padding-top: 0;
}

/* Sidebar */
.sidebar {
  width: 280px;
  background: var(--deep-navy);
  color: var(--white);
  position: fixed;
  top: 0;
  left: 0;
  height: 100vh;
  z-index: 200;
  overflow-y: auto;
  border-right: 1px solid var(--deep-dark);
}

.sidebar-header {
  padding: 1.5rem 1rem;
  border-bottom: 1px solid var(--deep-dark);
}

.sidebar-header h2 {
  color: var(--white);
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
  text-align: center;
}

.sidebar-nav {
  padding: 1rem 0;
}

.nav-section {
  margin-bottom: 2rem;
}

.nav-title {
  color: var(--deep-light);
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin: 0 0 0.75rem 1rem;
}

.nav-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.nav-item {
  margin: 0;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  color: var(--deep-light);
  text-decoration: none;
  transition: all 0.2s ease;
  border-left: 3px solid transparent;
}

.nav-link:hover {
  background: var(--deep-dark);
  color: var(--white);
}

.nav-item.active .nav-link {
  background: var(--deep-dark);
  color: var(--white);
  border-left-color: var(--primary-color);
}

.nav-link svg {
  flex-shrink: 0;
}

/* Section Compte */
.account-section {
  margin-top: auto;
  padding-top: 1rem;
  border-top: 1px solid var(--deep-dark);
}

.account-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  margin-bottom: 1rem;
  background: var(--deep-dark);
  border-radius: 8px;
  margin-left: 1rem;
  margin-right: 1rem;
}

.account-avatar-container {
  display: flex;
  justify-content: center;
  width: 100%;
}

.account-details {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  width: 100%;
}

.account-name {
  font-weight: 700;
  font-size: 0.9rem;
  color: var(--white);
  margin-bottom: 0.25rem;
  word-wrap: break-word;
  line-height: 1.2;
}

.account-email {
  font-size: 0.75rem;
  color: var(--deep-light);
  margin-bottom: 0.25rem;
  word-wrap: break-word;
  overflow-wrap: break-word;
  line-height: 1.2;
}

.account-member-since {
  font-size: 0.7rem;
  color: var(--deep-light);
  word-wrap: break-word;
  line-height: 1.2;
}

.account-logout-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: calc(100% - 2rem);
  padding: 0.75rem 1rem;
  margin: 0.5rem 1rem;
  background: var(--danger-color);
  color: var(--white);
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.account-logout-btn:hover {
  background: #c82333;
  transform: translateY(-1px);
}

.account-logout-btn svg {
  flex-shrink: 0;
}

/* Main content area */
.main-content {
  flex: 1;
  margin-left: 280px;
  min-height: 100vh;
  padding: 2rem;
}

.loading,
.no-tasks {
  text-align: center;
  padding: 3rem;
  color: var(--text-primary);
}

.no-tasks-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.no-tasks-content svg {
  opacity: 0.5;
}

.tasks-content {
  max-width: 1200px;
  margin: 0 auto;
}

.filters-section {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  align-items: end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-group label {
  font-weight: 600;
  color: #333;
  font-size: 0.9rem;
}

.filter-group select {
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 0.9rem;
  min-width: 150px;
}

.tasks-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.task-item {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  transition:
    transform 0.2s,
    box-shadow 0.2s;
}

.task-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
}

.task-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.task-title {
  font-size: 1.3rem;
  font-weight: 600;
  color: #333;
  margin: 0;
}

.task-actions {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
  transition: background 0.2s;
  color: #666;
  display: flex;
  align-items: center;
  justify-content: center;
}

.action-btn:hover {
  background: #f8f9fa;
}

.task-description {
  color: #666;
  line-height: 1.5;
  margin: 0 0 1rem 0;
}

.task-skills {
  margin: 0.5rem 0 1rem 0;
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.task-skills .skill-tag {
  background: #e3f2fd;
  color: #1976d2;
  padding: 0.25rem 0.75rem;
  border-radius: 16px;
  font-size: 0.8rem;
  font-weight: 500;
  border: 1px solid #bbdefb;
  transition: all 0.2s ease;
}

.task-skills .skill-tag:hover {
  background: #bbdefb;
  transform: translateY(-1px);
}

.task-details {
  border-top: 1px solid #f0f0f0;
  padding-top: 1rem;
}

.task-meta {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
}

.task-status,
.task-priority {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
}

.status-todo {
  background: #e3f2fd;
  color: #1976d2;
}

.status-in_progress {
  background: #fff3e0;
  color: #f57c00;
}

.status-completed {
  background: #e8f5e8;
  color: #2e7d32;
}

.priority-low {
  background: #f3e5f5;
  color: #7b1fa2;
}

.priority-medium {
  background: #fff3e0;
  color: #ef6c00;
}

.priority-high {
  background: #ffebee;
  color: #c62828;
}

.priority-urgent {
  background: #fce4ec;
  color: #ad1457;
}

.task-info {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  font-size: 0.9rem;
  color: #666;
}

.task-info strong {
  color: #333;
}

/* Responsive pour la sidebar */
@media (max-width: 768px) {
  .sidebar {
    width: 100%;
    transform: translateX(-100%);
    transition: transform 0.3s ease;
  }

  .sidebar.open {
    transform: translateX(0);
  }

  .main-content {
    margin-left: 0;
  }

  /* Responsive pour les informations du compte */
  .account-info {
    gap: 0.5rem;
    padding: 0.75rem;
  }

  .account-avatar-container {
    justify-content: center;
  }

  .account-details {
    align-items: center;
    text-align: center;
  }

  .account-name {
    font-size: 0.85rem;
  }

  .account-email {
    font-size: 0.7rem;
  }

  .account-member-since {
    font-size: 0.65rem;
  }

  .account-logout-btn {
    width: calc(100% - 1rem);
    margin: 0.5rem 0.5rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.8rem;
  }

  .my-tasks-container {
    padding: 1rem;
  }

  .filters-section {
    flex-direction: column;
    align-items: stretch;
  }

  .task-header {
    flex-direction: column;
    gap: 1rem;
  }

  .task-info {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  .account-info {
    margin-left: 0.5rem;
    margin-right: 0.5rem;
    padding: 0.5rem;
  }

  .account-name {
    font-size: 0.8rem;
  }

  .account-email {
    font-size: 0.65rem;
  }

  .account-member-since {
    font-size: 0.6rem;
  }

  .account-logout-btn {
    width: calc(100% - 1rem);
    margin: 0.5rem 0.5rem;
    padding: 0.5rem;
    font-size: 0.75rem;
  }
}

/* Styles pour la modal de suppression */
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
  color: #6b7280;
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
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
  border: none;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.btn-secondary {
  background: #6b7280;
  color: white;
}

.btn-secondary:hover {
  background: #4b5563;
}

.btn-danger {
  background: #dc2626;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: #b91c1c;
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
