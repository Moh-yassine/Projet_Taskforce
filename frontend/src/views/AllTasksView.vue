<template>
  <div class="all-tasks-view">
    <div class="page-header">
      <h1>Toutes les Tâches</h1>
      <p>Vue d'ensemble de toutes les tâches du système</p>
    </div>

    <div class="tasks-content">
      <!-- Filtres et actions -->
      <div class="tasks-controls">
        <div class="filters">
          <div class="filter-group">
            <label>Statut :</label>
            <select v-model="filters.status" @change="applyFilters">
              <option value="">Tous</option>
              <option value="todo">À faire</option>
              <option value="in_progress">En cours</option>
              <option value="completed">Terminé</option>
              <option value="cancelled">Annulé</option>
            </select>
          </div>
          <div class="filter-group">
            <label>Priorité :</label>
            <select v-model="filters.priority" @change="applyFilters">
              <option value="">Toutes</option>
              <option value="low">Faible</option>
              <option value="medium">Moyenne</option>
              <option value="high">Élevée</option>
              <option value="urgent">Urgente</option>
            </select>
          </div>
          <div class="filter-group">
            <label>Projet :</label>
            <select v-model="filters.project" @change="applyFilters">
              <option value="">Tous les projets</option>
              <option v-for="project in projects" :key="project.id" :value="project.id">
                {{ project.name }}
              </option>
            </select>
          </div>
        </div>
        <div class="actions">
          <button @click="refreshTasks" class="btn btn-secondary" :disabled="loading">
            <i class="icon">🔄</i>
            Actualiser
          </button>
        </div>
      </div>

      <!-- Statistiques rapides -->
      <div class="tasks-stats">
        <div class="stat-item">
          <span class="stat-number">{{ totalTasks }}</span>
          <span class="stat-label">Total</span>
        </div>
        <div class="stat-item">
          <span class="stat-number">{{ completedTasks }}</span>
          <span class="stat-label">Terminées</span>
        </div>
        <div class="stat-item">
          <span class="stat-number">{{ inProgressTasks }}</span>
          <span class="stat-label">En cours</span>
        </div>
        <div class="stat-item">
          <span class="stat-number">{{ overdueTasks }}</span>
          <span class="stat-label">En retard</span>
        </div>
      </div>

      <!-- Liste des tâches -->
      <div class="tasks-list">
        <div v-if="loading" class="loading">
          <div class="spinner"></div>
          <p>Chargement des tâches...</p>
        </div>

        <div v-else-if="error" class="error">
          <p>{{ error }}</p>
          <button @click="loadTasks" class="btn btn-primary">Réessayer</button>
        </div>

        <div v-else-if="filteredTasks.length === 0" class="empty-state">
          <div class="empty-icon">📋</div>
          <h3>Aucune tâche trouvée</h3>
          <p>Il n'y a actuellement aucune tâche correspondant à vos critères.</p>
        </div>

        <div v-else class="tasks-grid">
          <div v-for="task in filteredTasks" :key="task.id" class="task-card">
            <div class="task-header">
              <div class="task-title">{{ task.title }}</div>
              <div class="task-priority" :class="getPriorityClass(task.priority)">
                {{ getPriorityLabel(task.priority) }}
              </div>
            </div>

            <div class="task-description">{{ task.description }}</div>

            <div class="task-meta">
              <div class="task-project">
                <i class="icon">📁</i>
                <span>{{ task.project?.name || 'Sans projet' }}</span>
              </div>
              <div class="task-assignee" v-if="task.assignee">
                <i class="icon">👤</i>
                <span>{{ task.assignee.firstName }} {{ task.assignee.lastName }}</span>
              </div>
            </div>

            <div class="task-status">
              <span class="status-badge" :class="getStatusClass(task.status)">
                {{ getStatusLabel(task.status) }}
              </span>
              <span
                v-if="task.dueDate"
                class="due-date"
                :class="{ overdue: isOverdue(task.dueDate) }"
              >
                <i class="icon">📅</i>
                {{ formatDate(task.dueDate) }}
              </span>
            </div>

            <div class="task-skills" v-if="task.skills && task.skills.length > 0">
              <div class="skills-label">Compétences :</div>
              <div class="skills-list">
                <span v-for="skill in task.skills" :key="skill.id" class="skill-tag">
                  {{ skill.name }}
                </span>
              </div>
            </div>

            <div class="task-actions">
              <button @click="viewTask(task)" class="btn btn-sm btn-primary">
                <i class="icon">👁️</i>
                Voir
              </button>
              <button
                v-if="canEditTask(task)"
                @click="editTask(task)"
                class="btn btn-sm btn-secondary"
              >
                <i class="icon">✏️</i>
                Modifier
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { taskService } from '@/services/taskService'
import { projectService } from '@/services/projectService'
import { roleService } from '@/services/roleService'

interface Task {
  id: number
  title: string
  description: string
  status: string
  priority: string
  dueDate?: string
  project?: {
    id: number
    name: string
  }
  assignee?: {
    id: number
    firstName: string
    lastName: string
  }
  skills?: Array<{
    id: number
    name: string
  }>
}

interface Project {
  id: number
  name: string
}

const tasks = ref<Task[]>([])
const projects = ref<Project[]>([])
const loading = ref(false)
const error = ref('')

const filters = ref({
  status: '',
  priority: '',
  project: '',
})

const filteredTasks = computed(() => {
  let filtered = tasks.value

  if (filters.value.status) {
    filtered = filtered.filter((task) => task.status === filters.value.status)
  }

  if (filters.value.priority) {
    filtered = filtered.filter((task) => task.priority === filters.value.priority)
  }

  if (filters.value.project) {
    filtered = filtered.filter((task) => task.project?.id === parseInt(filters.value.project))
  }

  return filtered
})

const totalTasks = computed(() => tasks.value.length)
const completedTasks = computed(() => tasks.value.filter((t) => t.status === 'completed').length)
const inProgressTasks = computed(() => tasks.value.filter((t) => t.status === 'in_progress').length)
const overdueTasks = computed(() => {
  const now = new Date()
  return tasks.value.filter(
    (t) => t.dueDate && new Date(t.dueDate) < now && t.status !== 'completed',
  ).length
})

const loadTasks = async () => {
  try {
    loading.value = true
    error.value = ''

    // TODO: Remplacer par des appels API réels
    // const tasksData = await taskService.getAllTasks()

    // Données simulées
    tasks.value = [
      {
        id: 1,
        title: "Implémenter l'authentification",
        description: "Créer le système d'authentification JWT",
        status: 'completed',
        priority: 'high',
        dueDate: '2024-01-15',
        project: { id: 1, name: 'Application Web' },
        assignee: { id: 1, firstName: 'Jean', lastName: 'Dupont' },
        skills: [
          { id: 1, name: 'JavaScript' },
          { id: 2, name: 'Node.js' },
        ],
      },
      {
        id: 2,
        title: "Créer l'interface utilisateur",
        description: 'Développer les composants Vue.js',
        status: 'in_progress',
        priority: 'medium',
        dueDate: '2024-02-01',
        project: { id: 1, name: 'Application Web' },
        assignee: { id: 2, firstName: 'Marie', lastName: 'Martin' },
        skills: [
          { id: 3, name: 'Vue.js' },
          { id: 4, name: 'CSS' },
        ],
      },
      {
        id: 3,
        title: 'Tests unitaires',
        description: 'Écrire les tests pour les services',
        status: 'todo',
        priority: 'low',
        dueDate: '2024-02-15',
        project: { id: 2, name: 'API Backend' },
        assignee: { id: 3, firstName: 'Pierre', lastName: 'Durand' },
        skills: [
          { id: 5, name: 'Jest' },
          { id: 6, name: 'Testing' },
        ],
      },
    ]
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Erreur lors du chargement des tâches'
  } finally {
    loading.value = false
  }
}

const loadProjects = async () => {
  try {
    // TODO: Remplacer par des appels API réels
    // const projectsData = await projectService.getProjects()

    // Données simulées
    projects.value = [
      { id: 1, name: 'Application Web' },
      { id: 2, name: 'API Backend' },
      { id: 3, name: 'Tests Unitaires' },
    ]
  } catch (err) {
    console.error('Erreur lors du chargement des projets:', err)
  }
}

const refreshTasks = () => {
  loadTasks()
}

const applyFilters = () => {
  // Les filtres sont appliqués automatiquement via computed
}

const viewTask = (task: Task) => {
  // TODO: Implémenter la vue détaillée de la tâche
  console.log('Voir la tâche:', task)
}

const editTask = (task: Task) => {
  // TODO: Implémenter l'édition de la tâche
  console.log('Modifier la tâche:', task)
}

const canEditTask = (task: Task): boolean => {
  // Vérifier les permissions selon le rôle de l'utilisateur
  return roleService.canSuperviseTasks()
}

const getPriorityLabel = (priority: string): string => {
  const priorityLabels: Record<string, string> = {
    low: 'Faible',
    medium: 'Moyenne',
    high: 'Élevée',
    urgent: 'Urgente',
  }
  return priorityLabels[priority] || priority
}

const getPriorityClass = (priority: string): string => {
  const priorityClasses: Record<string, string> = {
    low: 'priority-low',
    medium: 'priority-medium',
    high: 'priority-high',
    urgent: 'priority-urgent',
  }
  return priorityClasses[priority] || 'priority-default'
}

const getStatusLabel = (status: string): string => {
  const statusLabels: Record<string, string> = {
    todo: 'À faire',
    in_progress: 'En cours',
    completed: 'Terminé',
    cancelled: 'Annulé',
  }
  return statusLabels[status] || status
}

const getStatusClass = (status: string): string => {
  const statusClasses: Record<string, string> = {
    todo: 'status-todo',
    in_progress: 'status-in-progress',
    completed: 'status-completed',
    cancelled: 'status-cancelled',
  }
  return statusClasses[status] || 'status-default'
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('fr-FR')
}

const isOverdue = (dateString: string): boolean => {
  return new Date(dateString) < new Date()
}

onMounted(() => {
  loadTasks()
  loadProjects()
})
</script>

<style scoped>
.all-tasks-view {
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

.tasks-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding: 20px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.filters {
  display: flex;
  gap: 20px;
  align-items: center;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-group label {
  font-weight: 500;
  color: #2c3e50;
}

.filter-group select {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
}

.tasks-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 16px;
  margin-bottom: 30px;
}

.stat-item {
  background: white;
  border-radius: 8px;
  padding: 16px;
  text-align: center;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.stat-number {
  display: block;
  font-size: 24px;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 4px;
}

.stat-label {
  font-size: 12px;
  color: #7f8c8d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.tasks-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.task-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  border: 1px solid #e1e8ed;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.task-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.task-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}

.task-title {
  font-weight: 600;
  color: #2c3e50;
  font-size: 16px;
  line-height: 1.4;
}

.task-priority {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.priority-low {
  background: #e8f5e8;
  color: #27ae60;
}

.priority-medium {
  background: #fff3e0;
  color: #ff9800;
}

.priority-high {
  background: #ffebee;
  color: #f44336;
}

.priority-urgent {
  background: #f3e5f5;
  color: #9c27b0;
}

.task-description {
  color: #7f8c8d;
  font-size: 14px;
  line-height: 1.5;
  margin-bottom: 16px;
}

.task-meta {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 16px;
}

.task-project,
.task-assignee {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #2c3e50;
}

.task-status {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-todo {
  background: #f5f5f5;
  color: #757575;
}

.status-in-progress {
  background: #e3f2fd;
  color: #2196f3;
}

.status-completed {
  background: #e8f5e8;
  color: #27ae60;
}

.status-cancelled {
  background: #ffebee;
  color: #f44336;
}

.due-date {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #7f8c8d;
}

.due-date.overdue {
  color: #e74c3c;
  font-weight: 600;
}

.task-skills {
  margin-bottom: 16px;
}

.skills-label {
  font-size: 12px;
  color: #7f8c8d;
  margin-bottom: 8px;
}

.skills-list {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.skill-tag {
  background: #e3f2fd;
  color: #1976d2;
  padding: 2px 6px;
  border-radius: 8px;
  font-size: 10px;
  font-weight: 500;
}

.task-actions {
  display: flex;
  gap: 8px;
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
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
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
  .tasks-controls {
    flex-direction: column;
    gap: 16px;
    align-items: stretch;
  }

  .filters {
    flex-direction: column;
    gap: 12px;
  }

  .filter-group {
    flex-direction: column;
    align-items: stretch;
  }

  .tasks-grid {
    grid-template-columns: 1fr;
  }

  .task-actions {
    flex-direction: column;
  }
}
</style>
