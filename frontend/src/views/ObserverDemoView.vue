<template>
  <div class="observer-demo-container">
    <!-- Header -->
    <div class="demo-header">
      <button @click="goBack" class="back-btn">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
          <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        Retour au dashboard
      </button>
      <h1>Démonstration du Mode Observateur</h1>
      <p>Cette page démontre comment le mode observateur limite les actions des utilisateurs observés.</p>
    </div>

    <!-- Contenu principal -->
    <div class="demo-content">
      <!-- Section d'information -->
      <div class="info-section">
        <div class="info-card">
          <div class="info-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
            </svg>
          </div>
          <div class="info-content">
            <h3>Mode Observateur Activé</h3>
            <p>Les utilisateurs marqués avec l'icône d'œil sont en observation. Leurs actions peuvent être limitées selon vos paramètres Premium.</p>
          </div>
        </div>
      </div>

      <!-- Section des tâches de démonstration -->
      <div class="tasks-section">
        <h2>Tâches de Démonstration</h2>
        <div class="tasks-grid">
          <TaskCard 
            v-for="task in demoTasks" 
            :key="task.id"
            :task="task"
            @edit="handleEditTask"
            @delete="handleDeleteTask"
          />
        </div>
      </div>

      <!-- Section des actions de démonstration -->
      <div class="actions-section">
        <h2>Actions de Démonstration</h2>
        <div class="actions-grid">
          <!-- Création de tâche -->
          <ObserverGuard 
            action="create"
            custom-message="La création de nouvelles tâches est restreinte pour les utilisateurs observés."
          >
            <div class="action-card">
              <div class="action-icon">➕</div>
              <h3>Créer une tâche</h3>
              <p>Cette action est protégée par le mode observateur</p>
              <button class="btn btn-primary">Créer</button>
            </div>
          </ObserverGuard>

          <!-- Modification de projet -->
          <ObserverGuard 
            action="modify"
            custom-message="La modification des paramètres de projet est restreinte."
          >
            <div class="action-card">
              <div class="action-icon">⚙️</div>
              <h3>Modifier le projet</h3>
              <p>Cette action est protégée par le mode observateur</p>
              <button class="btn btn-secondary">Modifier</button>
            </div>
          </ObserverGuard>

          <!-- Suppression d'éléments -->
          <ObserverGuard 
            action="delete"
            custom-message="La suppression d'éléments est restreinte pour les utilisateurs observés."
          >
            <div class="action-card">
              <div class="action-icon">🗑️</div>
              <h3>Supprimer des éléments</h3>
              <p>Cette action est protégée par le mode observateur</p>
              <button class="btn btn-danger">Supprimer</button>
            </div>
          </ObserverGuard>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import TaskCard from '@/components/TaskCard.vue'
import ObserverGuard from '@/components/ObserverGuard.vue'

const router = useRouter()

// Tâches de démonstration
const demoTasks = ref([
  {
    id: 1,
    title: 'Tâche assignée à un utilisateur observé',
    description: 'Cette tâche est assignée à un utilisateur qui est en observation.',
    status: 'in_progress',
    priority: 'high',
    dueDate: '2024-01-15',
    createdAt: '2024-01-10',
    assignedTo: {
      id: 2,
      firstName: 'Jean',
      lastName: 'Dupont',
      email: 'jean.dupont@example.com'
    }
  },
  {
    id: 2,
    title: 'Tâche assignée à un utilisateur normal',
    description: 'Cette tâche est assignée à un utilisateur qui n\'est pas en observation.',
    status: 'todo',
    priority: 'medium',
    dueDate: '2024-01-20',
    createdAt: '2024-01-12',
    assignedTo: {
      id: 3,
      firstName: 'Marie',
      lastName: 'Martin',
      email: 'marie.martin@example.com'
    }
  },
  {
    id: 3,
    title: 'Tâche sans assignation',
    description: 'Cette tâche n\'est assignée à personne.',
    status: 'todo',
    priority: 'low',
    dueDate: '2024-01-25',
    createdAt: '2024-01-14',
    assignedTo: undefined
  }
])

// Gestionnaires d'événements
const handleEditTask = (task: any) => {
  console.log('Modification de la tâche:', task)
  alert(`Modification de la tâche: ${task.title}`)
}

const handleDeleteTask = (task: any) => {
  console.log('Suppression de la tâche:', task)
  if (confirm(`Êtes-vous sûr de vouloir supprimer la tâche "${task.title}" ?`)) {
    alert(`Tâche "${task.title}" supprimée`)
  }
}

const goBack = () => {
  router.push('/dashboard')
}
</script>

<style scoped>
:root {
  --deep-dark: #0D1B2A;
  --deep-navy: #1B263B;
  --deep-blue: #415A77;
  --deep-light: #778DA9;
  --deep-pale: #E0E1DD;
  --primary-color: var(--deep-blue);
  --primary-hover: var(--deep-navy);
  --white: #ffffff;
  --text-primary: var(--deep-dark);
  --text-secondary: var(--deep-blue);
  --success-color: #10b981;
  --error-color: #ef4444;
  --warning-color: #f59e0b;
}

.observer-demo-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  padding: 2rem;
}

.demo-header {
  max-width: 1200px;
  margin: 0 auto 3rem auto;
}

.back-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: var(--white);
  color: var(--text-primary);
  border: 2px solid var(--deep-light);
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  margin-bottom: 2rem;
}

.back-btn:hover {
  background: var(--deep-light);
  color: var(--white);
  transform: translateY(-1px);
}

.demo-header h1 {
  color: var(--text-primary);
  font-size: 2.5rem;
  font-weight: 800;
  margin: 0 0 1rem 0;
}

.demo-header p {
  color: var(--text-secondary);
  font-size: 1.2rem;
  margin: 0;
  line-height: 1.6;
}

.demo-content {
  max-width: 1200px;
  margin: 0 auto;
}

.info-section {
  margin-bottom: 3rem;
}

.info-card {
  background: var(--white);
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 10px 40px rgba(27, 38, 59, 0.1);
  display: flex;
  align-items: center;
  gap: 1.5rem;
  border: 3px solid transparent;
  background-clip: padding-box;
  position: relative;
}

.info-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, #f59e0b, #fbbf24, #f59e0b);
  border-radius: 16px;
  padding: 3px;
  mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  mask-composite: exclude;
  -webkit-mask-composite: xor;
  z-index: -1;
}

.info-icon {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, var(--warning-color), #fbbf24);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--white);
  box-shadow: 0 8px 25px rgba(245, 158, 11, 0.3);
  flex-shrink: 0;
}

.info-content h3 {
  color: var(--text-primary);
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
}

.info-content p {
  color: var(--text-secondary);
  font-size: 1rem;
  margin: 0;
  line-height: 1.5;
}

.tasks-section,
.actions-section {
  margin-bottom: 3rem;
}

.tasks-section h2,
.actions-section h2 {
  color: var(--text-primary);
  font-size: 2rem;
  font-weight: 700;
  margin: 0 0 2rem 0;
}

.tasks-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 1.5rem;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.action-card {
  background: var(--white);
  border-radius: 12px;
  padding: 2rem;
  text-align: center;
  box-shadow: 0 4px 20px rgba(27, 38, 59, 0.1);
  border: 2px solid transparent;
  transition: all 0.3s ease;
}

.action-card:hover {
  border-color: var(--primary-color);
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(27, 38, 59, 0.15);
}

.action-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.action-card h3 {
  color: var(--text-primary);
  font-size: 1.3rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
}

.action-card p {
  color: var(--text-secondary);
  margin: 0 0 1.5rem 0;
  line-height: 1.5;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  display: inline-block;
}

.btn-primary {
  background: var(--primary-color);
  color: var(--white);
}

.btn-primary:hover {
  background: var(--primary-hover);
  transform: translateY(-1px);
}

.btn-secondary {
  background: var(--deep-light);
  color: var(--white);
}

.btn-secondary:hover {
  background: var(--deep-blue);
  transform: translateY(-1px);
}

.btn-danger {
  background: var(--error-color);
  color: var(--white);
}

.btn-danger:hover {
  background: #dc2626;
  transform: translateY(-1px);
}

/* Responsive */
@media (max-width: 768px) {
  .observer-demo-container {
    padding: 1rem;
  }
  
  .demo-header h1 {
    font-size: 2rem;
  }
  
  .demo-header p {
    font-size: 1rem;
  }
  
  .info-card {
    flex-direction: column;
    text-align: center;
    padding: 1.5rem;
  }
  
  .tasks-grid,
  .actions-grid {
    grid-template-columns: 1fr;
  }
  
  .action-card {
    padding: 1.5rem;
  }
}

@media (max-width: 480px) {
  .demo-header h1 {
    font-size: 1.5rem;
  }
  
  .info-card {
    padding: 1rem;
  }
  
  .action-card {
    padding: 1rem;
  }
}
</style>
