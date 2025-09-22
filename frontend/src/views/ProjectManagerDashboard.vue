<template>
  <div class="dashboard-container">
    <header class="dashboard-header">
      <div class="header-content">
        <div class="header-left">
          <h1>Dashboard Responsable de Projet</h1>
          <p>Gérez vos projets, tâches et équipes</p>
        </div>
        <div class="header-right">
          <button @click="showCreateProjectModal = true" class="btn btn-primary">
            <span class="icon">+</span>
            Nouveau Projet
          </button>
          <button @click="showCreateTaskModal = true" class="btn btn-secondary">
            <span class="icon">📋</span>
            Nouvelle Tâche
          </button>
          <button @click="handleLogout" class="btn btn-logout">
            <span class="icon">🚪</span>
            Déconnexion
          </button>
        </div>
      </div>
    </header>

    <main class="dashboard-main">
      <div class="stats-overview">
        <div class="stat-card">
          <div class="stat-icon">📊</div>
          <div class="stat-content">
            <h3>{{ stats.totalProjects }}</h3>
            <p>Projets actifs</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">✅</div>
          <div class="stat-content">
            <h3>{{ stats.completedTasks }}</h3>
            <p>Tâches terminées</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">⏰</div>
          <div class="stat-content">
            <h3>{{ stats.overdueTasks }}</h3>
            <p>Tâches en retard</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">👥</div>
          <div class="stat-content">
            <h3>{{ stats.teamMembers }}</h3>
            <p>Membres d'équipe</p>
          </div>
        </div>
      </div>

      <div class="main-content">
        <div class="projects-section">
          <div class="section-header">
            <h2>Mes Projets</h2>
            <div class="section-actions">
              <select v-model="projectFilter" class="filter-select">
                <option value="">Tous les statuts</option>
                <option value="planning">Planification</option>
                <option value="active">Actif</option>
                <option value="on-hold">En pause</option>
                <option value="completed">Terminé</option>
              </select>
              <button @click="refreshProjects" class="btn btn-refresh">
                <span class="icon">🔄</span>
              </button>
            </div>
          </div>

          <div class="projects-grid">
            <div
              v-for="project in filteredProjects"
              :key="project.id"
              class="project-card"
              @click="openProject(project)"
            >
              <div class="project-header">
                <h3>{{ project.name }}</h3>
                <span :class="['status-badge', `status-${project.status}`]">
                  {{ getStatusLabel(project.status) }}
                </span>
              </div>
              <p class="project-description">{{ project.description }}</p>
              <div class="project-meta">
                <div class="meta-item">
                  <span class="icon">📅</span>
                  <span>{{ formatDate(project.endDate) }}</span>
                </div>
                <div class="meta-item">
                  <span class="icon">📋</span>
                  <span>{{ project.taskCount }} tâches</span>
                </div>
                <div class="meta-item">
                  <span class="icon">👥</span>
                  <span>{{ project.teamMemberCount }} membres</span>
                </div>
              </div>
              <div class="project-progress">
                <div class="progress-bar">
                  <div
                    class="progress-fill"
                    :style="{ width: getProjectProgress(project) + '%' }"
                  ></div>
                </div>
                <span class="progress-text">{{ getProjectProgress(project) }}%</span>
              </div>
            </div>
          </div>
        </div>

        <div class="tasks-section">
          <div class="section-header">
            <h2>Tâches Prioritaires</h2>
            <button @click="showAllTasks = !showAllTasks" class="btn btn-toggle">
              {{ showAllTasks ? 'Masquer' : 'Voir tout' }}
            </button>
          </div>

          <div class="tasks-kanban">
            <div class="kanban-column">
              <h3>À faire</h3>
              <div class="task-list">
                <div
                  v-for="task in getTasksByStatus('todo')"
                  :key="task.id"
                  class="task-card"
                  @click="openTask(task)"
                >
                  <div class="task-header">
                    <h4>{{ task.title }}</h4>
                    <span :class="['priority-badge', `priority-${task.priority}`]">
                      {{ getPriorityLabel(task.priority) }}
                    </span>
                  </div>
                  <p class="task-description">{{ task.description }}</p>
                  <div class="task-meta">
                    <span class="due-date">{{ formatDate(task.dueDate) }}</span>
                    <span class="project-name">{{ task.project.name }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="kanban-column">
              <h3>En cours</h3>
              <div class="task-list">
                <div
                  v-for="task in getTasksByStatus('in-progress')"
                  :key="task.id"
                  class="task-card"
                  @click="openTask(task)"
                >
                  <div class="task-header">
                    <h4>{{ task.title }}</h4>
                    <span :class="['priority-badge', `priority-${task.priority}`]">
                      {{ getPriorityLabel(task.priority) }}
                    </span>
                  </div>
                  <p class="task-description">{{ task.description }}</p>
                  <div class="task-meta">
                    <span class="due-date">{{ formatDate(task.dueDate) }}</span>
                    <span class="project-name">{{ task.project.name }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="kanban-column">
              <h3>Terminé</h3>
              <div class="task-list">
                <div
                  v-for="task in getTasksByStatus('completed')"
                  :key="task.id"
                  class="task-card completed"
                  @click="openTask(task)"
                >
                  <div class="task-header">
                    <h4>{{ task.title }}</h4>
                    <span class="priority-badge priority-low"> Terminé </span>
                  </div>
                  <p class="task-description">{{ task.description }}</p>
                  <div class="task-meta">
                    <span class="due-date">{{ formatDate(task.dueDate) }}</span>
                    <span class="project-name">{{ task.project.name }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <CreateProjectModal
      v-if="showCreateProjectModal"
      @close="showCreateProjectModal = false"
      @project-created="handleProjectCreated"
    />

    <CreateTaskModal
      v-if="showCreateTaskModal"
      :projects="projects"
      @close="showCreateTaskModal = false"
      @task-created="handleTaskCreated"
    />

    <ProjectDetailModal
      v-if="selectedProject"
      :project="selectedProject"
      @close="selectedProject = null"
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
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { authService } from '@/services/authService'
import CreateProjectModal from '@/components/project/CreateProjectModal.vue'
import CreateTaskModal from '@/components/task/CreateTaskModal.vue'
import ProjectDetailModal from '@/components/project/ProjectDetailModal.vue'
import TaskDetailModal from '@/components/task/TaskDetailModal.vue'

const router = useRouter()

const projects = ref([])
const tasks = ref([])
const stats = ref({
  totalProjects: 0,
  completedTasks: 0,
  overdueTasks: 0,
  teamMembers: 0,
})

const showCreateProjectModal = ref(false)
const showCreateTaskModal = ref(false)
const selectedProject = ref(null)
const selectedTask = ref(null)
const projectFilter = ref('')
const showAllTasks = ref(false)

const filteredProjects = computed(() => {
  if (!projectFilter.value) return projects.value
  return projects.value.filter((project) => project.status === projectFilter.value)
})

onMounted(async () => {
  if (!authService.isAuthenticated()) {
    router.push('/login')
    return
  }

  const user = authService.getCurrentUser()
  if (!user?.isProjectManager) {
    router.push('/dashboard')
    return
  }

  await loadDashboardData()
})

const loadDashboardData = async () => {
  try {
    await Promise.all([loadProjects(), loadTasks(), loadStats()])
  } catch (error) {
    console.error('Erreur lors du chargement des données:', error)
  }
}

const loadProjects = async () => {
  try {
    const response = await fetch('http://localhost:8000/api/projects', {
      headers: {
        Authorization: `Bearer ${authService.getAuthToken()}`,
      },
    })
    if (response.ok) {
      projects.value = await response.json()
    }
  } catch (error) {
    console.error('Erreur lors du chargement des projets:', error)
  }
}

const loadTasks = async () => {
  try {
    const response = await fetch('http://localhost:8000/api/tasks', {
      headers: {
        Authorization: `Bearer ${authService.getAuthToken()}`,
      },
    })
    if (response.ok) {
      tasks.value = await response.json()
    }
  } catch (error) {
    console.error('Erreur lors du chargement des tâches:', error)
  }
}

const loadStats = async () => {
  try {
    const response = await fetch('http://localhost:8000/api/projects', {
      headers: {
        Authorization: `Bearer ${authService.getAuthToken()}`,
      },
    })
    if (response.ok) {
      const allProjects = await response.json()
      const allTasks = await fetch('http://localhost:8000/api/tasks', {
        headers: {
          Authorization: `Bearer ${authService.getAuthToken()}`,
        },
      }).then((res) => (res.ok ? res.json() : []))

      stats.value = {
        totalProjects: allProjects.length,
        completedTasks: allTasks.filter((t) => t.status === 'completed').length,
        overdueTasks: allTasks.filter((t) => {
          if (t.status === 'completed') return false
          return new Date(t.dueDate) < new Date()
        }).length,
        teamMembers: new Set(allProjects.flatMap((p) => p.teamMembers?.map((m) => m.id) || []))
          .size,
      }
    }
  } catch (error) {
    console.error('Erreur lors du chargement des statistiques:', error)
  }
}

const getTasksByStatus = (status: string) => {
  return tasks.value.filter((task) => task.status === status)
}

const getStatusLabel = (status: string) => {
  const labels = {
    planning: 'Planification',
    active: 'Actif',
    'on-hold': 'En pause',
    completed: 'Terminé',
  }
  return labels[status] || status
}

const getPriorityLabel = (priority: string) => {
  const labels = {
    low: 'Basse',
    medium: 'Moyenne',
    high: 'Haute',
    urgent: 'Urgente',
  }
  return labels[priority] || priority
}

const getProjectProgress = (project: any) => {
  if (!project.tasks || project.tasks.length === 0) return 0
  const completedTasks = project.tasks.filter((t: any) => t.status === 'completed').length
  return Math.round((completedTasks / project.tasks.length) * 100)
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('fr-FR')
}

const openProject = (project: any) => {
  selectedProject.value = project
}

const openTask = (task: any) => {
  selectedTask.value = task
}

const handleProjectCreated = async () => {
  showCreateProjectModal.value = false
  await loadDashboardData()
}

const handleTaskCreated = async () => {
  showCreateTaskModal.value = false
  await loadDashboardData()
}

const handleProjectUpdated = async () => {
  selectedProject.value = null
  await loadDashboardData()
}

const handleTaskUpdated = async () => {
  selectedTask.value = null
  await loadDashboardData()
}

const refreshProjects = async () => {
  await loadDashboardData()
}

const handleLogout = () => {
  authService.logout()
  router.push('/')
}
</script>

<style scoped>
.dashboard-container {
  min-height: 100vh;
  background: #f5f6f8;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.dashboard-header {
  background: #ffffff;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  padding: 1rem 0;
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-content {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-left h1 {
  color: #172b4d;
  font-size: 1.8rem;
  font-weight: 700;
  margin: 0 0 0.25rem 0;
}

.header-left p {
  color: #6b778c;
  margin: 0;
}

.header-right {
  display: flex;
  gap: 1rem;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
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

.btn-logout {
  background: #de350b;
  color: white;
}

.btn-logout:hover {
  background: #bf2600;
}

.dashboard-main {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
}

.stats-overview {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 3rem;
}

.stat-card {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  font-size: 2rem;
}

.stat-content h3 {
  font-size: 2rem;
  font-weight: 700;
  color: #172b4d;
  margin: 0;
}

.stat-content p {
  color: #6b778c;
  margin: 0;
}

.main-content {
  display: grid;
  gap: 3rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-header h2 {
  color: #172b4d;
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0;
}

.section-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.filter-select {
  padding: 0.5rem;
  border: 1px solid #dfe1e6;
  border-radius: 4px;
  background: white;
}

.btn-refresh {
  padding: 0.5rem;
  background: #f4f5f7;
  border: 1px solid #dfe1e6;
  border-radius: 4px;
}

.projects-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.project-card {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  transition: all 0.2s ease;
}

.project-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.project-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.project-header h3 {
  color: #172b4d;
  font-size: 1.2rem;
  font-weight: 600;
  margin: 0;
  flex: 1;
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

.project-description {
  color: #6b778c;
  margin-bottom: 1rem;
  line-height: 1.4;
}

.project-meta {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #6b778c;
  font-size: 0.9rem;
}

.project-progress {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.progress-bar {
  flex: 1;
  height: 6px;
  background: #f4f5f7;
  border-radius: 3px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: #0079bf;
  transition: width 0.3s ease;
}

.progress-text {
  color: #6b778c;
  font-size: 0.9rem;
  font-weight: 600;
  min-width: 40px;
}

.tasks-section {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.tasks-kanban {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2rem;
}

.kanban-column h3 {
  color: #172b4d;
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 1rem;
  text-align: center;
}

.task-list {
  min-height: 200px;
}

.task-card {
  background: #f4f5f7;
  padding: 1rem;
  border-radius: 6px;
  margin-bottom: 1rem;
  cursor: pointer;
  transition: all 0.2s ease;
  border-left: 4px solid #dfe1e6;
}

.task-card:hover {
  background: #ebecf0;
  transform: translateY(-1px);
}

.task-card.completed {
  opacity: 0.7;
  border-left-color: #36b37e;
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

.task-description {
  color: #6b778c;
  font-size: 0.9rem;
  margin-bottom: 0.75rem;
  line-height: 1.4;
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

.project-name {
  color: #0079bf;
  font-weight: 500;
}

@media (max-width: 1200px) {
  .tasks-kanban {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .kanban-column {
    margin-bottom: 2rem;
  }
}

@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }

  .header-right {
    flex-wrap: wrap;
    justify-content: center;
  }

  .dashboard-main {
    padding: 1rem;
  }

  .stats-overview {
    grid-template-columns: repeat(2, 1fr);
  }

  .projects-grid {
    grid-template-columns: 1fr;
  }

  .section-header {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }
}
</style>
