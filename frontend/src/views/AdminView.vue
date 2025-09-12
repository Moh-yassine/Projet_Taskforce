<template>
  <div class="admin-container">
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
              <a href="#" class="nav-link" @click.prevent="goToProjects">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                </svg>
                <span>Projets</span>
              </a>
            </li>
            <!-- Admin - Visible pour le Responsable de Projet et le Manager -->
            <li v-if="canAccessAdmin" class="nav-item active">
              <router-link to="/admin" class="nav-link">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 1H5C3.89 1 3 1.89 3 3V21C3 22.11 3.89 23 5 23H19C20.11 23 21 22.11 21 21V9M19 9H14V4H5V21H19V9Z"/>
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
                <div class="account-member-since">Membre depuis: {{ formatDate(user?.createdAt) }}</div>
          </div>
        </div>
          
          <!-- Bouton de déconnexion -->
          <button @click="logout" class="account-logout-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
            </svg>
            <span>Se déconnecter</span>
          </button>
        </div>
      </nav>
    </aside>

    <!-- Contenu principal -->
    <main class="main-content">
      <div class="content-header">
        <h1>Administration TaskForce</h1>
        <p>Gérez les utilisateurs, projets, assignations automatiques et notifications</p>
      </div>

      <!-- Statistiques générales pour le Responsable de Projet -->
      <div v-if="isProjectManager" class="stats-section">
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ stats.users }}</div>
              <div class="stat-label">Utilisateurs</div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon">
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
            <div class="stat-icon">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M9 11H7v6h2v-6zm4 0h-2v6h2v-6zm4 0h-2v6h2v-6zm2-7H3v2h16V4zm0 4H3v2h16V8zm0 4H3v2h16v-2z"/>
              </svg>
            </div>
            <div class="stat-content">
              <div class="stat-number">{{ stats.tasks }}</div>
              <div class="stat-label">Tâches</div>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon">
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
      </div>

      <!-- Sections spécifiques au Manager -->
      <div v-if="isManager && managerDashboard" class="manager-sections">
        <!-- Statistiques du manager -->
        <div class="admin-section">
          <div class="section-header">
            <h2>Vue d'ensemble Manager</h2>
          </div>
          <div class="manager-stats-grid">
            <div class="stat-card">
              <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2zm4 18v-6h2.5l-2.54-7.63A1.5 1.5 0 0 0 18.54 8H17c-.8 0-1.54.37-2.01.99L14 10.5l-1-1.5c-.47-.62-1.21-.99-2.01-.99H9.46c-.8 0-1.54.37-2.01.99L5 10.5l-1-1.5C3.53 8.37 2.79 8 2 8H.5L3 15.5V22h2v-6h2v6h2v-6h2v6h2v-6h2v6h2z"/>
                </svg>
              </div>
              <div class="stat-content">
                <div class="stat-number">{{ managerDashboard.stats.totalCollaborators }}</div>
                <div class="stat-label">Collaborateurs</div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                </svg>
              </div>
              <div class="stat-content">
                <div class="stat-number">{{ managerDashboard.stats.totalTasks }}</div>
                <div class="stat-label">Tâches totales</div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
              </div>
              <div class="stat-content">
                <div class="stat-number">{{ managerDashboard.stats.completedTasks }}</div>
                <div class="stat-label">Tâches terminées</div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
                </svg>
              </div>
              <div class="stat-content">
                <div class="stat-number">{{ managerDashboard.stats.overdueTasks }}</div>
                <div class="stat-label">Tâches en retard</div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                </svg>
              </div>
              <div class="stat-content">
                <div class="stat-number">{{ managerDashboard.stats.unreadAlerts }}</div>
                <div class="stat-label">Alertes non lues</div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                </svg>
              </div>
              <div class="stat-content">
                <div class="stat-number">{{ managerDashboard.stats.completionRate }}%</div>
                <div class="stat-label">Taux de completion</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tâches en cours avec gestion des priorités -->
        <div class="admin-section">
          <div class="section-header">
            <h2>Suivi des tâches en cours</h2>
            <div class="pagination-info">
              {{ currentTasksPage }} sur {{ totalTasksPages }}
            </div>
          </div>
          <div class="tasks-grid">
            <div v-for="task in paginatedTasksInProgress" :key="task.id" class="task-card">
              <div class="task-header">
                <div class="task-title">{{ task.title }}</div>
                <span class="task-status" :class="'status-' + task.status">{{ getStatusLabel(task.status) }}</span>
              </div>
              <div class="task-details">
                <div class="task-detail-item">
                  <span class="detail-label">Assigné à:</span>
                  <span class="detail-value">{{ task.assignee?.firstName }} {{ task.assignee?.lastName }}</span>
                </div>
                <div class="task-detail-item">
                  <span class="detail-label">Projet:</span>
                  <span class="detail-value">{{ task.project?.name }}</span>
                </div>
                <div v-if="task.dueDate" class="task-detail-item">
                  <span class="detail-label">Échéance:</span>
                  <span class="detail-value">{{ formatDate(task.dueDate) }}</span>
                </div>
              </div>
              <div class="task-progress">
                <div class="progress-label">Progression</div>
                <div class="progress-container">
                  <div class="progress-bar">
                    <div class="progress-fill" :style="{ width: task.progress + '%' }"></div>
                  </div>
                  <span class="progress-text">{{ task.progress }}%</span>
                </div>
              </div>
              <div class="task-actions">
                <select 
                  :value="task.priority" 
                  @change="updateTaskPriority(task.id, ($event.target as HTMLSelectElement).value)"
                  class="priority-select"
                >
                  <option value="low">Faible</option>
                  <option value="medium">Moyenne</option>
                  <option value="high">Élevée</option>
                  <option value="urgent">Urgente</option>
                </select>
              </div>
            </div>
          </div>
          <div class="pagination-controls">
            <button 
              @click="currentTasksPage = Math.max(1, currentTasksPage - 1)" 
              :disabled="currentTasksPage === 1"
              class="pagination-btn"
            >
              Précédent
            </button>
            <span class="pagination-info">{{ currentTasksPage }} / {{ totalTasksPages }}</span>
            <button 
              @click="currentTasksPage = Math.min(totalTasksPages, currentTasksPage + 1)" 
              :disabled="currentTasksPage === totalTasksPages"
              class="pagination-btn"
            >
              Suivant
            </button>
          </div>
        </div>

        <!-- Alertes récentes - Visible seulement pour les Responsables de Projet -->
        <div v-if="isProjectManager" class="admin-section">
          <div class="section-header">
            <h2>Alertes récentes</h2>
            <div class="pagination-info">
              {{ currentAlertsPage }} sur {{ totalAlertsPages }}
            </div>
          </div>
          <div class="alerts-grid">
            <div v-for="alert in paginatedRecentAlerts" :key="alert.id" class="alert-card" :class="{ 'unread': !alert.isRead }">
              <div class="alert-header">
                <div class="alert-icon">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                  </svg>
                </div>
                <div class="alert-title">{{ alert.title }}</div>
                <div v-if="!alert.isRead" class="unread-badge">Non lu</div>
              </div>
              <div class="alert-content">
                <div class="alert-message">{{ alert.message }}</div>
                <div class="alert-date">{{ formatDateTime(alert.createdAt) }}</div>
              </div>
            </div>
          </div>
          <div class="pagination-controls">
            <button 
              @click="currentAlertsPage = Math.max(1, currentAlertsPage - 1)" 
              :disabled="currentAlertsPage === 1"
              class="pagination-btn"
            >
              Précédent
            </button>
            <span class="pagination-info">{{ currentAlertsPage }} / {{ totalAlertsPages }}</span>
            <button 
              @click="currentAlertsPage = Math.min(totalAlertsPages, currentAlertsPage + 1)" 
              :disabled="currentAlertsPage === totalAlertsPages"
              class="pagination-btn"
            >
              Suivant
            </button>
          </div>
        </div>

        <!-- Rapport de performance -->
        <div class="admin-section">
          <div class="section-header">
            <h2>Performance des collaborateurs</h2>
            <div class="pagination-info">
              {{ currentPerformancePage }} sur {{ totalPerformancePages }}
            </div>
          </div>
          <div class="performance-grid">
            <div v-for="perf in paginatedPerformanceReport" :key="perf.collaborator.id" class="performance-card">
              <div class="collaborator-header">
                <div class="collaborator-avatar">
                  <div class="avatar-placeholder">{{ perf.collaborator.firstName.charAt(0) }}{{ perf.collaborator.lastName.charAt(0) }}</div>
                </div>
                <div class="collaborator-info">
                  <div class="collaborator-name">{{ perf.collaborator.firstName }} {{ perf.collaborator.lastName }}</div>
                  <div class="collaborator-email">{{ perf.collaborator.email }}</div>
                </div>
              </div>
              <div class="performance-metrics">
                <div class="metric-row">
                  <div class="metric">
                    <div class="metric-label">Tâches terminées</div>
                    <div class="metric-value">{{ perf.completedTasks }}/{{ perf.totalTasks }}</div>
                  </div>
                  <div class="metric">
                    <div class="metric-label">Taux de completion</div>
                    <div class="metric-value">{{ perf.completionRate }}%</div>
                  </div>
                </div>
                <div class="metric-row">
                  <div class="metric">
                    <div class="metric-label">Efficacité</div>
                    <div class="metric-value" :style="{ color: getEfficiencyColor(perf.efficiency) }">{{ perf.efficiency }}%</div>
                  </div>
                  <div class="metric">
                    <div class="metric-label">Tâches en retard</div>
                    <div class="metric-value" :class="{ 'overdue': perf.overdueTasks > 0 }">{{ perf.overdueTasks }}</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="pagination-controls">
            <button 
              @click="currentPerformancePage = Math.max(1, currentPerformancePage - 1)" 
              :disabled="currentPerformancePage === 1"
              class="pagination-btn"
            >
              Précédent
            </button>
            <span class="pagination-info">{{ currentPerformancePage }} / {{ totalPerformancePages }}</span>
            <button 
              @click="currentPerformancePage = Math.min(totalPerformancePages, currentPerformancePage + 1)" 
              :disabled="currentPerformancePage === totalPerformancePages"
              class="pagination-btn"
            >
              Suivant
            </button>
          </div>
        </div>
      </div>

      <!-- Gestion des utilisateurs - Visible seulement pour les Responsables de Projet -->
      <div v-if="isProjectManager" class="admin-section">
        <div class="section-header">
          <h2>Gestion des Utilisateurs</h2>
          <button @click="refreshUsers" class="refresh-btn" :disabled="isLoading">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
            </svg>
            ACTUALISER
          </button>
        </div>
        <div class="users-list">
          <div v-for="user in paginatedUsers" :key="user.id" class="user-item">
            <div class="user-avatar">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
              </svg>
            </div>
            <div class="user-details">
              <div class="user-name">{{ user.firstName }} {{ user.lastName }}</div>
              <div class="user-email">{{ user.email }}</div>
              <div class="user-role">{{ getUserRoleDisplay(user.roles) }}</div>
            </div>
            <div class="user-actions">
              <div class="user-status" :class="getUserStatusClass(user)">{{ getUserStatus(user) }}</div>
              <div class="role-selector">
                <select 
                  :value="getUserPrimaryRole(user.roles)" 
                  @change="updateUserRole(user.id, ($event.target as HTMLSelectElement).value)"
                  class="role-select"
                  :disabled="isUpdatingRole"
                >
                  <option value="ROLE_COLLABORATOR">Collaborateur</option>
                  <option value="ROLE_MANAGER">Manager</option>
                  <option value="ROLE_PROJECT_MANAGER">Responsable de Projet</option>
                </select>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Pagination pour les utilisateurs -->
        <div v-if="totalUsersPages > 1" class="pagination">
          <button @click="prevUsersPage" :disabled="currentUsersPage === 1" class="pagination-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
            </svg>
          </button>
          <span class="pagination-info">
            Page {{ currentUsersPage }} sur {{ totalUsersPages }}
          </span>
          <button @click="nextUsersPage" :disabled="currentUsersPage === totalUsersPages" class="pagination-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Assignation automatique -->
      <div v-if="isProjectManager" class="admin-section">
        <div class="section-header">
          <h2>Assignation Automatique</h2>
        </div>
        <div class="assignment-actions">
          <button @click="assignAllTasks" class="action-btn primary" :disabled="isLoading">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
            </svg>
            ASSIGNER TOUTES LES TÂCHES
          </button>
          <button @click="redistributeTasks" class="action-btn secondary" :disabled="isLoading">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
            </svg>
            REDISTRIBUER LES TÂCHES
          </button>
        </div>
        <div class="assignment-stats">
          <div class="stat-box">
            <div class="stat-number">{{ stats.unassignedTasks }}</div>
            <div class="stat-label">Tâches non assignées</div>
          </div>
          <div class="stat-box">
            <div class="stat-number">{{ stats.overloadedUsers }}</div>
            <div class="stat-label">Utilisateurs surchargés</div>
          </div>
        </div>
      </div>

      <!-- Notifications -->
      <div class="admin-section">
        <div class="section-header">
          <h2>Notifications et Alertes</h2>
          <div class="header-actions">
            <button @click="checkAlerts" class="action-btn primary" :disabled="isLoading">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
              </svg>
              VÉRIFIER ALERTES
            </button>
          </div>
        </div>
        <div class="notifications-list">
          <div v-for="notification in paginatedNotifications" :key="notification.id" class="notification-item">
            <div class="notification-icon" :class="getNotificationTypeClass(notification.type)">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
              </svg>
            </div>
            <div class="notification-content">
              <div class="notification-title">{{ notification.title }}</div>
              <div class="notification-message">{{ notification.message }}</div>
              <div class="notification-date">{{ formatDate(notification.createdAt) }}</div>
            </div>
            <div class="notification-actions">
              <div class="notification-status" :class="notification.isRead ? 'read' : 'unread'">
                {{ notification.isRead ? 'Lu' : 'Non lu' }}
              </div>
              <div class="action-buttons">
                <button 
                  @click="toggleNotificationStatus(notification.id)" 
                  class="status-toggle-btn"
                  :class="notification.isRead ? 'mark-unread' : 'mark-read'"
                  :title="notification.isRead ? 'Marquer comme non lu' : 'Marquer comme lu'"
                >
                  <svg v-if="!notification.isRead" width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                  </svg>
                  <svg v-else width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 5v14H5V5h14m0-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/>
                  </svg>
                </button>
                <button 
                  @click="deleteNotification(notification.id)" 
                  class="delete-btn"
                  title="Supprimer la notification"
                >
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Pagination pour les notifications -->
        <div v-if="totalNotificationsPages > 1" class="pagination">
          <button @click="prevNotificationsPage" :disabled="currentNotificationsPage === 1" class="pagination-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
            </svg>
          </button>
          <span class="pagination-info">
            Page {{ currentNotificationsPage }} sur {{ totalNotificationsPages }}
          </span>
          <button @click="nextNotificationsPage" :disabled="currentNotificationsPage === totalNotificationsPages" class="pagination-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/>
            </svg>
          </button>
        </div>
      </div>
    </main>
    
    <!-- Notification Card -->
    <NotificationCard
      v-if="isVisible && notification"
      :type="notification.type"
      :title="notification.title"
      :message="notification.message"
      :button-text="notification.buttonText"
      :auto-close="notification.autoClose"
      :duration="notification.duration"
      @close="hide"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { authService, type User } from '@/services/authService'
import { userService } from '@/services/userService'
import { projectService } from '@/services/projectService'
import { taskService } from '@/services/taskService'
import { skillService } from '@/services/skillService'
import { notificationService } from '@/services/notificationService'
import { managerService } from '@/services/managerService'
import { useNotificationCard } from '@/composables/useNotificationCard'
import NotificationCard from '@/components/NotificationCard.vue'
import AvatarSelector from '@/components/AvatarSelector.vue'

const router = useRouter()
const { notification, isVisible, hide, success, error, warning, info } = useNotificationCard()

// État réactif
const user = ref<User | null>(null)
const users = ref<any[]>([])
const notifications = ref<any[]>([])
const isLoading = ref(false)
const isUpdatingRole = ref(false)

// Données spécifiques au manager
const managerDashboard = ref<any>(null)
const tasksInProgress = ref<any[]>([])
const recentAlerts = ref<any[]>([])
const performanceReport = ref<any[]>([])

// Pagination
const currentUsersPage = ref(1)
const currentNotificationsPage = ref(1)
const currentTasksPage = ref(1)
const currentAlertsPage = ref(1)
const currentPerformancePage = ref(1)
const itemsPerPage = 3

// Statistiques
const stats = ref({
  users: 0,
  projects: 0,
  tasks: 0,
  skills: 0,
  unassignedTasks: 0,
  overloadedUsers: 0
})

// Computed properties
const canManageProjects = computed(() => {
  return user.value?.permissions?.canManageProjects || false
})

const canAccessAdmin = computed(() => {
  return user.value?.permissions?.canAccessAdmin || false
})

const isManager = computed(() => {
  return user.value?.permissions?.primaryRole === 'ROLE_MANAGER'
})

// Pagination computed properties
const paginatedUsers = computed(() => {
  const start = (currentUsersPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return users.value.slice(start, end)
})

const paginatedNotifications = computed(() => {
  const start = (currentNotificationsPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return notifications.value.slice(start, end)
})

const paginatedTasksInProgress = computed(() => {
  const start = (currentTasksPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return tasksInProgress.value.slice(start, end)
})

const paginatedRecentAlerts = computed(() => {
  const start = (currentAlertsPage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return recentAlerts.value.slice(start, end)
})

const paginatedPerformanceReport = computed(() => {
  const start = (currentPerformancePage.value - 1) * itemsPerPage
  const end = start + itemsPerPage
  return performanceReport.value.slice(start, end)
})

const totalUsersPages = computed(() => {
  return Math.ceil(users.value.length / itemsPerPage)
})

const totalNotificationsPages = computed(() => {
  return Math.ceil(notifications.value.length / itemsPerPage)
})

const totalTasksPages = computed(() => {
  return Math.ceil(tasksInProgress.value.length / itemsPerPage)
})

const totalAlertsPages = computed(() => {
  return Math.ceil(recentAlerts.value.length / itemsPerPage)
})

const totalPerformancePages = computed(() => {
  return Math.ceil(performanceReport.value.length / itemsPerPage)
})

const isProjectManager = computed(() => {
  return user.value?.permissions?.primaryRole === 'ROLE_PROJECT_MANAGER'
})

// Méthodes
const loadUser = () => {
  user.value = authService.getCurrentUser()
}

const loadStats = async () => {
  try {
    isLoading.value = true
    
    // Charger les statistiques
    const [usersData, projectsData, skillsData] = await Promise.all([
      userService.getAllUsers(),
      projectService.getProjects(),
      skillService.getSkills()
    ])
    
    // Charger les tâches séparément pour éviter l'erreur TypeScript
    let tasksData: any[] = []
    try {
      const response = await fetch('/api/tasks', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('authToken')}`,
          'Content-Type': 'application/json'
        }
      })
      if (response.ok) {
        tasksData = await response.json()
      }
    } catch (error) {
      console.error('Erreur lors du chargement des tâches:', error)
    }
    
    stats.value = {
      users: usersData.length,
      projects: projectsData.length,
      tasks: tasksData.length,
      skills: skillsData.length,
      unassignedTasks: 0, // Pas d'assignations multiples
      overloadedUsers: 0  // Pas d'assignations multiples
    }
  } catch (error) {
    console.error('Erreur lors du chargement des statistiques:', error)
  } finally {
    isLoading.value = false
  }
}

// Fonctions pour charger les données du manager
const loadManagerDashboard = async () => {
  if (!isManager.value) return
  
  try {
    isLoading.value = true
    const dashboard = await managerService.getDashboard()
    managerDashboard.value = dashboard
    tasksInProgress.value = dashboard.tasksInProgress
    recentAlerts.value = dashboard.recentAlerts
  } catch (error) {
    console.error('Erreur lors du chargement du dashboard manager:', error)
  } finally {
    isLoading.value = false
  }
}

const loadPerformanceReport = async () => {
  if (!isManager.value) return
  
  try {
    const report = await managerService.getPerformanceReport()
    performanceReport.value = report
  } catch (error) {
    console.error('Erreur lors du chargement du rapport de performance:', error)
  }
}

const updateTaskPriority = async (taskId: number, priority: string) => {
  try {
    await managerService.updateTaskPriority(taskId, priority)
    // Recharger les données
    await loadManagerDashboard()
    success('Priorité mise à jour', 'La priorité de la tâche a été modifiée avec succès')
  } catch (err) {
    console.error('Erreur lors de la mise à jour de la priorité:', err)
    error('Erreur', 'Impossible de mettre à jour la priorité de la tâche')
  }
}

const loadUsers = async () => {
  try {
    const usersData = await userService.getAllUsers()
    users.value = usersData
  } catch (error) {
    console.error('Erreur lors du chargement des utilisateurs:', error)
  }
}

const loadNotifications = async () => {
  try {
    const notificationsData = await notificationService.getNotifications()
    notifications.value = notificationsData
  } catch (error) {
    console.error('Erreur lors du chargement des notifications:', error)
  }
}

const refreshUsers = async () => {
  await loadUsers()
  await loadStats()
}

const refreshNotifications = async () => {
  await loadNotifications()
}

const checkAlerts = async () => {
  try {
    isLoading.value = true
    
    // Vérifier les alertes de surcharge et de retard
    const workloadResult = await notificationService.checkWorkloadAlerts()
    const delayResult = await notificationService.checkDelayAlerts()
    
    console.log('Alertes de surcharge:', workloadResult)
    console.log('Alertes de retard:', delayResult)
    
    // Recharger les notifications après vérification
    await loadNotifications()
    await loadStats()
    
    const totalAlerts = (workloadResult.alerts?.length || 0) + (delayResult.alerts?.length || 0)
    if (totalAlerts > 0) {
      success(
        'Alertes vérifiées',
        `${totalAlerts} nouvelles alertes détectées et envoyées au responsable de projet`
      )
    } else {
      info(
        'Vérification terminée',
        'Aucune nouvelle alerte détectée'
      )
    }
  } catch (err) {
    console.error('Erreur lors de la vérification des alertes:', err)
    error(
      'Erreur de vérification',
      'Erreur lors de la vérification des alertes'
    )
  } finally {
    isLoading.value = false
  }
}

const assignAllTasks = async () => {
  try {
    isLoading.value = true
    
    // Fonctionnalité d'assignation automatique supprimée
    warning(
      'Fonctionnalité supprimée',
      'L\'assignation automatique multiple a été supprimée. Utilisez l\'assignation simple dans le modal de tâche.'
    )
  } catch (err) {
    console.error('Erreur lors de l\'assignation automatique:', err)
    error(
      'Erreur d\'assignation',
      'Erreur lors de l\'assignation automatique des tâches'
    )
  } finally {
    isLoading.value = false
  }
}

const redistributeTasks = async () => {
  try {
    isLoading.value = true
    
    // Fonctionnalité de redistribution supprimée
    warning(
      'Fonctionnalité supprimée',
      'La redistribution automatique multiple a été supprimée. Utilisez l\'assignation simple dans le modal de tâche.'
    )
  } catch (err) {
    console.error('Erreur lors de la redistribution des tâches:', err)
    error(
      'Erreur de redistribution',
      'Erreur lors de la redistribution des tâches'
    )
  } finally {
    isLoading.value = false
  }
}

const goToProjects = () => {
  router.push('/dashboard')
}

const logout = () => {
  authService.logout()
  router.push('/login')
}

const toggleNotificationStatus = async (notificationId: number) => {
  try {
    const updatedNotification = await notificationService.toggleReadStatus(notificationId)
    
    // Mettre à jour la notification dans la liste locale
    const index = notifications.value.findIndex(n => n.id === notificationId)
    if (index !== -1) {
      notifications.value[index] = updatedNotification
    }
    
    const status = updatedNotification.isRead ? 'lue' : 'non lue'
    success('Statut mis à jour', `Notification marquée comme ${status}`)
  } catch (err) {
    error('Erreur de mise à jour', `Erreur lors du changement d'état: ${err}`)
  }
}

const deleteNotification = async (notificationId: number) => {
  try {
    await notificationService.deleteNotification(notificationId)
    
    // Supprimer la notification de la liste locale
    notifications.value = notifications.value.filter(n => n.id !== notificationId)
    
    success('Suppression réussie', 'Notification supprimée avec succès')
  } catch (err) {
    error('Erreur de suppression', `Erreur lors de la suppression: ${err}`)
  }
}

// Méthodes de pagination
const goToUsersPage = (page: number) => {
  if (page >= 1 && page <= totalUsersPages.value) {
    currentUsersPage.value = page
  }
}

const goToNotificationsPage = (page: number) => {
  if (page >= 1 && page <= totalNotificationsPages.value) {
    currentNotificationsPage.value = page
  }
}

const nextUsersPage = () => {
  if (currentUsersPage.value < totalUsersPages.value) {
    currentUsersPage.value++
  }
}

const prevUsersPage = () => {
  if (currentUsersPage.value > 1) {
    currentUsersPage.value--
  }
}

const nextNotificationsPage = () => {
  if (currentNotificationsPage.value < totalNotificationsPages.value) {
    currentNotificationsPage.value++
  }
}

const prevNotificationsPage = () => {
  if (currentNotificationsPage.value > 1) {
    currentNotificationsPage.value--
  }
}

const getRoleDisplayName = () => {
  if (!user.value?.permissions?.primaryRole) return 'Utilisateur'
  
  const roleMap: { [key: string]: string } = {
    'ROLE_PROJECT_MANAGER': 'Responsable de Projet',
    'ROLE_MANAGER': 'Manager',
    'ROLE_COLLABORATOR': 'Collaborateur'
  }
  
  return roleMap[user.value.permissions.primaryRole] || 'Utilisateur'
}

const getUserRoleDisplay = (roles: any) => {
  if (Array.isArray(roles)) {
    if (roles.includes('ROLE_PROJECT_MANAGER')) return 'Responsable de Projet'
    if (roles.includes('ROLE_MANAGER')) return 'Manager'
    if (roles.includes('ROLE_COLLABORATOR')) return 'Collaborateur'
  } else if (typeof roles === 'object') {
    if (roles['2'] === 'ROLE_PROJECT_MANAGER') return 'Responsable de Projet'
    if (roles['2'] === 'ROLE_MANAGER') return 'Manager'
    if (roles['2'] === 'ROLE_COLLABORATOR') return 'Collaborateur'
  }
  return 'Utilisateur'
}

const getUserPrimaryRole = (roles: any) => {
  if (Array.isArray(roles)) {
    if (roles.includes('ROLE_PROJECT_MANAGER')) return 'ROLE_PROJECT_MANAGER'
    if (roles.includes('ROLE_MANAGER')) return 'ROLE_MANAGER'
    if (roles.includes('ROLE_COLLABORATOR')) return 'ROLE_COLLABORATOR'
  } else if (typeof roles === 'object') {
    if (roles['2'] === 'ROLE_PROJECT_MANAGER') return 'ROLE_PROJECT_MANAGER'
    if (roles['2'] === 'ROLE_MANAGER') return 'ROLE_MANAGER'
    if (roles['2'] === 'ROLE_COLLABORATOR') return 'ROLE_COLLABORATOR'
  }
  return 'ROLE_COLLABORATOR'
}

const updateUserRole = async (userId: number, newRole: string) => {
  try {
    isUpdatingRole.value = true
    const response = await userService.updateUserRole(userId, newRole)
    
    // Mettre à jour l'utilisateur dans la liste locale
    const userIndex = users.value.findIndex(u => u.id === userId)
    if (userIndex !== -1) {
      users.value[userIndex].roles = [newRole]
    }
    
    success('Rôle mis à jour', `Le rôle de l'utilisateur a été modifié avec succès`)
  } catch (err) {
    error('Erreur de mise à jour', `Erreur lors du changement de rôle: ${err}`)
  } finally {
    isUpdatingRole.value = false
  }
}

const getUserStatus = (user: any) => {
  if (user.utilizationPercentage >= 100) return 'Surchargé'
  if (user.utilizationPercentage >= 90) return 'Presque surchargé'
  if (user.utilizationPercentage >= 75) return 'Occupé'
  return 'Disponible'
}

const getUserStatusClass = (user: any) => {
  if (user.utilizationPercentage >= 100) return 'overloaded'
  if (user.utilizationPercentage >= 90) return 'busy'
  if (user.utilizationPercentage >= 75) return 'occupied'
  return 'available'
}

const getNotificationTypeClass = (type: string) => {
  const typeMap: { [key: string]: string } = {
    'info': 'info',
    'warning': 'warning',
    'error': 'error',
    'success': 'success',
    'workload_alert': 'warning',
    'delay_alert': 'error',
    'alert': 'error'
  }
  return typeMap[type] || 'info'
}

const formatDate = (dateString: string | undefined) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('fr-FR')
}

const formatDateTime = (dateString?: string) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleString('fr-FR')
}

const getEfficiencyColor = (efficiency: number) => {
  if (efficiency >= 100) return '#27ae60' // Vert
  if (efficiency >= 80) return '#f1c40f' // Jaune
  if (efficiency >= 60) return '#f39c12' // Orange
  return '#e74c3c' // Rouge
}

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    planning: 'En planification',
    in_progress: 'En cours',
    completed: 'Terminé',
    todo: 'À faire',
    cancelled: 'Annulé'
  }
  return labels[status] || status
}

const updateUserAvatar = (avatarUrl: string) => {
  if (user.value) {
    user.value.avatar = avatarUrl
    // TODO: Sauvegarder en base de données
    console.log('Avatar mis à jour:', avatarUrl)
  }
}

// Lifecycle
onMounted(async () => {
  loadUser()
  await Promise.all([
    loadStats(),
    loadUsers(),
    loadNotifications()
  ])
  
  // Charger les données spécifiques au manager
  if (isManager.value) {
    await loadManagerDashboard()
    await loadPerformanceReport()
  }
})
</script>

<style scoped>
.admin-container {
  display: flex;
  min-height: 100vh;
  background-color: #f8f9fa;
}

/* Sidebar */
.sidebar {
  width: 280px;
  background: linear-gradient(135deg, #1B263B 0%, #415A77 100%);
  color: white;
  display: flex;
  flex-direction: column;
  box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
  position: fixed;
  height: 100vh;
  overflow-y: auto;
}

.sidebar-header {
  padding: 2rem 1.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-header h2 {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
  color: white;
}

.sidebar-nav {
  flex: 1;
  padding: 1rem 0;
}

.nav-section {
  margin-bottom: 2rem;
}

.nav-title {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: rgba(255, 255, 255, 0.7);
  margin: 0 0 1rem 1.5rem;
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
  padding: 0.75rem 1.5rem;
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  transition: all 0.3s ease;
  border-left: 3px solid transparent;
}

.nav-link:hover {
  background-color: rgba(255, 255, 255, 0.1);
  color: white;
}

.nav-link.active {
  background-color: rgba(255, 255, 255, 0.15);
  color: white;
  border-left-color: white;
}

.nav-link svg {
  margin-right: 0.75rem;
  flex-shrink: 0;
}

/* Section Compte */
.account-section {
  margin-top: auto;
  padding-top: 1rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.account-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  margin-bottom: 1rem;
  background: rgba(255, 255, 255, 0.1);
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
  color: white;
  margin-bottom: 0.25rem;
  word-wrap: break-word;
  line-height: 1.2;
}

.account-email {
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.8);
  margin-bottom: 0.25rem;
  word-wrap: break-word;
  overflow-wrap: break-word;
  line-height: 1.2;
}

.account-member-since {
  font-size: 0.7rem;
  color: rgba(255, 255, 255, 0.7);
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
  background: #dc3545;
  color: white;
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

/* Main Content */
.main-content {
  flex: 1;
  padding: 2rem;
  overflow-y: auto;
  margin-left: 280px;
}

.content-header {
  margin-bottom: 2rem;
}

.content-header h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #2c3e50;
  margin: 0 0 0.5rem 0;
}

.content-header p {
  color: #7f8c8d;
  font-size: 1rem;
  margin: 0;
}

/* Stats Section */
.stats-section {
  margin-bottom: 2rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.stat-card {
  background: white;
  border-radius: 0.75rem;
  padding: 1.5rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.stat-card:nth-child(1) .stat-icon { background: linear-gradient(135deg, #1B263B 0%, #415A77 100%); }
.stat-card:nth-child(2) .stat-icon { background: linear-gradient(135deg, #415A77 0%, #778DA9 100%); }
.stat-card:nth-child(3) .stat-icon { background: linear-gradient(135deg, #1B263B 0%, #415A77 100%); }
.stat-card:nth-child(4) .stat-icon { background: linear-gradient(135deg, #415A77 0%, #778DA9 100%); }

.stat-content {
  flex: 1;
}

.stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: #2c3e50;
  line-height: 1;
}

.stat-label {
  font-size: 0.875rem;
  color: #7f8c8d;
  margin-top: 0.25rem;
}

/* Admin Sections */
.admin-section {
  background: white;
  border-radius: 0.75rem;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.header-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.section-header h2 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #2c3e50;
  margin: 0;
}

.refresh-btn {
  background: #415A77;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.refresh-btn:hover:not(:disabled) {
  background: #5a6fd8;
}

.refresh-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Users List */
.users-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.user-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 0.5rem;
  border: 1px solid #e9ecef;
}

.user-avatar {
  width: 40px;
  height: 40px;
  background: #415A77;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.user-details {
  flex: 1;
}

.user-name {
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.25rem;
}

.user-email {
  font-size: 0.875rem;
  color: #7f8c8d;
  margin-bottom: 0.25rem;
}

.user-role {
  font-size: 0.75rem;
  color: #415A77;
  font-weight: 500;
}

.user-actions {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.5rem;
}

.user-status {
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.75rem;
  font-weight: 500;
}

.user-status.available { background: #d4edda; color: #155724; }
.user-status.occupied { background: #fff3cd; color: #856404; }
.user-status.busy { background: #f8d7da; color: #721c24; }
.user-status.overloaded { background: #f5c6cb; color: #721c24; }

.role-selector {
  display: flex;
  align-items: center;
}

.role-select {
  padding: 0.375rem 0.75rem;
  border: 1px solid #e9ecef;
  border-radius: 0.375rem;
  background: white;
  font-size: 0.75rem;
  color: #415A77;
  cursor: pointer;
  transition: all 0.2s ease;
  min-width: 140px;
}

.role-select:hover:not(:disabled) {
  border-color: #1e3a8a;
  box-shadow: 0 0 0 1px #1e3a8a;
}

.role-select:focus {
  outline: none;
  border-color: #1e3a8a;
  box-shadow: 0 0 0 2px rgba(30, 58, 138, 0.2);
}

.role-select:disabled {
  background: #f8f9fa;
  color: #6c757d;
  cursor: not-allowed;
}

/* Pagination */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-top: 1.5rem;
  padding: 1rem;
}

.pagination-btn {
  padding: 0.5rem;
  border: 1px solid #e9ecef;
  background: white;
  border-radius: 0.375rem;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.pagination-btn:hover:not(:disabled) {
  background: #415A77;
  color: white;
  border-color: #415A77;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-info {
  font-size: 0.875rem;
  color: #6c757d;
  font-weight: 500;
}

/* Assignment Actions */
.assignment-actions {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.action-btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.action-btn.primary {
  background: #415A77;
  color: white;
}

.action-btn.primary:hover:not(:disabled) {
  background: #1B263B;
}

.action-btn.secondary {
  background: #f8f9fa;
  color: #415A77;
  border: 1px solid #415A77;
}

.action-btn.secondary:hover:not(:disabled) {
  background: #415A77;
  color: white;
}

.action-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.assignment-stats {
  display: flex;
  gap: 1rem;
}

.stat-box {
  flex: 1;
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 0.5rem;
  text-align: center;
  border: 1px solid #e9ecef;
}

.stat-box .stat-number {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 0.25rem;
}

.stat-box .stat-label {
  font-size: 0.875rem;
  color: #7f8c8d;
}

/* Notifications */
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
  background: #f8f9fa;
  border-radius: 0.5rem;
  border: 1px solid #e9ecef;
}

.notification-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
}

.notification-icon.info { background: #667eea; }
.notification-icon.warning { background: #f39c12; }
.notification-icon.error { background: #e74c3c; }
.notification-icon.success { background: #27ae60; }

.notification-content {
  flex: 1;
}

.notification-title {
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.25rem;
}

.notification-message {
  color: #7f8c8d;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
}

.notification-date {
  font-size: 0.75rem;
  color: #95a5a6;
}

.notification-status {
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.75rem;
  font-weight: 500;
  flex-shrink: 0;
}

.notification-status.read {
  background: #d4edda;
  color: #155724;
}

.notification-status.unread {
  background: #f8d7da;
  color: #721c24;
}

.notification-actions {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.5rem;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.status-toggle-btn {
  padding: 0.5rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.status-toggle-btn.mark-read {
  background: #28a745;
  color: white;
}

.status-toggle-btn.mark-read:hover {
  background: #218838;
  transform: translateY(-1px);
}

.status-toggle-btn.mark-unread {
  background: #6c757d;
  color: white;
}

.status-toggle-btn.mark-unread:hover {
  background: #5a6268;
  transform: translateY(-1px);
}

.delete-btn {
  padding: 0.5rem;
  border: none;
  border-radius: 6px;
  background: #dc3545;
  color: white;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.delete-btn:hover {
  background: #c82333;
  transform: translateY(-1px);
}

/* Styles pour les statistiques générales du Responsable de Projet */
.stats-section {
  margin-bottom: 2rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.stats-grid .stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  border: 1px solid #E5E7EB;
}

.stats-grid .stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.stats-grid .stat-icon {
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1B263B, #415A77);
  border-radius: 12px;
  color: white;
}

.stats-grid .stat-icon svg {
  width: 24px;
  height: 24px;
}

.stats-grid .stat-content {
  flex: 1;
}

.stats-grid .stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: #1B263B;
  line-height: 1;
}

.stats-grid .stat-label {
  font-size: 0.875rem;
  color: #6B7280;
  margin-top: 0.25rem;
}

/* Styles pour les sections du Manager */
.manager-sections {
  margin-top: 2rem;
}

.manager-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.manager-stats-grid .stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.manager-stats-grid .stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.manager-stats-grid .stat-icon {
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #1B263B, #415A77);
  border-radius: 12px;
  color: white;
}

.manager-stats-grid .stat-icon svg {
  width: 24px;
  height: 24px;
}

.manager-stats-grid .stat-content {
  flex: 1;
}

.manager-stats-grid .stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: #1B263B;
  line-height: 1;
}

.manager-stats-grid .stat-label {
  font-size: 0.875rem;
  color: #6B7280;
  margin-top: 0.25rem;
}

/* Styles pour les grilles de cartes */
.tasks-grid,
.alerts-grid,
.performance-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

/* Cartes de tâches */
.task-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  border: 1px solid #E5E7EB;
}

.task-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.task-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
}

.task-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1B263B;
  flex: 1;
  margin-right: 1rem;
}

.task-details {
  margin-bottom: 1rem;
}

.task-detail-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
}

.detail-label {
  color: #6B7280;
  font-weight: 500;
}

.detail-value {
  color: #1B263B;
  font-weight: 600;
}

.task-progress {
  margin-bottom: 1rem;
}

.progress-label {
  font-size: 0.875rem;
  color: #6B7280;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.progress-container {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.progress-bar {
  flex: 1;
  height: 8px;
  background: #E5E7EB;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #1B263B, #415A77);
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1B263B;
  min-width: 40px;
}

.task-actions {
  display: flex;
  justify-content: flex-end;
}

.priority-select {
  padding: 0.5rem 0.75rem;
  border: 1px solid #D1D5DB;
  border-radius: 6px;
  background: white;
  font-size: 0.875rem;
  color: #1B263B;
  cursor: pointer;
  transition: border-color 0.2s ease;
}

.priority-select:focus {
  outline: none;
  border-color: #1B263B;
  box-shadow: 0 0 0 2px rgba(27, 38, 59, 0.1);
}

.task-status {
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.75rem;
  font-weight: 500;
  white-space: nowrap;
}

.task-status.status-todo {
  background: #F3F4F6;
  color: #6B7280;
}

.task-status.status-in_progress {
  background: #DBEAFE;
  color: #1D4ED8;
}

.task-status.status-completed {
  background: #D1FAE5;
  color: #059669;
}

/* Cartes d'alertes */
.alert-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  border: 1px solid #E5E7EB;
  position: relative;
}

.alert-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.alert-card.unread {
  border-left: 4px solid #F59E0B;
  background: #FEFBF3;
}

.alert-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.alert-icon {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #F59E0B;
}

.alert-icon svg {
  width: 20px;
  height: 20px;
}

.alert-title {
  font-weight: 600;
  color: #1B263B;
  flex: 1;
  font-size: 1.125rem;
}

.unread-badge {
  background: #F59E0B;
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: 0.5rem;
  font-size: 0.75rem;
  font-weight: 500;
}

.alert-content {
  margin-top: 1rem;
}

.alert-message {
  color: #6B7280;
  margin-bottom: 0.75rem;
  line-height: 1.5;
}

.alert-date {
  font-size: 0.75rem;
  color: #9CA3AF;
}

/* Cartes de performance */
.performance-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  border: 1px solid #E5E7EB;
}

.performance-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.collaborator-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.collaborator-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: linear-gradient(135deg, #1B263B, #415A77);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 1.125rem;
}

.collaborator-info {
  flex: 1;
}

.collaborator-name {
  font-weight: 600;
  color: #1B263B;
  margin-bottom: 0.25rem;
  font-size: 1.125rem;
}

.collaborator-email {
  font-size: 0.875rem;
  color: #6B7280;
}

.performance-metrics {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.metric-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.metric {
  background: #F9FAFB;
  padding: 1rem;
  border-radius: 8px;
  text-align: center;
}

.metric-label {
  font-size: 0.75rem;
  color: #6B7280;
  margin-bottom: 0.5rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.metric-value {
  font-weight: 700;
  color: #1B263B;
  font-size: 1.25rem;
}

.metric-value.overdue {
  color: #DC2626;
}

/* Contrôles de pagination */
.pagination-controls {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-top: 2rem;
  padding: 1rem;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.pagination-btn {
  padding: 0.5rem 1rem;
  background: #1B263B;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s ease, transform 0.2s ease;
}

.pagination-btn:hover:not(:disabled) {
  background: #415A77;
  transform: translateY(-1px);
}

.pagination-btn:disabled {
  background: #9CA3AF;
  cursor: not-allowed;
  transform: none;
}

.pagination-info {
  font-size: 0.875rem;
  color: #6B7280;
  font-weight: 500;
  min-width: 80px;
  text-align: center;
}

/* Responsive */
@media (max-width: 768px) {
  .admin-container {
    flex-direction: column;
  }
  
  .sidebar {
    width: 100%;
    height: auto;
  }
  
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .manager-stats-grid {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  }
  
  .assignment-actions {
    flex-direction: column;
  }
  
  /* Responsive pour les nouvelles grilles */
  .tasks-grid,
  .alerts-grid,
  .performance-grid {
    grid-template-columns: 1fr;
  }
  
  .metric-row {
    grid-template-columns: 1fr;
  }
  
  .task-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  
  .collaborator-header {
    flex-direction: column;
    align-items: flex-start;
    text-align: center;
  }
}
</style>