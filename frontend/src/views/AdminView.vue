<template>
  <div class="admin-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <h2>TaskForce Admin</h2>
      </div>
      
      <div class="nav-section">
        <h3 class="nav-title">Navigation</h3>
        <ul class="nav-list">
          <li class="nav-item">
            <router-link to="/dashboard" class="nav-link">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
              </svg>
              <span>Tableau de bord</span>
            </router-link>
          </li>
          <li class="nav-item">
            <router-link to="/my-tasks" class="nav-link">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
              </svg>
              <span>Mes tâches</span>
            </router-link>
          </li>
          <li class="nav-item active">
            <router-link to="/admin" class="nav-link">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 1H5C3.89 1 3 1.89 3 3V21C3 22.11 3.89 23 5 23H19C20.11 23 21 22.11 21 21V9M19 9H14V4H5V21H19V9Z"/>
              </svg>
              <span>Administration</span>
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
            <div class="account-roles">
              <span v-for="role in userRoles" :key="role" class="role-badge">{{ role }}</span>
            </div>
          </div>
        </div>
        
        <!-- Bouton de déconnexion -->
        <button @click="handleLogout" class="account-logout-btn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
            <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
          </svg>
          Se déconnecter
        </button>
      </div>
    </aside>

    <!-- Contenu principal -->
    <main class="main-content">
      <div class="admin-header">
        <h1>Administration TaskForce</h1>
        <p>Gérez les utilisateurs, projets, assignations automatiques et notifications</p>
      </div>

      <!-- Initialisation des données -->
      <div v-if="!dataInitialized" class="init-section">
        <div class="init-card">
          <div class="init-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 1H5C3.89 1 3 1.89 3 3V21C3 22.11 3.89 23 5 23H19C20.11 23 21 22.11 21 21V9M19 9H14V4H5V21H19V9Z"/>
            </svg>
          </div>
          <div class="init-content">
            <h3>Charger les données</h3>
            <p>Chargez les utilisateurs, projets, tâches et compétences existants</p>
            <button @click="initializeData" class="btn btn-primary" :disabled="isInitializing">
              <span v-if="isInitializing">Chargement...</span>
              <span v-else>Charger les données</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Contenu principal de l'administration -->
      <div v-else class="admin-content">
        <!-- Statistiques rapides -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon users">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2zm4 18v-6h2.5l-2.54-7.63A1.5 1.5 0 0 0 18.54 8H17c-.8 0-1.54.37-2.01.99L14 10.5l-1-1.5c-.47-.62-1.21-.99-2.01-.99H9.46c-.8 0-1.54.37-2.01.99L5 10.5l-1-1.5C3.53 8.37 2.79 8 2 8H.5L3 15.5V22h2v-6h2v6h2v-6h2v6h2v-6h2v6h2z"/>
              </svg>
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ stats.users }}</div>
              <div class="stat-label">Utilisateurs</div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon projects">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
              </svg>
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ stats.projects }}</div>
              <div class="stat-label">Projets</div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon tasks">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
              </svg>
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ stats.tasks }}</div>
              <div class="stat-label">Tâches</div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon skills">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
              </svg>
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ stats.skills }}</div>
              <div class="stat-label">Compétences</div>
            </div>
          </div>
        </div>

        <!-- Sections d'administration -->
        <div class="admin-sections">
          <!-- Gestion des utilisateurs et compétences -->
          <div class="admin-section">
            <div class="section-header">
              <h2>Gestion des Utilisateurs</h2>
              <button @click="refreshUsers" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
                </svg>
                Actualiser
              </button>
            </div>
            <div class="users-grid">
              <div v-for="user in users" :key="user.id" class="user-card">
                <div class="user-header">
                  <div class="user-avatar">{{ user.firstName[0] }}{{ user.lastName[0] }}</div>
                  <div class="user-info">
                    <div class="user-name">{{ user.firstName }} {{ user.lastName }}</div>
                    <div class="user-email">{{ user.email }}</div>
                  </div>
                </div>
                <div class="user-roles">
                  <span v-for="role in getUserRoles(user)" :key="role" class="role-badge">{{ role }}</span>
                </div>
                <div class="user-skills">
                  <div class="skills-header">Compétences:</div>
                  <div class="skills-list">
                    <span v-for="skill in getUserSkills(user.id)" :key="skill.id" class="skill-badge">
                      {{ skill.skill.name }} ({{ skill.level }})
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Assignation automatique -->
          <div class="admin-section">
            <div class="section-header">
              <h2>Assignation Automatique</h2>
              <div class="section-actions">
                <button @click="autoAssignAllTasks" class="btn btn-primary" :disabled="isAssigning">
                  <span v-if="isAssigning">Assignation...</span>
                  <span v-else>Assigner toutes les tâches</span>
                </button>
                <button @click="redistributeTasks" class="btn btn-warning">
                  Redistribuer les tâches
                </button>
              </div>
            </div>
            <div class="assignment-stats">
              <div class="assignment-stat">
                <div class="stat-number">{{ unassignedTasks.length }}</div>
                <div class="stat-label">Tâches non assignées</div>
              </div>
              <div class="assignment-stat">
                <div class="stat-number">{{ overloadedUsers.length }}</div>
                <div class="stat-label">Utilisateurs surchargés</div>
              </div>
            </div>
          </div>

          <!-- Notifications -->
          <div class="admin-section">
            <div class="section-header">
              <h2>Notifications</h2>
              <button @click="refreshNotifications" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
                </svg>
                Actualiser
              </button>
            </div>
            <div class="notifications-list">
              <div v-for="notification in notifications" :key="notification.id" class="notification-item">
                <div class="notification-icon" :style="{ backgroundColor: getNotificationColor(notification.type) }">
                  {{ getNotificationIcon(notification.type) }}
                </div>
                <div class="notification-content">
                  <div class="notification-title">{{ notification.title }}</div>
                  <div class="notification-message">{{ notification.message }}</div>
                  <div class="notification-meta">
                    <span class="notification-user">{{ notification.user.firstName }} {{ notification.user.lastName }}</span>
                    <span class="notification-time">{{ formatDate(notification.createdAt) }}</span>
                  </div>
                </div>
                <div class="notification-actions">
                  <button @click="markAsRead(notification.id)" class="btn btn-sm" v-if="!notification.isRead">
                    Marquer comme lu
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { authService, type User } from '@/services/authService'
// import { fixturesService } from '@/services/fixturesService' // Supprimé - plus de données de test
import { userSkillService, type UserSkill } from '@/services/userSkillService'
import { workloadService, type Workload } from '@/services/workloadService'
import { notificationService, type Notification } from '@/services/notificationService'
import { taskAssignmentService } from '@/services/taskAssignmentService'
import AvatarSelector from '@/components/AvatarSelector.vue'

const router = useRouter()
const user = ref<User | null>(null)

// État des données
const dataInitialized = ref(false)
const isInitializing = ref(false)
const isAssigning = ref(false)

// Données
const users = ref<User[]>([])
const userSkills = ref<UserSkill[]>([])
const workloads = ref<Workload[]>([])
const notifications = ref<Notification[]>([])
const unassignedTasks = ref<any[]>([])
const overloadedUsers = ref<Workload[]>([])

// Statistiques
const stats = ref({
  users: 0,
  projects: 0,
  tasks: 0,
  skills: 0
})

// Rôles de l'utilisateur connecté
const userRoles = computed(() => {
  if (!user.value) return []
  return user.value.roles?.filter(role => role !== 'ROLE_USER') || []
})

// Fonctions utilitaires
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getUserRoles = (user: User) => {
  return user.roles?.filter(role => role !== 'ROLE_USER') || []
}

const getUserSkills = (userId: number) => {
  return userSkills.value.filter(us => us.user.id === userId)
}

const getNotificationIcon = (type: string) => {
  switch (type) {
    case 'info': return 'ℹ️'
    case 'warning': return '⚠️'
    case 'error': return '❌'
    case 'success': return '✅'
    case 'workload': return '📊'
    case 'alert': return '🚨'
    default: return '📢'
  }
}

const getNotificationColor = (type: string) => {
  switch (type) {
    case 'info': return '#3b82f6'
    case 'warning': return '#f97316'
    case 'error': return '#ef4444'
    case 'success': return '#22c55e'
    case 'workload': return '#8b5cf6'
    case 'alert': return '#dc2626'
    default: return '#6b7280'
  }
}

// Fonctions d'administration
const initializeData = async () => {
  isInitializing.value = true
  try {
    // Charger les données existantes au lieu de créer des données de test
    await loadAllData()
    
    // Marquer comme initialisé
    dataInitialized.value = true
    
    alert('Données chargées avec succès!')
  } catch (error) {
    console.error('Erreur lors du chargement des données:', error)
    alert('Erreur lors du chargement des données')
  } finally {
    isInitializing.value = false
  }
}

const loadAllData = async () => {
  try {
    // Charger les utilisateurs
    const usersResponse = await fetch('/api/users', {
      headers: authService.getAuthHeaders()
    })
    if (usersResponse.ok) {
      users.value = await usersResponse.json()
      stats.value.users = users.value.length
    }

    // Charger les compétences utilisateur
    userSkills.value = await userSkillService.getUserSkills()

    // Charger les charges de travail
    workloads.value = await workloadService.getWorkloads()
    overloadedUsers.value = await workloadService.getOverloadedUsers()

    // Charger les notifications
    notifications.value = await notificationService.getNotifications()

    // Charger les tâches non assignées
    unassignedTasks.value = await taskAssignmentService.getUnassignedTasks()

    // Charger les projets et tâches pour les statistiques
    const projectsResponse = await fetch('/api/projects', {
      headers: authService.getAuthHeaders()
    })
    if (projectsResponse.ok) {
      const projects = await projectsResponse.json()
      stats.value.projects = projects.length
    }

    const tasksResponse = await fetch('/api/tasks', {
      headers: authService.getAuthHeaders()
    })
    if (tasksResponse.ok) {
      const tasks = await tasksResponse.json()
      stats.value.tasks = tasks.length
    }

    const skillsResponse = await fetch('/api/skills', {
      headers: authService.getAuthHeaders()
    })
    if (skillsResponse.ok) {
      const skills = await skillsResponse.json()
      stats.value.skills = skills.length
    }

  } catch (error) {
    console.error('Erreur lors du chargement des données:', error)
  }
}

const refreshUsers = async () => {
  await loadAllData()
}

const refreshNotifications = async () => {
  try {
    notifications.value = await notificationService.getNotifications()
  } catch (error) {
    console.error('Erreur lors du rechargement des notifications:', error)
  }
}

const autoAssignAllTasks = async () => {
  isAssigning.value = true
  try {
    const result = await taskAssignmentService.autoAssignAllUnassignedTasks()
    console.log('Assignation automatique:', result)
    
    // Recharger les données
    await loadAllData()
    
    alert(`Assignation terminée: ${result.assigned_count} tâches assignées, ${result.failed_count} échecs`)
  } catch (error) {
    console.error('Erreur lors de l\'assignation automatique:', error)
    alert('Erreur lors de l\'assignation automatique')
  } finally {
    isAssigning.value = false
  }
}

const redistributeTasks = async () => {
  try {
    const result = await taskAssignmentService.redistributeTasks()
    console.log('Redistribution:', result)
    
    // Recharger les données
    await loadAllData()
    
    alert(`Redistribution terminée: ${result.redistributed?.length || 0} tâches redistribuées`)
  } catch (error) {
    console.error('Erreur lors de la redistribution:', error)
    alert('Erreur lors de la redistribution des tâches')
  }
}

const markAsRead = async (notificationId: number) => {
  try {
    await notificationService.markAsRead(notificationId)
    // Mettre à jour la notification localement
    const notification = notifications.value.find(n => n.id === notificationId)
    if (notification) {
      notification.isRead = true
    }
  } catch (error) {
    console.error('Erreur lors du marquage comme lu:', error)
  }
}

// Fonctions de navigation
const updateUserAvatar = (newAvatar: string) => {
  if (user.value) {
    user.value.avatar = newAvatar
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

  // Charger les données existantes
  try {
    await loadAllData()
    dataInitialized.value = true
  } catch (error) {
    console.error('Erreur lors du chargement des données:', error)
  }
})
</script>

<style scoped>
.admin-container {
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
}

.nav-section {
  padding: 1rem 0;
}

.nav-title {
  color: var(--deep-light);
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 0 0 0.5rem 0;
  padding: 0 1rem;
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
  align-items: center;
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

.account-roles {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
  justify-content: center;
}

.role-badge {
  background: var(--primary-color);
  color: var(--white);
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.7rem;
  font-weight: 500;
}

.account-logout-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: calc(100% - 2rem);
  padding: 0.75rem 1rem;
  margin: 0.5rem 1rem;
  background: var(--danger-color);
  color: var(--white);
  border: none;
  border-radius: 6px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.account-logout-btn:hover {
  background: #c82333;
  transform: translateY(-1px);
}

/* Contenu principal */
.main-content {
  flex: 1;
  margin-left: 280px;
  padding: 2rem;
}

.admin-header {
  margin-bottom: 2rem;
}

.admin-header h1 {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
}

.admin-header p {
  font-size: 1.1rem;
  color: var(--text-secondary);
  margin: 0;
}

/* Section d'initialisation */
.init-section {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 60vh;
}

.init-card {
  background: var(--white);
  border-radius: 12px;
  padding: 3rem;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
  text-align: center;
  max-width: 500px;
  width: 100%;
}

.init-icon {
  width: 80px;
  height: 80px;
  background: var(--primary-color);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--white);
  margin: 0 auto 1.5rem;
}

.init-content h3 {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0 0 1rem 0;
}

.init-content p {
  color: var(--text-secondary);
  margin: 0 0 2rem 0;
  line-height: 1.5;
}

/* Statistiques */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: var(--white);
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--white);
}

.stat-icon.users { background: #3b82f6; }
.stat-icon.projects { background: #10b981; }
.stat-icon.tasks { background: #f59e0b; }
.stat-icon.skills { background: #8b5cf6; }

.stat-content {
  flex: 1;
}

.stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1;
}

.stat-label {
  font-size: 0.9rem;
  color: var(--text-secondary);
  margin-top: 0.25rem;
}

/* Sections d'administration */
.admin-sections {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.admin-section {
  background: var(--white);
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.section-header {
  display: flex;
  justify-content: between;
  align-items: center;
  margin-bottom: 1.5rem;
  gap: 1rem;
}

.section-header h2 {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-primary);
  margin: 0;
  flex: 1;
}

.section-actions {
  display: flex;
  gap: 0.75rem;
}

/* Grille des utilisateurs */
.users-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.user-card {
  background: var(--background-light);
  border-radius: 8px;
  padding: 1.5rem;
  border: 1px solid var(--border-color);
}

.user-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.user-avatar {
  width: 48px;
  height: 48px;
  background: var(--primary-color);
  color: var(--white);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 1.1rem;
}

.user-info {
  flex: 1;
}

.user-name {
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.25rem;
}

.user-email {
  font-size: 0.9rem;
  color: var(--text-secondary);
}

.user-roles {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.user-skills {
  margin-top: 1rem;
}

.skills-header {
  font-size: 0.9rem;
  font-weight: 500;
  color: var(--text-secondary);
  margin-bottom: 0.5rem;
}

.skills-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.skill-badge {
  background: var(--primary-light);
  color: var(--primary-color);
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: 500;
}

/* Statistiques d'assignation */
.assignment-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.assignment-stat {
  text-align: center;
  padding: 1.5rem;
  background: var(--background-light);
  border-radius: 8px;
  border: 1px solid var(--border-color);
}

/* Liste des notifications */
.notifications-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.notification-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem;
  background: var(--background-light);
  border-radius: 8px;
  border: 1px solid var(--border-color);
}

.notification-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  flex-shrink: 0;
}

.notification-content {
  flex: 1;
}

.notification-title {
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.25rem;
}

.notification-message {
  color: var(--text-secondary);
  margin-bottom: 0.5rem;
  line-height: 1.4;
}

.notification-meta {
  display: flex;
  gap: 1rem;
  font-size: 0.8rem;
  color: var(--text-secondary);
}

.notification-actions {
  display: flex;
  align-items: center;
}

/* Boutons */
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

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: var(--primary-color);
  color: var(--white);
  box-shadow: 0 4px 15px rgba(65, 90, 119, 0.3);
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-dark);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(65, 90, 119, 0.4);
}

.btn-secondary {
  background: var(--text-secondary);
  color: var(--white);
}

.btn-secondary:hover {
  background: var(--text-primary);
  transform: translateY(-1px);
}

.btn-warning {
  background: #f59e0b;
  color: var(--white);
}

.btn-warning:hover {
  background: #d97706;
  transform: translateY(-1px);
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.8rem;
}

/* Responsive */
@media (max-width: 768px) {
  .sidebar {
    width: 100%;
    position: relative;
    height: auto;
  }
  
  .main-content {
    margin-left: 0;
    padding: 1rem;
  }
  
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .users-grid {
    grid-template-columns: 1fr;
  }
  
  .section-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .section-actions {
    width: 100%;
    justify-content: flex-start;
  }
}
</style>
