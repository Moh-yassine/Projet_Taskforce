<template>
  <div class="role-management">
    <div class="header">
      <h2>Gestion des Rôles</h2>
      <p>Assignez des rôles aux utilisateurs pour contrôler leurs accès</p>
    </div>

    <div class="users-list">
      <div v-for="user in users" :key="user.id" class="user-card">
        <div class="user-info">
          <div class="user-avatar">
            {{ user.firstName.charAt(0) }}{{ user.lastName.charAt(0) }}
          </div>
          <div class="user-details">
            <h3>{{ user.firstName }} {{ user.lastName }}</h3>
            <p class="user-email">{{ user.email }}</p>
            <p class="user-company" v-if="user.company">{{ user.company }}</p>
          </div>
        </div>

        <div class="role-section">
          <div class="current-role">
            <label>Rôle actuel :</label>
            <span class="role-badge" :class="getRoleClass(user.permissions.primaryRole)">
              {{ getRoleLabel(user.permissions.primaryRole) }}
            </span>
          </div>

          <div class="role-selector">
            <label for="role-select">Changer le rôle :</label>
            <select 
              :id="`role-select-${user.id}`"
              :value="user.permissions.primaryRole"
              @change="updateUserRole(user.id, $event.target.value)"
              :disabled="loading"
            >
              <option v-for="role in availableRoles" :key="role.value" :value="role.value">
                {{ role.label }}
              </option>
            </select>
          </div>

          <div class="permissions-preview">
            <h4>Permissions :</h4>
            <div class="permissions-grid">
              <div class="permission-item" v-for="(value, key) in user.permissions" :key="key">
                <span v-if="typeof value === 'boolean'" class="permission-name">
                  {{ getPermissionLabel(key) }}:
                </span>
                <span v-if="typeof value === 'boolean'" class="permission-value" :class="{ 'granted': value, 'denied': !value }">
                  {{ value ? '✓' : '✗' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Chargement...</p>
    </div>

    <div v-if="error" class="error">
      <p>{{ error }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { roleService, type UserWithPermissions, type Role } from '@/services/roleService'

const users = ref<UserWithPermissions[]>([])
const availableRoles = ref<Role[]>([])
const loading = ref(false)
const error = ref('')

const loadUsers = async () => {
  try {
    loading.value = true
    error.value = ''
    users.value = await roleService.getUsers()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Erreur lors du chargement des utilisateurs'
  } finally {
    loading.value = false
  }
}

const loadAvailableRoles = async () => {
  try {
    availableRoles.value = await roleService.getAvailableRoles()
  } catch (err) {
    console.error('Erreur lors du chargement des rôles:', err)
  }
}

const updateUserRole = async (userId: number, newRole: string) => {
  try {
    loading.value = true
    error.value = ''
    
    const updatedUser = await roleService.assignRole(userId, newRole)
    
    // Mettre à jour l'utilisateur dans la liste
    const userIndex = users.value.findIndex(u => u.id === userId)
    if (userIndex !== -1) {
      users.value[userIndex] = updatedUser.user
    }
    
    // Afficher un message de succès
    console.log(`Rôle mis à jour pour l'utilisateur ${userId}`)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Erreur lors de la mise à jour du rôle'
  } finally {
    loading.value = false
  }
}

const getRoleLabel = (role: string): string => {
  return roleService.getRoleLabel(role)
}

const getRoleClass = (role: string): string => {
  const roleClasses: Record<string, string> = {
    'ROLE_PROJECT_MANAGER': 'role-project-manager',
    'ROLE_MANAGER': 'role-manager',
    'ROLE_COLLABORATOR': 'role-collaborator',
    'ROLE_USER': 'role-user'
  }
  return roleClasses[role] || 'role-default'
}

const getPermissionLabel = (key: string): string => {
  const permissionLabels: Record<string, string> = {
    'canManageProjects': 'Gérer les projets',
    'canSuperviseTasks': 'Superviser les tâches',
    'canAssignTasks': 'Assigner les tâches',
    'canViewAllTasks': 'Voir toutes les tâches',
    'canViewReports': 'Voir les rapports',
    'canManageUsers': 'Gérer les utilisateurs',
    'canManageSkills': 'Gérer les compétences',
    'canViewNotifications': 'Voir les notifications',
    'canManageNotifications': 'Gérer les notifications'
  }
  return permissionLabels[key] || key
}

onMounted(() => {
  loadUsers()
  loadAvailableRoles()
})
</script>

<style scoped>
.role-management {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.header {
  margin-bottom: 30px;
  text-align: center;
}

.header h2 {
  color: #2c3e50;
  margin-bottom: 10px;
}

.header p {
  color: #7f8c8d;
  font-size: 16px;
}

.users-list {
  display: grid;
  gap: 20px;
}

.user-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  border: 1px solid #e1e8ed;
}

.user-info {
  display: flex;
  align-items: center;
  margin-bottom: 20px;
}

.user-avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 18px;
  margin-right: 16px;
}

.user-details h3 {
  margin: 0 0 4px 0;
  color: #2c3e50;
  font-size: 18px;
}

.user-email {
  margin: 0 0 4px 0;
  color: #7f8c8d;
  font-size: 14px;
}

.user-company {
  margin: 0;
  color: #95a5a6;
  font-size: 13px;
}

.role-section {
  border-top: 1px solid #ecf0f1;
  padding-top: 20px;
}

.current-role {
  display: flex;
  align-items: center;
  margin-bottom: 16px;
}

.current-role label {
  margin-right: 12px;
  font-weight: 500;
  color: #2c3e50;
}

.role-badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.role-project-manager {
  background: #e8f5e8;
  color: #27ae60;
}

.role-manager {
  background: #e3f2fd;
  color: #2196f3;
}

.role-collaborator {
  background: #fff3e0;
  color: #ff9800;
}

.role-user {
  background: #f5f5f5;
  color: #757575;
}

.role-selector {
  margin-bottom: 20px;
}

.role-selector label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #2c3e50;
}

.role-selector select {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  background: white;
  cursor: pointer;
}

.role-selector select:disabled {
  background: #f8f9fa;
  cursor: not-allowed;
}

.permissions-preview h4 {
  margin: 0 0 12px 0;
  color: #2c3e50;
  font-size: 16px;
}

.permissions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 8px;
}

.permission-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 12px;
  background: #f8f9fa;
  border-radius: 6px;
  font-size: 13px;
}

.permission-name {
  color: #2c3e50;
  font-weight: 500;
}

.permission-value {
  font-weight: bold;
  font-size: 14px;
}

.permission-value.granted {
  color: #27ae60;
}

.permission-value.denied {
  color: #e74c3c;
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
  background: #fee;
  color: #c33;
  padding: 16px;
  border-radius: 6px;
  border: 1px solid #fcc;
  text-align: center;
}

@media (max-width: 768px) {
  .user-info {
    flex-direction: column;
    text-align: center;
  }
  
  .user-avatar {
    margin-right: 0;
    margin-bottom: 12px;
  }
  
  .permissions-grid {
    grid-template-columns: 1fr;
  }
}
</style>
