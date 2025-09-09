<template>
  <nav class="role-based-nav">
    <div class="nav-container">
      <div class="nav-brand">
        <h1>TaskForce</h1>
        <span class="user-role">{{ getCurrentUserRole() }}</span>
      </div>

      <div class="nav-menu">
        <!-- Navigation pour tous les utilisateurs -->
        <router-link to="/dashboard" class="nav-item">
          <i class="icon">🏠</i>
          <span>Tableau de bord</span>
        </router-link>

        <!-- Navigation pour les collaborateurs et plus -->
        <router-link to="/my-tasks" class="nav-item">
          <i class="icon">📋</i>
          <span>Mes tâches</span>
        </router-link>

        <!-- Navigation pour les managers et responsables de projet -->
        <template v-if="canSuperviseTasks">
          <router-link to="/all-tasks" class="nav-item">
            <i class="icon">📊</i>
            <span>Toutes les tâches</span>
          </router-link>
        </template>

        <!-- Navigation pour les responsables de projet uniquement -->
        <template v-if="canManageProjects">
          <router-link to="/projects" class="nav-item">
            <i class="icon">📁</i>
            <span>Projets</span>
          </router-link>
        </template>

        <!-- Navigation pour les managers et responsables de projet -->
        <template v-if="canViewReports">
          <router-link to="/reports" class="nav-item">
            <i class="icon">📈</i>
            <span>Rapports</span>
          </router-link>
        </template>

        <!-- Navigation pour les responsables de projet uniquement -->
        <template v-if="canManageUsers">
          <router-link to="/users" class="nav-item">
            <i class="icon">👥</i>
            <span>Utilisateurs</span>
          </router-link>
          <router-link to="/roles" class="nav-item">
            <i class="icon">🔐</i>
            <span>Rôles</span>
          </router-link>
        </template>

        <!-- Navigation pour les responsables de projet uniquement -->
        <template v-if="canManageSkills">
          <router-link to="/skills" class="nav-item">
            <i class="icon">🎯</i>
            <span>Compétences</span>
          </router-link>
        </template>

        <!-- Navigation pour tous les utilisateurs -->
        <router-link to="/notifications" class="nav-item">
          <i class="icon">🔔</i>
          <span>Notifications</span>
          <span v-if="unreadCount > 0" class="notification-badge">{{ unreadCount }}</span>
        </router-link>
      </div>

      <div class="nav-user">
        <div class="user-info">
          <div class="user-avatar">
            {{ userInitials }}
          </div>
          <div class="user-details">
            <span class="user-name">{{ currentUser?.firstName }} {{ currentUser?.lastName }}</span>
            <span class="user-email">{{ currentUser?.email }}</span>
          </div>
        </div>
        <button @click="logout" class="logout-btn">
          <i class="icon">🚪</i>
          <span>Déconnexion</span>
        </button>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { authService, type User } from '@/services/authService'
import { roleService } from '@/services/roleService'

const router = useRouter()
const currentUser = ref<User | null>(null)
const unreadCount = ref(0)

const userInitials = computed(() => {
  if (!currentUser.value) return 'U'
  return `${currentUser.value.firstName.charAt(0)}${currentUser.value.lastName.charAt(0)}`
})

const canManageProjects = computed(() => {
  return currentUser.value?.permissions?.canManageProjects || false
})

const canSuperviseTasks = computed(() => {
  return currentUser.value?.permissions?.canSuperviseTasks || false
})

const canViewReports = computed(() => {
  return currentUser.value?.permissions?.canViewReports || false
})

const canManageUsers = computed(() => {
  return currentUser.value?.permissions?.canManageUsers || false
})

const canManageSkills = computed(() => {
  return currentUser.value?.permissions?.canManageSkills || false
})

const getCurrentUserRole = () => {
  if (!currentUser.value?.permissions?.primaryRole) return 'Utilisateur'
  return roleService.getRoleLabel(currentUser.value.permissions.primaryRole)
}

const logout = () => {
  authService.logout()
  router.push('/login')
}

const loadUser = () => {
  currentUser.value = authService.getCurrentUser()
}

const loadUnreadNotifications = async () => {
  // TODO: Implémenter le chargement des notifications non lues
  // unreadCount.value = await notificationService.getUnreadCount()
}

onMounted(() => {
  loadUser()
  loadUnreadNotifications()
})
</script>

<style scoped>
.role-based-nav {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.nav-container {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
  height: 70px;
}

.nav-brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.nav-brand h1 {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
}

.user-role {
  background: rgba(255, 255, 255, 0.2);
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.nav-menu {
  display: flex;
  align-items: center;
  gap: 8px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  border-radius: 8px;
  text-decoration: none;
  color: white;
  font-weight: 500;
  transition: all 0.3s ease;
  position: relative;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.1);
  transform: translateY(-1px);
}

.nav-item.router-link-active {
  background: rgba(255, 255, 255, 0.2);
  font-weight: 600;
}

.nav-item .icon {
  font-size: 16px;
}

.notification-badge {
  background: #e74c3c;
  color: white;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 11px;
  font-weight: 600;
  position: absolute;
  top: 8px;
  right: 8px;
}

.nav-user {
  display: flex;
  align-items: center;
  gap: 16px;
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
  background: rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 14px;
}

.user-details {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-weight: 600;
  font-size: 14px;
}

.user-email {
  font-size: 12px;
  opacity: 0.8;
}

.logout-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 8px;
  color: white;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
}

.logout-btn:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-1px);
}

.logout-btn .icon {
  font-size: 14px;
}

@media (max-width: 768px) {
  .nav-container {
    flex-direction: column;
    height: auto;
    padding: 16px 20px;
    gap: 16px;
  }

  .nav-menu {
    flex-wrap: wrap;
    justify-content: center;
    gap: 4px;
  }

  .nav-item {
    padding: 8px 12px;
    font-size: 14px;
  }

  .nav-item span {
    display: none;
  }

  .nav-item .icon {
    font-size: 18px;
  }

  .user-details {
    display: none;
  }

  .logout-btn span {
    display: none;
  }
}

@media (max-width: 480px) {
  .nav-menu {
    grid-template-columns: repeat(4, 1fr);
    display: grid;
    width: 100%;
  }

  .nav-item {
    justify-content: center;
    padding: 12px 8px;
  }
}
</style>
