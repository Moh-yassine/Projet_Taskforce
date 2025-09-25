<template>
  <AuthGuard>
    <div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <h2>TaskForce</h2>
      </div>
      
      <nav class="sidebar-nav">
        <div class="nav-section">
          <h3 class="nav-title">Navigation</h3>
          <ul class="nav-list">
            <li class="nav-item active">
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
            <!-- Projets - Visible seulement pour le Responsable de Projet -->
            <li v-if="canManageProjects" class="nav-item">
              <a href="#" class="nav-link" @click.prevent="showProjects = true; showWelcome = false">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                </svg>
                <span>Projets</span>
              </a>
            </li>
            <!-- Admin - Visible pour le Responsable de Projet et le Manager -->
            <li v-if="canAccessAdmin" class="nav-item">
              <router-link to="/admin" class="nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 1H5C3.89 1 3 1.89 3 3V21C3 22.11 3.89 23 5 23H19C20.11 23 21 22.11 21 21V9M19 9H14V4H5V21H19V9Z"/>
                </svg>
                <span>Admin</span>
              </router-link>
            </li>
            
            <!-- Premium - Visible pour les utilisateurs avec abonnement premium -->
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
                <div class="account-member-since">Membre depuis: {{ formatDate(user?.createdAt) }}</div>
          </div>
        </div>

          
          <!-- Bouton de déconnexion -->
          <button @click="handleLogout" class="account-logout-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
            </svg>
            <span>Se déconnecter</span>
          </button>
        </div>
      </nav>
    </aside>

    <!-- Main content area -->
    <div class="main-content">
      <main class="dashboard-main">
        <!-- Section de bienvenue -->
        <div v-if="showWelcome" class="welcome-section">
          <h2>Bienvenue, {{ user?.firstName }} !</h2>
          
          <!-- Message personnalisé selon le rôle -->
          <div v-if="isProjectManager" class="role-message">
            <p>En tant que <strong>Responsable de Projet</strong>, vous avez accès à toutes les fonctionnalités de TaskForce.</p>
            <p>Gérez vos projets, assignez des tâches et supervisez l'équipe depuis votre tableau de bord.</p>
          </div>
          <div v-else-if="user?.permissions?.primaryRole === 'ROLE_MANAGER'" class="role-message">
            <p>En tant que <strong>Manager</strong>, vous pouvez superviser les tâches et consulter les rapports.</p>
            <p>Accédez aux fonctionnalités d'administration pour gérer les notifications et suivre les performances.</p>
          </div>
          <div v-else class="role-message">
            <p>En tant que <strong>Collaborateur</strong>, consultez vos tâches assignées et suivez votre progression.</p>
            <p>Restez informé de vos missions et deadlines depuis votre tableau de bord personnel.</p>
          </div>
          
          <!-- Actions rapides -->
          <div class="quick-actions-section">
            <h3>Actions rapides</h3>
            <div class="quick-actions-grid">
              <!-- Actions selon le rôle -->
              <div v-if="canManageProjects" class="quick-action-card" @click="showProjects = true; showWelcome = false">
                <div class="quick-action-content">
                  <h4>Gérer les projets</h4>
                  <p>Créez et gérez vos projets</p>
                  <button class="quick-action-btn">Gérer</button>
                </div>
              </div>
              
              <div v-if="canAccessAdmin" class="quick-action-card" @click="router.push('/admin')">
                <div class="quick-action-content">
                  <h4>Administration</h4>
                  <p>Accédez aux outils d'administration</p>
                  <button class="quick-action-btn">Admin</button>
                </div>
              </div>
              
              <!-- Action pour les collaborateurs -->
              <div v-if="isCollaborator" class="quick-action-card" @click="router.push('/my-tasks')">
                <div class="quick-action-content">
                  <h4>Mes tâches</h4>
                  <p>Consultez et gérez vos tâches assignées</p>
                  <button class="quick-action-btn" @click="router.push('/my-tasks')">Voir mes tâches</button>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- Section Premium -->
        <div v-if="showWelcome && !hasActiveSubscription" class="premium-section">
          <div class="premium-container">
            <div class="premium-card">
              <div class="premium-background">
                <div class="premium-shine"></div>
              </div>
              <div class="premium-content">
                <div class="premium-header">
                  <div class="premium-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                  </div>
                  <div class="premium-badge">PREMIUM</div>
                </div>
                <h3 class="premium-title">Débloquez tout le potentiel de TaskForce</h3>
                <p class="premium-description">Accédez à des fonctionnalités avancées, rapports détaillés et support prioritaire pour optimiser votre gestion de projets.</p>
                <button class="premium-btn" @click="tryPremiumTaskforce">
                  <span class="premium-btn-text">
                    {{ hasActiveSubscription ? 'ACCÉDER AUX FONCTIONNALITÉS PREMIUM' : 'ESSAYER PREMIUM TASKFORCE' }}
                  </span>
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>
                  </svg>
                </button>
                
              </div>
            </div>
          </div>
        </div>

        <!-- Section des projets -->
        <div v-if="showProjects" class="projects-section">
          <div class="projects-header">
            <h2>Mes projets</h2>
            <button @click="showWelcome = true; showProjects = false" class="back-btn">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
              </svg>
              Retour
            </button>
          </div>

          <div class="boards-grid">
            <div v-for="project in projects" :key="project.id" class="board-card" @click="openProject(project)">
              <div class="board-card-background" :style="getBoardBackground(project)">
                <div class="board-card-content">
                  <div class="board-header">
                    <h4 class="board-title">{{ project.name }}</h4>
                    <button 
                      @click.stop="confirmDeleteProject(project)" 
                      class="delete-project-btn"
                      title="Supprimer le projet"
                    >
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3,6 5,6 21,6"></polyline>
                        <path d="M19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2"></path>
                        <line x1="10" y1="11" x2="10" y2="17"></line>
                        <line x1="14" y1="11" x2="14" y2="17"></line>
                      </svg>
                    </button>
              </div>
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


      </main>
              </div>
              </div>

  <!-- Modal de création de projet -->
    <CreateProjectModal 
      :is-open="showCreateProjectModal" 
      @close="closeCreateProjectModal"
      @project-created="handleProjectCreated"
    />

    <!-- Modal Premium -->
    <PremiumModal 
      :is-open="showPremiumModal" 
      @close="closePremiumModal"
    />

    <!-- Modal de confirmation de suppression -->
    <div v-if="showDeleteModal" class="delete-modal-overlay" @click="closeDeleteModal">
      <div class="delete-modal" @click.stop>
        <div class="delete-modal-header">
          <h3>Supprimer le projet</h3>
          <button @click="closeDeleteModal" class="close-btn">×</button>
              </div>
        <div class="delete-modal-content">
          <div class="warning-icon">⚠️</div>
          <p>Êtes-vous sûr de vouloir supprimer le projet <strong>"{{ projectToDelete?.name }}"</strong> ?</p>
          <p class="warning-text">Cette action est irréversible et supprimera également toutes les tâches associées.</p>
            </div>
        <div class="delete-modal-actions">
          <button @click="closeDeleteModal" class="btn btn-secondary">Annuler</button>
          <button @click="deleteProject" class="btn btn-danger" :disabled="isDeleting">
            <span v-if="isDeleting">Suppression...</span>
            <span v-else>Supprimer</span>
          </button>
          </div>
    </div>
    </div>
  </AuthGuard>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { authService, type User } from '@/services/authService'
import { projectService, type Project } from '@/services/projectService'
import { paymentService } from '@/services/paymentService'
import CreateProjectModal from '@/components/CreateProjectModal.vue'
import AvatarSelector from '@/components/AvatarSelector.vue'
import PremiumModal from '@/components/PremiumModal.vue'
import AuthGuard from '@/components/AuthGuard.vue'

const router = useRouter()
const user = ref<User | null>(null)
const projects = ref<Project[]>([])
const showCreateProjectModal = ref(false)
const showPremiumModal = ref(false)
const hasActiveSubscription = ref(false)


const showDeleteModal = ref(false)
const projectToDelete = ref<Project | null>(null)
const isDeleting = ref(false)
const isLoading = ref(false)

// Variables pour l'affichage conditionnel
const showWelcome = ref(true)
const showProjects = ref(false)

// Computed properties pour les permissions
const canManageProjects = computed(() => {
  return user.value?.permissions?.canManageProjects || false
})

const canAccessAdmin = computed(() => {
  return user.value?.permissions?.canAccessAdmin || false
})


const isProjectManager = computed(() => {
  return user.value?.permissions?.primaryRole === 'ROLE_PROJECT_MANAGER'
})

const isCollaborator = computed(() => {
  return user.value?.permissions?.primaryRole === 'ROLE_COLLABORATOR'
})

onMounted(async () => {
  // Nettoyer automatiquement les données corrompues au démarrage
  authService.cleanupCorruptedData()
  
  // Vérifier si l'utilisateur a un token JWT valide
  if (!authService.isAuthenticated() || !authService.getAuthToken()) {
    console.log('Utilisateur non authentifié ou token manquant, redirection vers login')
    router.push('/login')
    return
  }

  user.value = authService.getCurrentUser()
  
  // Vérifier si l'utilisateur revient d'un paiement réussi
  const urlParams = new URLSearchParams(window.location.search)
  if (urlParams.get('premium') === 'success') {
    console.log('🎉 Retour après paiement réussi, vérification du statut premium...')
    
    // Attendre que le webhook Stripe traite le paiement
    await waitForPremiumActivation()
    
    // Nettoyer l'URL
    window.history.replaceState({}, document.title, window.location.pathname)
  }
  
  try {
    await loadProjects()
    await checkSubscriptionStatus()
  } catch (error) {
    console.error('Erreur lors du chargement des données:', error)
    // Si erreur d'authentification, rediriger vers login
    if (error instanceof Error && (error.message.includes('401') || error.message.includes('Non authentifié'))) {
      console.log('Token expiré, redirection vers login')
      authService.logout()
      router.push('/login')
    }
  }
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


const checkSubscriptionStatus = async () => {
  try {
    // AuthGuard s'occupe de la vérification d'authentification
    const config = await paymentService.getConfig()
    hasActiveSubscription.value = config.hasActiveSubscription
    console.log('Statut abonnement vérifié:', hasActiveSubscription.value)
  } catch (error) {
    console.error('Erreur lors de la vérification du statut d\'abonnement:', error)
    
    // Si erreur 401, rediriger vers login
    if (error instanceof Error && (error.message.includes('401') || error.message.includes('Non authentifié'))) {
      console.log('Token expiré ou invalide, redirection vers login')
      authService.logout()
      router.push('/login')
      return
    }
    
    hasActiveSubscription.value = false
  }
}

const waitForPremiumActivation = async () => {
  console.log('⏳ Attente de l\'activation Premium via webhook Stripe...')
  
  // Polling pour vérifier le statut Premium
  let attempts = 0
  const maxAttempts = 30 // 30 tentatives = 30 secondes max
  
  while (attempts < maxAttempts) {
    try {
      await checkSubscriptionStatus()
      
      if (hasActiveSubscription.value) {
        console.log('🎉 Abonnement Premium activé avec succès !')
        // Afficher une notification de succès
        showSuccessNotification('Abonnement Premium activé ! Vous avez maintenant accès au mode observateur.')
        return
      }
      
      // Attendre 1 seconde avant la prochaine vérification
      await new Promise(resolve => setTimeout(resolve, 1000))
      attempts++
      
      if (attempts % 5 === 0) {
        console.log(`⏳ Vérification du statut Premium... (${attempts}/${maxAttempts})`)
      }
    } catch (error) {
      console.error('Erreur lors de la vérification du statut Premium:', error)
      attempts++
    }
  }
  
  // Si on arrive ici, le Premium n'a pas été activé dans les temps
  console.warn('⚠️ L\'activation Premium prend plus de temps que prévu. Le webhook Stripe peut prendre quelques minutes.')
  showWarningNotification('L\'activation Premium prend plus de temps que prévu. Le webhook Stripe peut prendre quelques minutes. Veuillez rafraîchir la page si nécessaire.')
}

const showSuccessNotification = (message: string) => {
  // Créer une notification de succès temporaire
  const notification = document.createElement('div')
  notification.className = 'premium-success-notification'
  notification.innerHTML = `
    <div class="notification-content">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="color: #10b981;">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
      </svg>
      <span>${message}</span>
    </div>
  `
  
  // Styles pour la notification
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: #10b981;
    color: white;
    padding: 16px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    z-index: 10000;
    animation: slideInRight 0.3s ease;
  `
  
  document.body.appendChild(notification)
  
  // Supprimer la notification après 5 secondes
  setTimeout(() => {
    notification.style.animation = 'slideOutRight 0.3s ease'
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification)
      }
    }, 300)
  }, 5000)
}

const showWarningNotification = (message: string) => {
  // Créer une notification d'avertissement temporaire
  const notification = document.createElement('div')
  notification.className = 'premium-warning-notification'
  notification.innerHTML = `
    <div class="notification-content">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="color: #f59e0b;">
        <path d="M12 2l9 20H3l9-20zm0 3.8L5.2 20h13.6L12 5.8zM11 16h2v2h-2v-2zm0-6h2v4h-2v-4z"/>
      </svg>
      <span>${message}</span>
    </div>
  `
  
  // Styles pour la notification
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: #f59e0b;
    color: white;
    padding: 16px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
    z-index: 10000;
    animation: slideInRight 0.3s ease;
  `
  
  document.body.appendChild(notification)
  
  // Supprimer la notification après 8 secondes
  setTimeout(() => {
    notification.style.animation = 'slideOutRight 0.3s ease'
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification)
      }
    }, 300)
  }, 8000)
}

const tryPremiumTaskforce = () => {
  // AuthGuard s'occupe de la vérification d'authentification
  showPremiumModal.value = true
}


const closePremiumModal = () => {
  showPremiumModal.value = false
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

// Méthodes pour la suppression de projet
const confirmDeleteProject = (project: Project) => {
  projectToDelete.value = project
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  projectToDelete.value = null
  isDeleting.value = false
}

const deleteProject = async () => {
  if (!projectToDelete.value) return
  
  isDeleting.value = true
  try {
    await projectService.deleteProject(projectToDelete.value.id)
    
    // Supprimer le projet de la liste locale
    projects.value = projects.value.filter(p => p.id !== projectToDelete.value!.id)
    
    // Fermer la modal
    closeDeleteModal()
    
    // Afficher un message de succès (optionnel)
    console.log('Projet supprimé avec succès')
  } catch (error) {
    console.error('Erreur lors de la suppression:', error)
    alert('Erreur lors de la suppression du projet. Veuillez réessayer.')
  } finally {
    isDeleting.value = false
  }
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
  /* Palette Deep Sea */
  --deep-dark: #0D1B2A;
  --deep-navy: #1B263B;
  --deep-blue: #415A77;
  --deep-light: #778DA9;
  --deep-pale: #E0E1DD;
  
  /* Couleurs principales */
  --primary-color: var(--deep-blue);
  --primary-hover: var(--deep-navy);
  --secondary-color: var(--deep-light);
  --background-light: var(--deep-pale);
  --white: #ffffff;
  --text-primary: var(--deep-dark);
  --text-secondary: var(--deep-blue);
  
  /* Ombres */
  --shadow: 0 4px 6px rgba(13, 27, 42, 0.1);
  --shadow-hover: 0 8px 25px rgba(13, 27, 42, 0.15);
  
  /* Couleurs des projets */
  --trello-blue: var(--deep-blue);
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
  display: flex;
  padding-top: 0; /* Pas de padding en haut car le header est fixe */
}

/* Header supprimé - plus nécessaire */

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


/* Styles pour le lien Premium dans la navigation */
.premium-nav-link {
  position: relative;
}

.premium-nav-badge {
  position: absolute;
  top: -5px;
  right: -5px;
  background: linear-gradient(45deg, #ffd700, #ffed4e);
  color: #333;
  font-size: 0.6rem;
  font-weight: 800;
  padding: 0.2rem 0.4rem;
  border-radius: 8px;
  letter-spacing: 0.5px;
  box-shadow: 0 2px 8px rgba(255, 215, 0, 0.4);
  animation: premiumPulse 2s infinite;
}


/* Main content area */
.main-content {
  flex: 1;
  margin-left: 280px;
  min-height: 100vh;
}

/* Plus d'espacement nécessaire - header supprimé */

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
  color: var(--primary-color);
  font-size: 2rem;
  font-weight: 800;
  letter-spacing: -0.5px;
  text-transform: uppercase;
}

.btn {
  padding: 1rem 2rem;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  text-decoration: none;
  display: inline-block;
  text-align: center;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  position: relative;
  overflow: hidden;
}

.btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s;
}

.btn:hover::before {
  left: 100%;
}

.btn-primary {
  background: var(--primary-color);
  color: var(--white);
  box-shadow: 0 4px 15px rgba(65, 90, 119, 0.3);
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-hover);
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(65, 90, 119, 0.4);
}

.btn-primary.active {
  background: var(--primary-hover);
}

.btn-secondary {
  background: var(--white);
  color: var(--primary-color);
  border: 3px solid var(--primary-color);
  box-shadow: 0 4px 15px rgba(65, 90, 119, 0.1);
}

.btn-secondary:hover {
  background: var(--primary-color);
  color: var(--white);
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(65, 90, 119, 0.3);
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
  min-width: 350px;
  max-width: 450px;
  width: max-content;
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
  gap: 1rem;
  padding: 1rem;
  margin-bottom: 0.5rem;
  background: var(--deep-dark);
  border-radius: 8px;
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
  min-width: 0; /* Permet au texte de se tronquer si nécessaire */
}

.account-name {
  font-weight: 700;
  color: var(--white);
  font-size: 1.1rem;
  margin-bottom: 0.25rem;
  line-height: 1.2;
  word-wrap: break-word;
}

.account-email {
  color: var(--deep-light);
  font-size: 0.9rem;
  word-wrap: break-word;
  overflow-wrap: break-word;
}

.account-details-info {
  padding: 1rem;
  background: var(--background-light);
  border-radius: 8px;
  margin: 0.5rem 0;
}

.detail-item {
  margin-bottom: 0.75rem;
  color: var(--text-primary);
  font-size: 0.95rem;
  padding: 0.5rem 0;
  line-height: 1.4;
  word-wrap: break-word;
}

.detail-item:last-child {
  margin-bottom: 0;
}

.detail-item strong {
  color: var(--primary-color);
  font-weight: 600;
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
  min-height: 120px;
  max-height: 200px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  overflow: hidden;
  position: relative;
  display: flex;
  flex-direction: column;
}

.board-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.board-card-background {
  width: 100%;
  flex: 1;
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.board-card-content {
  padding: 1rem;
  color: white;
  width: 100%;
  background: linear-gradient(transparent, rgba(0, 0, 0, 0.3));
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
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
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.4;
  max-height: 2.8em; /* 2 lignes * 1.4em */
  flex: 1;
}

.board-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.75rem;
  flex-shrink: 0;
  margin-top: auto;
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

/* Styles pour les sections conditionnelles */
.projects-section {
  width: 100%;
}

.projects-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 2rem;
}

.projects-header h2 {
  color: var(--text-primary);
  font-size: 2rem;
  font-weight: 700;
  margin: 0;
}

.back-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: var(--secondary-color);
  color: var(--white);
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.back-btn:hover {
  background: var(--deep-blue);
  transform: translateY(-1px);
}

.back-btn svg {
  flex-shrink: 0;
}

.welcome-section {
  background: var(--white);
  padding: 2rem;
  border-radius: 12px;
  margin-bottom: 2.5rem;
  box-shadow: var(--shadow);
  text-align: center;
  max-width: 1200px;
  margin-left: auto;
  margin-right: auto;
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
    min-height: 110px;
    max-height: 180px;
  }
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
    min-height: 100px;
    max-height: 160px;
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
}

/* Styles pour les actions rapides */
.quick-actions-section {
  margin-bottom: 3rem;
}

.quick-actions-section h3 {
  color: #1B263B;
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  text-align: center;
}

.quick-actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  max-width: 1200px;
  margin: 0 auto;
}

.quick-action-card {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 4px 20px rgba(27, 38, 59, 0.1);
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
  text-align: center;
  border: 2px solid #E0E1DD;
}

.quick-action-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 30px rgba(27, 38, 59, 0.2);
  border-color: #415A77;
}

.quick-action-content h4 {
  font-size: 1.3rem;
  font-weight: 600;
  color: #1B263B;
  margin: 0 0 0.5rem 0;
}

.quick-action-content p {
  color: #415A77;
  margin: 0 0 1.5rem 0;
  line-height: 1.5;
}

.quick-action-btn {
  background: #415A77;
  color: white;
  border: none;
  padding: 0.75rem 2rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
  font-size: 1rem;
}

.quick-action-btn:hover {
  background: #1B263B;
}


/* Styles pour la section Premium */
.premium-section {
  margin-top: 2rem;
  padding: 0 2rem;
  max-width: 1200px;
  margin-left: auto;
  margin-right: auto;
}

.premium-card {
  position: relative;
  background: linear-gradient(135deg, #1B263B 0%, #415A77 100%);
  border-radius: 20px;
  padding: 0;
  box-shadow: 0 12px 40px rgba(27, 38, 59, 0.3);
  cursor: pointer;
  transition: all 0.4s ease;
  overflow: hidden;
  border: 3px solid transparent;
  background-clip: padding-box;
}

.premium-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, #ffd700, #ffed4e, #ffd700);
  border-radius: 20px;
  padding: 3px;
  mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  mask-composite: exclude;
  -webkit-mask-composite: xor;
  z-index: -1;
}

.premium-card:hover {
  transform: translateY(-8px) scale(1.02);
  box-shadow: 0 20px 60px rgba(27, 38, 59, 0.4);
}

.premium-background {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, #1B263B 0%, #415A77 100%);
  border-radius: 17px;
}

.premium-shine {
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.15), transparent);
  transform: rotate(45deg);
  animation: premiumShine 4s infinite;
}

@keyframes premiumShine {
  0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
  50% { transform: translateX(100%) translateY(100%) rotate(45deg); }
  100% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
}

.premium-content {
  position: relative;
  z-index: 2;
  padding: 3rem;
  color: white;
  text-align: center;
}

.premium-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.premium-icon {
  width: 50px;
  height: 50px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #ffd700;
  backdrop-filter: blur(15px);
  box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
}

.premium-badge {
  background: linear-gradient(45deg, #ffd700, #ffed4e);
  color: #333;
  padding: 0.5rem 1rem;
  border-radius: 25px;
  font-size: 0.8rem;
  font-weight: 800;
  letter-spacing: 1px;
  text-transform: uppercase;
  box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
  animation: premiumPulse 2s infinite;
}

@keyframes premiumPulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}

.premium-title {
  font-size: 2rem;
  font-weight: 800;
  margin: 0 0 1rem 0;
  background: linear-gradient(45deg, #fff, #f0f0f0);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  line-height: 1.2;
}

.premium-description {
  font-size: 1.1rem;
  margin: 0 0 2rem 0;
  opacity: 0.95;
  line-height: 1.6;
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}


.premium-btn {
  background: linear-gradient(45deg, #ffd700, #ffed4e);
  color: #333;
  border: none;
  border-radius: 8px;
  padding: 0.75rem 2rem;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.4s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  width: 100%;
  max-width: 400px;
  margin: 0 auto;
  box-shadow: 0 6px 25px rgba(255, 215, 0, 0.4);
  position: relative;
  overflow: hidden;
}

.premium-btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transition: left 0.5s ease;
}

.premium-btn:hover::before {
  left: 100%;
}

.premium-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 35px rgba(255, 215, 0, 0.5);
  background: linear-gradient(45deg, #ffed4e, #ffd700);
}

.premium-btn:active {
  transform: translateY(-1px);
}

.premium-btn svg {
  transition: transform 0.3s ease;
}

.premium-btn:hover svg {
  transform: translateX(3px);
}


@media (max-width: 768px) {
  .quick-actions-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .quick-action-card {
    padding: 1.5rem;
  }
  
  .welcome-section {
    padding: 1.5rem;
    margin-bottom: 2rem;
  }
  
  .premium-section {
    padding: 0 1rem;
    margin-top: 1.5rem;
    max-width: 100%;
  }
  
  .premium-content {
    padding: 2rem 1.5rem;
  }
  
  .premium-title {
    font-size: 1.5rem;
  }
  
  .premium-description {
    font-size: 1rem;
    margin-bottom: 1.5rem;
  }
  
  .premium-btn {
    padding: 0.75rem 2rem;
    font-size: 1rem;
  }

  .profile-dropdown {
    min-width: 320px;
    right: -1rem;
    max-width: calc(100vw - 2rem);
  }
  
  .account-info {
    flex-direction: row;
    text-align: left;
  gap: 1rem;
    padding: 1rem;
  }
  
  .profile-avatar-selector {
    flex-shrink: 0;
  }
  
  .account-details {
    min-width: 0;
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
    min-height: 90px;
    max-height: 140px;
  }
  
  .profile-dropdown {
    min-width: 280px;
    right: -0.5rem;
    max-width: calc(100vw - 1rem);
  }
  
  .account-info {
    flex-direction: column;
    text-align: center;
    gap: 0.75rem;
  }
  
  .account-name {
    font-size: 1rem;
  }
  
  .account-email {
    font-size: 0.85rem;
  }
}

/* Styles pour le bouton de suppression de projet */
.board-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
  flex-shrink: 0;
}

.delete-project-btn {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  border-radius: 6px;
  padding: 0.5rem;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(10px);
}

.delete-project-btn:hover {
  background: rgba(255, 255, 255, 0.3);
  color: white;
  transform: scale(1.05);
}

.delete-project-btn svg {
  width: 16px;
  height: 16px;
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
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover {
  background: #e5e7eb;
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

/* Responsive */
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

/* Styles pour les messages de rôle */
.role-message {
  background: linear-gradient(135deg, #1B263B 0%, #415A77 100%);
  color: white;
  padding: 20px;
  border-radius: 12px;
  margin-bottom: 24px;
  box-shadow: 0 4px 15px rgba(27, 38, 59, 0.3);
}

.role-message p {
  margin: 0 0 8px 0;
  line-height: 1.6;
  color: white;
}

.role-message p:last-child {
  margin-bottom: 0;
}

.role-message strong {
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Animations pour les notifications */
@keyframes slideInRight {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

@keyframes slideOutRight {
  from {
    transform: translateX(0);
    opacity: 1;
  }
  to {
    transform: translateX(100%);
    opacity: 0;
  }
}

.notification-content {
  display: flex;
  align-items: center;
  gap: 12px;
  font-weight: 500;
}
</style>
