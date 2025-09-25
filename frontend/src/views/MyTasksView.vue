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
            <!-- Mode Observateur Premium -->
            <li v-if="hasActiveSubscription" class="nav-item">
              <router-link to="/observer-mode" class="nav-link premium-nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                </svg>
                <span>Mode Observateur</span>
                <div class="premium-nav-badge">PREMIUM</div>
              </router-link>
            </li>
          </ul>
        </div>

        <div class="nav-section">
          <h3 class="nav-title">Actions</h3>
          <ul class="nav-list">
            <li class="nav-item">
              <a href="#" class="nav-link" @click.prevent="logout">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                  <path
                    d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"
                  />
                </svg>
                <span>Déconnexion</span>
              </a>
            </li>
          </ul>
        </div>
      </nav>
    </aside>

    <!-- Main content area -->
    <div class="main-content">
      <div class="tasks-header">
        <h1>Mes tâches</h1>
        <p>Consultez et gérez vos tâches assignées</p>
      </div>

      <div v-if="isLoading" class="loading">
        <div class="loading-spinner"></div>
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
            <select v-model="selectedProject" @change="filterTasks">
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

              <!-- Informations de la tâche -->
              <div class="task-info">
                <div class="task-meta">
                  <span class="task-status" :class="`status-${task.status}`">
                    {{ getStatusLabel(task.status) }}
                  </span>
                  <span class="task-priority" :class="`priority-${task.priority}`">
                    {{ getPriorityLabel(task.priority) }}
                  </span>
                </div>
                <div class="task-dates">
                  <span v-if="task.dueDate" class="task-due-date">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                    </svg>
                    Échéance: {{ formatDate(task.dueDate) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Projet -->
            <div class="task-project">
              <div class="project-info">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M10 4H4c-1.11 0-2 .89-2 2v12c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2h-8l-2-2z"/>
                </svg>
                <span>{{ getProjectName(task.project?.id) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { authService } from '@/services/authService'
import { taskService } from '@/services/taskService'
import { projectService } from '@/services/projectService'
import { paymentService } from '@/services/paymentService'

const router = useRouter()

// État réactif
const isLoading = ref(false)
const tasks = ref<any[]>([])
const projects = ref<any[]>([])
const hasActiveSubscription = ref(false)

// Filtres
const selectedProject = ref('')
const selectedStatus = ref('')
const selectedPriority = ref('')

// Computed properties
const canManageProjects = computed(() => {
  const user = authService.getCurrentUser()
  return user?.roles?.includes('ROLE_PROJECT_MANAGER') || false
})

const canAccessAdmin = computed(() => {
  const user = authService.getCurrentUser()
  return user?.roles?.includes('ROLE_PROJECT_MANAGER') || user?.roles?.includes('ROLE_MANAGER')
})

const canDeleteTasks = computed(() => {
  const user = authService.getCurrentUser()
  return user?.roles?.includes('ROLE_PROJECT_MANAGER') || false
})

const filteredTasks = computed(() => {
  let filtered = tasks.value

  if (selectedProject.value) {
    filtered = filtered.filter(task => task.project?.id === parseInt(selectedProject.value))
  }

  if (selectedStatus.value) {
    filtered = filtered.filter(task => task.status === selectedStatus.value)
  }

  if (selectedPriority.value) {
    filtered = filtered.filter(task => task.priority === selectedPriority.value)
  }

  return filtered
})

// Méthodes
const loadTasks = async () => {
  try {
    isLoading.value = true
    tasks.value = await taskService.getAllTasks()
  } catch (error) {
    console.error('Erreur lors du chargement des tâches:', error)
  } finally {
    isLoading.value = false
  }
}

const loadProjects = async () => {
  try {
    projects.value = await projectService.getProjects()
  } catch (error) {
    console.error('Erreur lors du chargement des projets:', error)
  }
}

const checkSubscriptionStatus = async () => {
  try {
    const config = await paymentService.getConfig()
    hasActiveSubscription.value = config.hasActiveSubscription
  } catch (error) {
    console.error('Erreur lors de la vérification du statut d\'abonnement:', error)
    hasActiveSubscription.value = false
  }
}

const filterTasks = () => {
  // Le filtrage est géré par le computed filteredTasks
}

const getStatusLabel = (status: string): string => {
  const labels: Record<string, string> = {
    'todo': 'À faire',
    'in_progress': 'En cours',
    'completed': 'Terminé'
  }
  return labels[status] || status
}

const getPriorityLabel = (priority: string): string => {
  const labels: Record<string, string> = {
    'low': 'Faible',
    'medium': 'Moyenne',
    'high': 'Élevée',
    'urgent': 'Urgente'
  }
  return labels[priority] || priority
}

const getProjectName = (projectId: number): string => {
  const project = projects.value.find(p => p.id === projectId)
  return project?.name || 'Projet inconnu'
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('fr-FR')
}

const editTask = (task: any) => {
  // Rediriger vers le projet pour éditer la tâche
  router.push(`/project/${task.project?.id}/tasks`)
}

const deleteTask = async (task: any) => {
  if (!confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) return
  
  try {
    await taskService.deleteTask(task.id)
    tasks.value = tasks.value.filter(t => t.id !== task.id)
  } catch (error) {
    console.error('Erreur lors de la suppression de la tâche:', error)
    alert('Erreur lors de la suppression de la tâche')
  }
}

const goToProjects = () => {
  router.push('/dashboard')
}

const logout = async () => {
  try {
    await authService.logout()
    router.push('/login')
  } catch (error) {
    console.error('Erreur lors de la déconnexion:', error)
  }
}

// Lifecycle
onMounted(async () => {
  await Promise.all([
    loadTasks(),
    loadProjects(),
    checkSubscriptionStatus()
  ])
})
</script>

<style scoped>
.my-tasks-container {
  display: flex;
  min-height: 100vh;
  background: #f8fafc;
}

/* Sidebar */
.sidebar {
  width: 280px;
  background: #1e293b;
  color: white;
  padding: 0;
  display: flex;
  flex-direction: column;
}

.sidebar-header {
  padding: 24px 20px;
  border-bottom: 1px solid #334155;
}

.sidebar-header h2 {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
  color: #f1f5f9;
}

.sidebar-nav {
  flex: 1;
  padding: 20px 0;
}

.nav-section {
  margin-bottom: 32px;
}

.nav-title {
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #94a3b8;
  margin: 0 0 12px 0;
  padding: 0 20px;
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
  gap: 12px;
  padding: 12px 20px;
  color: #cbd5e1;
  text-decoration: none;
  transition: all 0.2s;
  border-left: 3px solid transparent;
}

.nav-link:hover {
  background: #334155;
  color: #f1f5f9;
}

.nav-item.active .nav-link {
  background: #3b82f6;
  color: white;
  border-left-color: #60a5fa;
}

.nav-link span {
  font-weight: 500;
}

/* Premium nav link */
.premium-nav-link {
  position: relative;
  background: linear-gradient(135deg, #ffd700, #ffed4e);
  color: #333 !important;
  font-weight: 600;
}

.premium-nav-link:hover {
  background: linear-gradient(135deg, #ffed4e, #ffd700);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
}

.premium-nav-badge {
  position: absolute;
  top: -8px;
  right: -8px;
  background: #ff6b6b;
  color: white;
  font-size: 10px;
  font-weight: 800;
  padding: 2px 6px;
  border-radius: 10px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  box-shadow: 0 2px 4px rgba(255, 107, 107, 0.3);
}

/* Main content */
.main-content {
  flex: 1;
  padding: 40px;
  overflow-y: auto;
}

.tasks-header {
  margin-bottom: 32px;
}

.tasks-header h1 {
  margin: 0 0 8px 0;
  font-size: 32px;
  font-weight: 700;
  color: #1e293b;
}

.tasks-header p {
  margin: 0;
  font-size: 16px;
  color: #64748b;
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 400px;
  color: #64748b;
}

.loading-spinner {
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

.no-tasks {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 400px;
}

.no-tasks-content {
  text-align: center;
  color: #64748b;
}

.no-tasks-content svg {
  color: #cbd5e1;
  margin-bottom: 16px;
}

.no-tasks-content h3 {
  margin: 0 0 8px 0;
  font-size: 20px;
  font-weight: 600;
  color: #1e293b;
}

.no-tasks-content p {
  margin: 0;
  font-size: 16px;
}

/* Filtres */
.filters-section {
  display: flex;
  gap: 24px;
  margin-bottom: 32px;
  padding: 24px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.filter-group label {
  font-weight: 500;
  color: #374151;
  font-size: 14px;
}

.filter-group select {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  background: white;
  min-width: 150px;
}

.filter-group select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Liste des tâches */
.tasks-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.task-item {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: all 0.2s;
}

.task-item:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  transform: translateY(-1px);
}

.task-main {
  margin-bottom: 16px;
}

.task-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}

.task-title {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: #1e293b;
  line-height: 1.4;
}

.task-actions {
  display: flex;
  gap: 8px;
}

.action-btn {
  background: none;
  border: none;
  color: #64748b;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: all 0.2s;
}

.action-btn:hover {
  color: #3b82f6;
  background: #eff6ff;
}

.task-description {
  margin: 0 0 16px 0;
  color: #64748b;
  line-height: 1.5;
}

.task-skills {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 16px;
}

.skill-tag {
  background: #eff6ff;
  color: #1e40af;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
}

.task-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.task-meta {
  display: flex;
  gap: 12px;
}

.task-status,
.task-priority {
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-todo {
  background: #f3f4f6;
  color: #374151;
}

.status-in_progress {
  background: #dbeafe;
  color: #1e40af;
}

.status-completed {
  background: #d1fae5;
  color: #065f46;
}

.priority-low {
  background: #f0fdf4;
  color: #166534;
}

.priority-medium {
  background: #fef3c7;
  color: #92400e;
}

.priority-high {
  background: #fed7d7;
  color: #991b1b;
}

.priority-urgent {
  background: #fecaca;
  color: #7f1d1d;
}

.task-dates {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #64748b;
  font-size: 14px;
}

.task-project {
  padding-top: 16px;
  border-top: 1px solid #e2e8f0;
}

.project-info {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #64748b;
  font-size: 14px;
}

.project-info svg {
  color: #94a3b8;
}

/* Responsive */
@media (max-width: 768px) {
  .my-tasks-container {
    flex-direction: column;
  }
  
  .sidebar {
    width: 100%;
    height: auto;
  }
  
  .main-content {
    padding: 20px;
  }
  
  .filters-section {
    flex-direction: column;
    gap: 16px;
  }
  
  .task-header {
    flex-direction: column;
    gap: 12px;
  }
  
  .task-info {
    flex-direction: column;
    gap: 12px;
    align-items: flex-start;
  }
}
</style>
