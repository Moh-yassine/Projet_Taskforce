<template>
  <div class="tasks-container">
    <header class="tasks-header">
      <div class="header-content">
        <div class="header-left">
          <button @click="goBack" class="btn btn-back">← Retour au dashboard</button>
          <h1>Mes tâches</h1>
        </div>
        <button @click="handleLogout" class="btn btn-logout">Se déconnecter</button>
      </div>
    </header>

    <main class="tasks-main">
      <div class="tasks-section">
        <div class="section-header">
          <h2>Vos tâches</h2>
          <p>Consultez et gérez vos tâches assignées</p>
        </div>

        <div v-if="isLoading" class="loading-state">
          <div class="loading-spinner"></div>
          <p>Chargement de vos tâches...</p>
        </div>

        <div v-else-if="errorMessage" class="error-state">
          <div class="error-icon">⚠️</div>
          <p>{{ errorMessage }}</p>
          <button @click="loadTasks" class="btn btn-primary">Réessayer</button>
        </div>

        <div v-else-if="tasks.length === 0" class="empty-state">
          <div class="empty-icon">📋</div>
          <h3>Aucune tâche assignée</h3>
          <p>Vous n'avez pas encore de tâches assignées. Créez un projet pour commencer !</p>
          <button @click="goToProjects" class="btn btn-primary">Voir mes projets</button>
        </div>

        <div v-else class="tasks-grid">
          <div v-for="task in tasks" :key="task.id" class="task-card">
            <div class="task-header">
              <h3 class="task-title">{{ task.title }}</h3>
              <span class="task-status" :class="`status-${task.status}`">
                {{ getStatusLabel(task.status) }}
              </span>
            </div>

            <div class="task-content">
              <p class="task-description">{{ task.description }}</p>

              <div class="task-meta">
                <div class="task-project">
                  <strong>Projet:</strong> {{ task.project?.name || 'Non assigné' }}
                </div>
                <div class="task-dates">
                  <div v-if="task.dueDate">
                    <strong>Échéance:</strong> {{ formatDate(task.dueDate) }}
                  </div>
                  <div><strong>Créée:</strong> {{ formatDate(task.createdAt) }}</div>
                </div>
              </div>
            </div>

            <div class="task-actions">
              <button @click="viewTask(task)" class="btn btn-sm btn-primary">Voir détails</button>
              <button @click="editTask(task)" class="btn btn-sm btn-secondary">Modifier</button>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { authService } from '@/services/authService'

const router = useRouter()

const tasks = ref([])
const isLoading = ref(false)
const errorMessage = ref('')

// Données de test pour l'instant
const mockTasks = [
  {
    id: 1,
    title: "Concevoir l'interface utilisateur",
    description: "Créer les maquettes et prototypes pour l'interface principale",
    status: 'in_progress',
    project: { name: 'Application Web' },
    dueDate: '2025-09-10',
    createdAt: '2025-09-01',
  },
  {
    id: 2,
    title: "Développer l'API backend",
    description: 'Implémenter les endpoints pour la gestion des utilisateurs',
    status: 'pending',
    project: { name: 'Application Web' },
    dueDate: '2025-09-15',
    createdAt: '2025-09-02',
  },
  {
    id: 3,
    title: 'Tests unitaires',
    description: 'Écrire les tests pour les fonctionnalités principales',
    status: 'completed',
    project: { name: 'Application Web' },
    dueDate: '2025-09-05',
    createdAt: '2025-08-28',
  },
]

const loadTasks = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    // Simulation d'un appel API
    await new Promise((resolve) => setTimeout(resolve, 1000))
    tasks.value = mockTasks
  } catch (error) {
    errorMessage.value = 'Erreur lors du chargement des tâches'
  } finally {
    isLoading.value = false
  }
}

const getStatusLabel = (status: string) => {
  const labels = {
    pending: 'En attente',
    in_progress: 'En cours',
    completed: 'Terminé',
    cancelled: 'Annulé',
  }
  return labels[status] || status
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('fr-FR')
}

const viewTask = (task: any) => {
  // TODO: Implémenter la vue détaillée de la tâche
  console.log('Voir tâche:', task)
}

const editTask = (task: any) => {
  // TODO: Implémenter l'édition de la tâche
  console.log('Modifier tâche:', task)
}

const goBack = () => {
  router.push('/dashboard')
}

const goToProjects = () => {
  router.push('/dashboard')
}

const handleLogout = async () => {
  try {
    await authService.logout()
    router.push('/login')
  } catch (error) {
    console.error('Erreur lors de la déconnexion:', error)
    router.push('/login')
  }
}

onMounted(() => {
  loadTasks()
})
</script>

<style scoped>
.tasks-container {
  min-height: 100vh;
  background: #f8fafc;
}

.tasks-header {
  background: white;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  padding: 1rem 0;
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-left h1 {
  margin: 0;
  color: #1f2937;
  font-size: 1.5rem;
  font-weight: 600;
}

.tasks-main {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.tasks-section {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.section-header {
  margin-bottom: 2rem;
}

.section-header h2 {
  margin: 0 0 0.5rem 0;
  color: #1f2937;
  font-size: 1.25rem;
  font-weight: 600;
}

.section-header p {
  margin: 0;
  color: #6b7280;
}

.loading-state,
.error-state,
.empty-state {
  text-align: center;
  padding: 3rem 1rem;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e5e7eb;
  border-top: 4px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.error-icon,
.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.empty-state h3 {
  margin: 0 0 0.5rem 0;
  color: #1f2937;
}

.empty-state p {
  color: #6b7280;
  margin-bottom: 1.5rem;
}

.tasks-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.task-card {
  background: #f8fafc;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1.5rem;
  transition: all 0.2s;
}

.task-card:hover {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.task-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.task-title {
  margin: 0;
  color: #1f2937;
  font-size: 1.125rem;
  font-weight: 600;
  flex: 1;
}

.task-status {
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-in_progress {
  background: #dbeafe;
  color: #1e40af;
}

.status-completed {
  background: #d1fae5;
  color: #065f46;
}

.status-cancelled {
  background: #fee2e2;
  color: #991b1b;
}

.task-content {
  margin-bottom: 1.5rem;
}

.task-description {
  color: #6b7280;
  margin: 0 0 1rem 0;
  line-height: 1.5;
}

.task-meta {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.task-actions {
  display: flex;
  gap: 0.75rem;
}

.btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

.btn-back {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-back:hover {
  background: #e5e7eb;
}

.btn-logout {
  background: #dc2626;
  color: white;
}

.btn-logout:hover {
  background: #b91c1c;
}

@media (max-width: 768px) {
  .header-content {
    padding: 0 1rem;
  }

  .tasks-main {
    padding: 1rem;
  }

  .tasks-section {
    padding: 1.5rem;
  }

  .tasks-grid {
    grid-template-columns: 1fr;
  }

  .header-left {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
}
</style>
