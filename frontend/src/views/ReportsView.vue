<template>
  <div class="reports-view">
    <div class="page-header">
      <h1>Rapports et Statistiques</h1>
      <p>Analysez les performances et la progression des projets</p>
    </div>

    <div class="reports-content">
      <!-- Statistiques générales -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">📊</div>
          <div class="stat-info">
            <h3>{{ totalTasks }}</h3>
            <p>Tâches totales</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">✅</div>
          <div class="stat-info">
            <h3>{{ completedTasks }}</h3>
            <p>Tâches terminées</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">⏳</div>
          <div class="stat-info">
            <h3>{{ inProgressTasks }}</h3>
            <p>En cours</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">⚠️</div>
          <div class="stat-info">
            <h3>{{ overdueTasks }}</h3>
            <p>En retard</p>
          </div>
        </div>
      </div>

      <!-- Graphiques -->
      <div class="charts-section">
        <div class="chart-container">
          <h2>Répartition des tâches par statut</h2>
          <div class="chart-placeholder">
            <div class="chart-bars">
              <div class="bar" :style="{ height: `${(completedTasks / totalTasks) * 100}%` }">
                <span class="bar-label">Terminées</span>
                <span class="bar-value">{{ completedTasks }}</span>
              </div>
              <div class="bar" :style="{ height: `${(inProgressTasks / totalTasks) * 100}%` }">
                <span class="bar-label">En cours</span>
                <span class="bar-value">{{ inProgressTasks }}</span>
              </div>
              <div class="bar" :style="{ height: `${(todoTasks / totalTasks) * 100}%` }">
                <span class="bar-label">À faire</span>
                <span class="bar-value">{{ todoTasks }}</span>
              </div>
              <div class="bar" :style="{ height: `${(overdueTasks / totalTasks) * 100}%` }">
                <span class="bar-label">En retard</span>
                <span class="bar-value">{{ overdueTasks }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="chart-container">
          <h2>Performance par utilisateur</h2>
          <div class="user-performance">
            <div v-for="user in userPerformance" :key="user.id" class="user-stat">
              <div class="user-info">
                <div class="user-avatar">
                  {{ user.firstName.charAt(0) }}{{ user.lastName.charAt(0) }}
                </div>
                <div class="user-details">
                  <span class="user-name">{{ user.firstName }} {{ user.lastName }}</span>
                  <span class="user-role">{{ getRoleLabel(user.role) }}</span>
                </div>
              </div>
              <div class="user-metrics">
                <div class="metric">
                  <span class="metric-label">Tâches assignées</span>
                  <span class="metric-value">{{ user.assignedTasks }}</span>
                </div>
                <div class="metric">
                  <span class="metric-label">Terminées</span>
                  <span class="metric-value">{{ user.completedTasks }}</span>
                </div>
                <div class="metric">
                  <span class="metric-label">Taux de réussite</span>
                  <span class="metric-value">{{ user.completionRate }}%</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tableau des projets -->
      <div class="projects-section">
        <h2>État des projets</h2>
        <div class="projects-table">
          <table>
            <thead>
              <tr>
                <th>Projet</th>
                <th>Responsable</th>
                <th>Statut</th>
                <th>Progression</th>
                <th>Tâches</th>
                <th>Échéance</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="project in projects" :key="project.id">
                <td class="project-cell">
                  <div class="project-name">{{ project.name }}</div>
                  <div class="project-description">{{ project.description }}</div>
                </td>
                <td class="manager-cell">
                  <div class="manager-info">
                    <div class="manager-avatar">
                      {{ project.manager.firstName.charAt(0) }}{{ project.manager.lastName.charAt(0) }}
                    </div>
                    <span>{{ project.manager.firstName }} {{ project.manager.lastName }}</span>
                  </div>
                </td>
                <td class="status-cell">
                  <span class="status-badge" :class="getStatusClass(project.status)">
                    {{ getStatusLabel(project.status) }}
                  </span>
                </td>
                <td class="progress-cell">
                  <div class="progress-bar">
                    <div class="progress-fill" :style="{ width: `${project.progress}%` }"></div>
                    <span class="progress-text">{{ project.progress }}%</span>
                  </div>
                </td>
                <td class="tasks-cell">
                  <div class="tasks-summary">
                    <span class="completed">{{ project.completedTasks }}</span>
                    <span class="separator">/</span>
                    <span class="total">{{ project.totalTasks }}</span>
                  </div>
                </td>
                <td class="deadline-cell">
                  <span :class="{ 'overdue': isOverdue(project.deadline) }">
                    {{ formatDate(project.deadline) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Chargement des rapports...</p>
    </div>

    <div v-if="error" class="error">
      <p>{{ error }}</p>
      <button @click="loadReports" class="btn btn-primary">Réessayer</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { roleService } from '@/services/roleService'

// Données simulées pour les rapports
const loading = ref(false)
const error = ref('')

const totalTasks = ref(156)
const completedTasks = ref(89)
const inProgressTasks = ref(34)
const todoTasks = ref(23)
const overdueTasks = ref(10)

const userPerformance = ref([
  {
    id: 1,
    firstName: 'Jean',
    lastName: 'Dupont',
    role: 'ROLE_COLLABORATOR',
    assignedTasks: 12,
    completedTasks: 10,
    completionRate: 83
  },
  {
    id: 2,
    firstName: 'Marie',
    lastName: 'Martin',
    role: 'ROLE_COLLABORATOR',
    assignedTasks: 15,
    completedTasks: 12,
    completionRate: 80
  },
  {
    id: 3,
    firstName: 'Pierre',
    lastName: 'Durand',
    role: 'ROLE_MANAGER',
    assignedTasks: 8,
    completedTasks: 7,
    completionRate: 88
  }
])

const projects = ref([
  {
    id: 1,
    name: 'Application Web',
    description: 'Développement d\'une application web moderne',
    manager: {
      firstName: 'Sophie',
      lastName: 'Leroy'
    },
    status: 'in_progress',
    progress: 75,
    completedTasks: 15,
    totalTasks: 20,
    deadline: '2024-02-15'
  },
  {
    id: 2,
    name: 'API Backend',
    description: 'Création de l\'API REST',
    manager: {
      firstName: 'Thomas',
      lastName: 'Moreau'
    },
    status: 'completed',
    progress: 100,
    completedTasks: 12,
    totalTasks: 12,
    deadline: '2024-01-30'
  },
  {
    id: 3,
    name: 'Tests Unitaires',
    description: 'Implémentation des tests',
    manager: {
      firstName: 'Julie',
      lastName: 'Petit'
    },
    status: 'planning',
    progress: 25,
    completedTasks: 3,
    totalTasks: 12,
    deadline: '2024-03-01'
  }
])

const loadReports = async () => {
  try {
    loading.value = true
    error.value = ''
    
    // TODO: Remplacer par des appels API réels
    // const reports = await reportService.getReports()
    
    // Simuler un délai de chargement
    await new Promise(resolve => setTimeout(resolve, 1000))
    
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Erreur lors du chargement des rapports'
  } finally {
    loading.value = false
  }
}

const getRoleLabel = (role: string): string => {
  return roleService.getRoleLabel(role)
}

const getStatusLabel = (status: string): string => {
  const statusLabels: Record<string, string> = {
    'planning': 'Planification',
    'in_progress': 'En cours',
    'completed': 'Terminé',
    'on_hold': 'En attente',
    'cancelled': 'Annulé'
  }
  return statusLabels[status] || status
}

const getStatusClass = (status: string): string => {
  const statusClasses: Record<string, string> = {
    'planning': 'status-planning',
    'in_progress': 'status-in-progress',
    'completed': 'status-completed',
    'on_hold': 'status-on-hold',
    'cancelled': 'status-cancelled'
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
  loadReports()
})
</script>

<style scoped>
.reports-view {
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

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 40px;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon {
  font-size: 32px;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
}

.stat-info h3 {
  margin: 0 0 4px 0;
  font-size: 28px;
  font-weight: 700;
  color: #2c3e50;
}

.stat-info p {
  margin: 0;
  color: #7f8c8d;
  font-size: 14px;
}

.charts-section {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 30px;
  margin-bottom: 40px;
}

.chart-container {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.chart-container h2 {
  margin: 0 0 20px 0;
  color: #2c3e50;
  font-size: 18px;
}

.chart-bars {
  display: flex;
  align-items: end;
  gap: 16px;
  height: 200px;
  padding: 20px 0;
}

.bar {
  flex: 1;
  background: linear-gradient(to top, #3498db, #2980b9);
  border-radius: 4px 4px 0 0;
  position: relative;
  min-height: 20px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 8px 4px;
}

.bar-label {
  font-size: 12px;
  color: white;
  font-weight: 500;
  text-align: center;
}

.bar-value {
  font-size: 14px;
  color: white;
  font-weight: bold;
  text-align: center;
}

.user-performance {
  max-height: 300px;
  overflow-y: auto;
}

.user-stat {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 0;
  border-bottom: 1px solid #ecf0f1;
}

.user-stat:last-child {
  border-bottom: none;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 14px;
}

.user-name {
  font-weight: 600;
  color: #2c3e50;
}

.user-role {
  font-size: 12px;
  color: #7f8c8d;
}

.user-metrics {
  display: flex;
  gap: 20px;
}

.metric {
  text-align: center;
}

.metric-label {
  display: block;
  font-size: 12px;
  color: #7f8c8d;
  margin-bottom: 4px;
}

.metric-value {
  font-weight: bold;
  color: #2c3e50;
}

.projects-section {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.projects-section h2 {
  margin: 0 0 20px 0;
  color: #2c3e50;
}

.projects-table {
  overflow-x: auto;
}

.projects-table table {
  width: 100%;
  border-collapse: collapse;
}

.projects-table th,
.projects-table td {
  padding: 16px;
  text-align: left;
  border-bottom: 1px solid #ecf0f1;
}

.projects-table th {
  background: #f8f9fa;
  font-weight: 600;
  color: #2c3e50;
}

.project-name {
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 4px;
}

.project-description {
  font-size: 12px;
  color: #7f8c8d;
}

.manager-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.manager-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 12px;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-planning {
  background: #fff3e0;
  color: #ff9800;
}

.status-in-progress {
  background: #e3f2fd;
  color: #2196f3;
}

.status-completed {
  background: #e8f5e8;
  color: #27ae60;
}

.status-on-hold {
  background: #f5f5f5;
  color: #757575;
}

.status-cancelled {
  background: #ffebee;
  color: #f44336;
}

.progress-bar {
  position: relative;
  background: #ecf0f1;
  border-radius: 10px;
  height: 20px;
  overflow: hidden;
}

.progress-fill {
  background: linear-gradient(90deg, #27ae60, #2ecc71);
  height: 100%;
  transition: width 0.3s ease;
}

.progress-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 11px;
  font-weight: 600;
  color: #2c3e50;
}

.tasks-summary {
  display: flex;
  align-items: center;
  gap: 4px;
}

.completed {
  color: #27ae60;
  font-weight: 600;
}

.separator {
  color: #7f8c8d;
}

.total {
  color: #2c3e50;
}

.overdue {
  color: #e74c3c;
  font-weight: 600;
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

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-primary {
  background: #3498db;
  color: white;
}

.btn-primary:hover {
  background: #2980b9;
}

@media (max-width: 768px) {
  .charts-section {
    grid-template-columns: 1fr;
  }
  
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .user-metrics {
    flex-direction: column;
    gap: 8px;
  }
  
  .projects-table {
    font-size: 14px;
  }
  
  .projects-table th,
  .projects-table td {
    padding: 12px 8px;
  }
}
</style>
