<template>
  <div class="dashboard-container">
    <header class="dashboard-header">
      <div class="header-content">
        <h1>Tableau de bord TaskForce</h1>
        <div class="header-actions">
          <div class="profile-menu" :class="{ 'active': showProfileMenu }">
            <button @click="toggleProfileMenu" class="profile-trigger">
              <div class="user-icon">
                <img v-if="user?.avatar" :src="user.avatar" :alt="user.firstName" />
                <div v-else class="user-icon-placeholder">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                  </svg>
        </div>
        </div>
            </button>
            
            <div v-if="showProfileMenu" class="profile-dropdown">
              <!-- Section COMPTE -->
              <div class="profile-section">
                <h3 class="section-title">COMPTE</h3>
                <div class="account-info">
                  <AvatarSelector 
                    :model-value="user?.avatar" 
                    @update:model-value="updateUserAvatar"
                    class="profile-avatar-selector"
                  />
                  <div class="account-details">
                    <div class="account-name">{{ user?.firstName }} {{ user?.lastName }}</div>
                    <div class="account-email">{{ user?.email }}</div>
          </div>
          </div>
        </div>
              
              <div class="profile-divider"></div>
              
              <!-- Informations du compte -->
              <div class="profile-section">
                <div class="account-details-info">
                  <div class="detail-item">
                    <strong>Entreprise:</strong> {{ user?.company || 'Non renseignée' }}
          </div>
                  <div class="detail-item">
                    <strong>Membre depuis:</strong> {{ formatDate(user?.createdAt) }}
        </div>
      </div>
    </div>

              <div class="profile-divider"></div>
              
              <!-- Déconnexion -->
              <div class="profile-section">
                <button @click="handleLogout" class="profile-action-btn logout-btn">
                  <span>Se déconnecter</span>
                </button>
        </div>
          </div>
              </div>
            </div>
          </div>
    </header>

    <main class="dashboard-main">
      <div class="welcome-section">
        <h2>Bienvenue, {{ user?.firstName }} !</h2>
        <p>Gérez vos projets et tâches depuis votre tableau de bord personnel.</p>
    </div>

                <div class="boards-section">
            <div class="boards-header">
              <h3>Mes projets</h3>
          </div>

        <div class="boards-grid">
          <div v-for="project in projects" :key="project.id" class="board-card" @click="openProject(project)">
            <div class="board-card-background" :style="getBoardBackground(project)">
              <div class="board-card-content">
                <h4 class="board-title">{{ project.name }}</h4>
                <p class="board-description">{{ project.description || 'Aucune description' }}</p>
                <div class="board-meta">
                  <span class="board-status" :class="`status-${project.status}`">
                    {{ getStatusLabel(project.status) }}
                  </span>
                  <span class="board-date">{{ formatDate(project.createdAt) }}</span>
              </div>
                </div>
                </div>
                </div>

          <!-- Carte pour créer un nouveau projet -->
          <div class="board-card create-card" @click="openCreateProjectModal">
            <div class="create-card-content">
              <div class="create-icon">➕</div>
              <h4>Créer un nouveau projet</h4>
              <p>Commencez un nouveau projet</p>
              </div>
            </div>
          </div>
        </div>

      <div class="quick-actions">
        <h3>Actions rapides</h3>
        <div class="actions-grid">
          <div class="action-card">
            <h4>Voir mes tâches</h4>
            <p>Consultez vos tâches en cours</p>
            <button @click="goToTasks" class="btn btn-secondary">Voir</button>
              </div>
              </div>
              </div>

      </main>

    <!-- Modal de création de projet -->
    <CreateProjectModal 
      :is-open="showCreateProjectModal" 
      @close="closeCreateProjectModal"
      @project-created="handleProjectCreated"
    />

  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { authService, type User } from '@/services/authService'
import { projectService, type Project } from '@/services/projectService'
import CreateProjectModal from '@/components/CreateProjectModal.vue'
import AvatarSelector from '@/components/AvatarSelector.vue'

const router = useRouter()
const user = ref<User | null>(null)
const projects = ref<Project[]>([])
const showCreateProjectModal = ref(false)
const showProfileMenu = ref(false)
const isLoading = ref(false)

onMounted(async () => {
  if (!authService.isAuthenticated()) {
    router.push('/login')
    return
  }

  user.value = authService.getCurrentUser()
  await loadProjects()
  
  // Fermer le menu profil quand on clique ailleurs
  document.addEventListener('click', (event) => {
    const target = event.target as HTMLElement
    if (!target.closest('.profile-menu')) {
      showProfileMenu.value = false
    }
  })
})

const handleLogout = () => {
  authService.logout()
  router.push('/')
}

const formatDate = (dateString?: string) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('fr-FR')
}

const openCreateProjectModal = () => {
  showCreateProjectModal.value = true
}

const closeCreateProjectModal = () => {
  showCreateProjectModal.value = false
}

const handleProjectCreated = async (project: any) => {
  console.log('Projet créé:', project)
  showCreateProjectModal.value = false
  await loadProjects() // Rafraîchir la liste des projets
}

const goToTasks = () => {
  router.push('/tasks')
}

const loadProjects = async () => {
  isLoading.value = true
  try {
    projects.value = await projectService.getProjects()
  } catch (error) {
    console.error('Erreur lors du chargement des projets:', error)
  } finally {
    isLoading.value = false
  }
}

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    planning: 'En planification',
    in_progress: 'En cours',
    completed: 'Terminé',
    on_hold: 'En attente'
  }
  return labels[status] || status
}

const getBoardBackground = (project: Project) => {
  // Pour l'instant, utilisons des couleurs par défaut
  // Plus tard, on pourra ajouter des images de fond
  const colors = [
    'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
    'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
    'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
    'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
    'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)'
  ]
  
  const colorIndex = project.id % colors.length
  return {
    background: colors[colorIndex]
  }
}

const openProject = (project: Project) => {
  router.push(`/project/${project.id}/tasks`)
}

// Méthodes pour le menu profil
const toggleProfileMenu = () => {
  showProfileMenu.value = !showProfileMenu.value
}

const getInitials = (firstName?: string, lastName?: string) => {
  if (!firstName || !lastName) return '?'
  return (firstName.charAt(0) + lastName.charAt(0)).toUpperCase()
}


const updateUserAvatar = (avatarUrl: string) => {
  if (user.value) {
    user.value.avatar = avatarUrl
    // TODO: Sauvegarder en base de données
    console.log('Avatar mis à jour:', avatarUrl)
  }
}
</script>

<style scoped>
:root {
  --primary-color: #0079bf;
  --primary-hover: #005a8b;
  --text-primary: #2c3e50;
  --text-secondary: #6c757d;
  --background-light: #f8f9fa;
  --white: #ffffff;
  --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  --trello-blue: #0079bf;
  --trello-green: #61bd4f;
  --trello-orange: #ff9f1a;
  --trello-red: #eb5a46;
  --trello-purple: #c377e0;
  --trello-pink: #ff78cb;
  --trello-lime: #51e898;
  --trello-sky: #00c2e0;
  --trello-gray: #838c91;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.dashboard-container {
  min-height: 100vh;
  background: var(--background-light);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.dashboard-header {
  background: var(--white);
  box-shadow: var(--shadow);
  padding: 1rem 0;
}

.header-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-content h1 {
  color: var(--text-primary);
  font-size: 1.8rem;
  font-weight: 700;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  text-decoration: none;
  display: inline-block;
  text-align: center;
}

.btn-primary {
  background: var(--primary-color);
  color: var(--white);
}

.btn-primary:hover {
  background: var(--primary-hover);
  transform: translateY(-2px);
}

.btn-secondary {
  background: var(--white);
  color: var(--text-primary);
  border: 2px solid var(--text-secondary);
}

.btn-secondary:hover {
  background: var(--text-secondary);
  color: var(--white);
  transform: translateY(-2px);
}

.btn-logout {
  background: #dc3545;
  color: var(--white);
}

.btn-logout:hover {
  background: #c82333;
  transform: translateY(-2px);
}

/* Styles pour le menu profil */
.profile-menu {
  position: relative;
}

.profile-trigger {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background: var(--white);
  border: 2px solid #e5e7eb;
  border-radius: 50%;
  cursor: pointer;
  transition: all 0.2s ease;
  padding: 0;
}

.profile-trigger:hover {
  border-color: var(--primary-color);
  box-shadow: 0 2px 8px rgba(0, 121, 191, 0.1);
  transform: scale(1.05);
}

.user-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--primary-color);
  color: white;
}

.user-icon img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.user-icon-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--primary-color);
  color: white;
}

.profile-dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 0.5rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  border: 1px solid #e5e7eb;
  min-width: 300px;
  z-index: 1000;
  overflow: hidden;
  padding: 1rem;
  opacity: 1;
  backdrop-filter: none;
}

.profile-section {
  padding: 0.5rem 0;
}

.section-title {
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 0 0 0.5rem 0;
  padding: 0 1rem;
}

.account-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 1rem;
  margin-bottom: 0.5rem;
}

.account-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  overflow: hidden;
  background: var(--primary-color);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 0.9rem;
  border: 2px solid #e5e7eb;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.account-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.account-avatar-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--primary-color);
  color: white;
  font-weight: 600;
  font-size: 0.9rem;
}

.account-avatar-placeholder svg {
  width: 24px;
  height: 24px;
  color: white;
}

.account-details {
  flex: 1;
}

.account-name {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.9rem;
  margin-bottom: 0.125rem;
}

.account-email {
  color: #6b7280;
  font-size: 0.8rem;
}

.account-details-info {
  padding: 0 1rem;
}

.detail-item {
  margin-bottom: 0.5rem;
  color: var(--text-primary);
  font-size: 0.9rem;
  padding: 0.25rem 0;
}

.detail-item:last-child {
  margin-bottom: 0;
}

.detail-item strong {
  color: var(--text-secondary);
  font-weight: 500;
}

.profile-action-btn {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 0.5rem 1rem;
  background: none;
  border: none;
  text-align: left;
  cursor: pointer;
  transition: background-color 0.2s ease;
  color: var(--text-primary);
  font-size: 0.9rem;
  border-radius: 4px;
  margin-bottom: 0.125rem;
}

.profile-action-btn:hover {
  background: #f3f4f6;
}

.profile-action-btn svg {
  color: #9ca3af;
}

.workspace-btn {
  color: var(--primary-color);
  font-weight: 500;
}

.workspace-btn svg {
  color: var(--primary-color);
}

.logout-btn {
  color: #dc2626;
}

.logout-btn:hover {
  background: #fef2f2;
}

.profile-divider {
  height: 1px;
  background: #e5e7eb;
  margin: 0.5rem 0;
}

.profile-avatar-selector {
  flex-shrink: 0;
}

.profile-avatar-selector .avatar-preview {
  width: 50px;
  height: 50px;
  border: 2px solid #e5e7eb;
}

.profile-avatar-selector .btn-avatar-select {
  font-size: 0.75rem;
  padding: 0.375rem 0.75rem;
  margin-top: 0.5rem;
}

/* Styles Trello pour les cartes de projets */
.boards-section {
  background: var(--white);
  padding: 2rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  box-shadow: var(--shadow);
}

.boards-header {
  margin-bottom: 2rem;
}

.boards-header h3 {
  color: var(--text-primary);
  font-size: 1.5rem;
  margin: 0;
}


.boards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
  width: 100%;
}

.board-card {
  height: 120px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  overflow: hidden;
  position: relative;
}

.board-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.board-card-background {
  width: 100%;
  height: 100%;
  position: relative;
  display: flex;
  align-items: flex-end;
}

.board-card-content {
  padding: 1rem;
  color: white;
  width: 100%;
  background: linear-gradient(transparent, rgba(0, 0, 0, 0.3));
}

.board-title {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.board-description {
  font-size: 0.85rem;
  margin: 0 0 0.5rem 0;
  opacity: 0.9;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.board-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.75rem;
}

.board-status {
  padding: 0.2rem 0.5rem;
  border-radius: 12px;
  font-weight: 500;
  text-shadow: none;
}

.status-planning {
  background: rgba(255, 255, 255, 0.2);
  color: white;
}

.status-in_progress {
  background: rgba(97, 189, 79, 0.8);
  color: white;
}

.status-completed {
  background: rgba(0, 121, 191, 0.8);
  color: white;
}

.status-on_hold {
  background: rgba(255, 159, 26, 0.8);
  color: white;
}

.board-date {
  opacity: 0.8;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

/* Carte de création */
.create-card {
  background: #f8f9fa;
  border: 2px dashed #dee2e6;
  display: flex;
  align-items: center;
  justify-content: center;
}

.create-card:hover {
  background: #e9ecef;
  border-color: var(--primary-color);
}

.create-card-content {
  text-align: center;
  color: var(--text-secondary);
}

.create-icon {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.create-card h4 {
  font-size: 1rem;
  margin: 0 0 0.25rem 0;
  color: var(--text-primary);
}

.create-card p {
  font-size: 0.85rem;
  margin: 0;
}


.dashboard-main {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.welcome-section {
  background: var(--white);
  padding: 2rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  box-shadow: var(--shadow);
  text-align: center;
}

.welcome-section h2 {
  color: var(--text-primary);
  font-size: 2rem;
  margin-bottom: 1rem;
}

.welcome-section p {
  color: var(--text-secondary);
  font-size: 1.1rem;
}

.quick-actions {
  background: var(--white);
  padding: 2rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  box-shadow: var(--shadow);
}

.quick-actions h3 {
  color: var(--text-primary);
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.action-card {
  padding: 1.5rem;
  border: 1px solid #e1e5e9;
  border-radius: 8px;
  text-align: center;
  transition: all 0.3s ease;
}

.action-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--shadow);
}

.action-card h4 {
  color: var(--text-primary);
  font-size: 1.2rem;
  margin-bottom: 0.5rem;
}

.action-card p {
  color: var(--text-secondary);
  margin-bottom: 1rem;
}

.user-info {
  background: var(--white);
  padding: 2rem;
  border-radius: 12px;
  box-shadow: var(--shadow);
}

.user-info h3 {
  color: var(--text-primary);
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.info-item {
  padding: 1rem;
  background: var(--background-light);
  border-radius: 8px;
  color: var(--text-primary);
}

/* Responsive Design */
@media (max-width: 1200px) {
  .boards-grid {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  }
}

@media (max-width: 992px) {
  .boards-grid {
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1rem;
  }
  
  .board-card {
    height: 110px;
  }
}

@media (max-width: 768px) {
  .header-content {
    padding: 0 1rem;
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }
  
  .header-content h1 {
    font-size: 1.5rem;
  }
  
  .header-actions {
    align-self: flex-end;
  }
  
  .dashboard-main {
    padding: 1rem;
  }
  
  .boards-section {
    padding: 1.5rem;
  }
  
  .boards-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .board-card {
    height: 100px;
  }

  .board-title {
    font-size: 1rem;
  }

  .board-description {
    font-size: 0.8rem;
  }

  .board-meta {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }

  .board-status {
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
  }

  .board-date {
    font-size: 0.7rem;
  }

  .profile-dropdown {
    min-width: 280px;
    right: -1rem;
    max-width: calc(100vw - 2rem);
  }
  
  .account-info {
    flex-direction: column;
    text-align: center;
    gap: 0.5rem;
  }
  
  .profile-avatar-selector {
    align-self: center;
  }
}

@media (max-width: 480px) {
  .header-content h1 {
    font-size: 1.25rem;
  }
  
  .boards-section {
    padding: 1rem;
  }
  
  .board-card {
    height: 90px;
  }
  
  .profile-dropdown {
    min-width: 260px;
    right: -0.5rem;
  }
}
</style>
