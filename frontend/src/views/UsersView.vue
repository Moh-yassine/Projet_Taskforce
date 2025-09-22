<template>
  <div class="users-view">
    <div class="page-header">
      <h1>Gestion des Utilisateurs</h1>
      <p>Gérez les utilisateurs et leurs rôles dans l'application</p>
    </div>

    <div class="users-content">
      <div class="users-stats">
        <div class="stat-card">
          <div class="stat-icon">👥</div>
          <div class="stat-info">
            <h3>{{ users.length }}</h3>
            <p>Utilisateurs total</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">👑</div>
          <div class="stat-info">
            <h3>{{ projectManagersCount }}</h3>
            <p>Responsables de projet</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">👨‍💼</div>
          <div class="stat-info">
            <h3>{{ managersCount }}</h3>
            <p>Managers</p>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">👨‍💻</div>
          <div class="stat-info">
            <h3>{{ collaboratorsCount }}</h3>
            <p>Collaborateurs</p>
          </div>
        </div>
      </div>

      <div class="users-table-container">
        <div class="table-header">
          <h2>Liste des Utilisateurs</h2>
          <div class="table-actions">
            <button @click="refreshUsers" class="btn btn-secondary" :disabled="loading">
              <i class="icon">🔄</i>
              Actualiser
            </button>
          </div>
        </div>

        <div v-if="loading" class="loading">
          <div class="spinner"></div>
          <p>Chargement des utilisateurs...</p>
        </div>

        <div v-else-if="error" class="error">
          <p>{{ error }}</p>
          <button @click="loadUsers" class="btn btn-primary">Réessayer</button>
        </div>

        <div v-else class="users-table">
          <table>
            <thead>
              <tr>
                <th>Utilisateur</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Permissions</th>
                <th>Date d'inscription</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in users" :key="user.id">
                <td class="user-cell">
                  <div class="user-avatar">
                    {{ user.firstName.charAt(0) }}{{ user.lastName.charAt(0) }}
                  </div>
                  <div class="user-info">
                    <div class="user-name">{{ user.firstName }} {{ user.lastName }}</div>
                    <div class="user-company" v-if="user.company">{{ user.company }}</div>
                  </div>
                </td>
                <td class="email-cell">{{ user.email }}</td>
                <td class="role-cell">
                  <span class="role-badge" :class="getRoleClass(user.permissions.primaryRole)">
                    {{ getRoleLabel(user.permissions.primaryRole) }}
                  </span>
                </td>
                <td class="permissions-cell">
                  <div class="permissions-summary">
                    <span v-if="user.permissions.canManageProjects" class="permission-tag"
                      >Gestion Projets</span
                    >
                    <span v-if="user.permissions.canSuperviseTasks" class="permission-tag"
                      >Supervision</span
                    >
                    <span v-if="user.permissions.canAssignTasks" class="permission-tag"
                      >Assignation</span
                    >
                    <span v-if="user.permissions.canViewReports" class="permission-tag"
                      >Rapports</span
                    >
                    <span v-if="user.permissions.canManageUsers" class="permission-tag"
                      >Gestion Users</span
                    >
                  </div>
                </td>
                <td class="date-cell">{{ formatDate(user.createdAt) }}</td>
                <td class="actions-cell">
                  <button @click="editUser(user)" class="btn btn-sm btn-primary">
                    <i class="icon">✏️</i>
                    Modifier
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal de modification d'utilisateur -->
    <div v-if="showEditModal" class="modal-overlay" @click="closeEditModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Modifier l'utilisateur</h3>
          <button @click="closeEditModal" class="modal-close">×</button>
        </div>
        <div class="modal-body">
          <div v-if="editingUser" class="edit-form">
            <div class="form-group">
              <label>Nom complet</label>
              <div class="user-display">{{ editingUser.firstName }} {{ editingUser.lastName }}</div>
            </div>
            <div class="form-group">
              <label>Email</label>
              <div class="user-display">{{ editingUser.email }}</div>
            </div>
            <div class="form-group">
              <label>Rôle</label>
              <select v-model="newRole" class="form-control">
                <option v-for="role in availableRoles" :key="role.value" :value="role.value">
                  {{ role.label }}
                </option>
              </select>
            </div>
            <div class="form-group">
              <label>Nouvelles permissions</label>
              <div class="permissions-preview">
                <div
                  v-for="(value, key) in getPreviewPermissions()"
                  :key="key"
                  class="permission-item"
                >
                  <span class="permission-name">{{ getPermissionLabel(key) }}:</span>
                  <span class="permission-value" :class="{ granted: value, denied: !value }">
                    {{ value ? '✓' : '✗' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeEditModal" class="btn btn-secondary">Annuler</button>
          <button @click="saveUserRole" class="btn btn-primary" :disabled="saving">
            {{ saving ? 'Sauvegarde...' : 'Sauvegarder' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import {
  roleService,
  type UserWithPermissions,
  type Role,
  type UserPermissions,
} from '@/services/roleService'

const users = ref<UserWithPermissions[]>([])
const availableRoles = ref<Role[]>([])
const loading = ref(false)
const error = ref('')
const showEditModal = ref(false)
const editingUser = ref<UserWithPermissions | null>(null)
const newRole = ref('')
const saving = ref(false)

const projectManagersCount = computed(
  () => users.value.filter((u) => u.permissions.primaryRole === 'ROLE_PROJECT_MANAGER').length,
)

const managersCount = computed(
  () => users.value.filter((u) => u.permissions.primaryRole === 'ROLE_MANAGER').length,
)

const collaboratorsCount = computed(
  () => users.value.filter((u) => u.permissions.primaryRole === 'ROLE_COLLABORATOR').length,
)

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

const refreshUsers = () => {
  loadUsers()
}

const editUser = (user: UserWithPermissions) => {
  editingUser.value = user
  newRole.value = user.permissions.primaryRole
  showEditModal.value = true
}

const closeEditModal = () => {
  showEditModal.value = false
  editingUser.value = null
  newRole.value = ''
}

const saveUserRole = async () => {
  if (!editingUser.value) return

  try {
    saving.value = true
    const updatedUser = await roleService.assignRole(editingUser.value.id, newRole.value)

    // Mettre à jour l'utilisateur dans la liste
    const userIndex = users.value.findIndex((u) => u.id === editingUser.value!.id)
    if (userIndex !== -1) {
      users.value[userIndex] = updatedUser.user
    }

    closeEditModal()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Erreur lors de la sauvegarde'
  } finally {
    saving.value = false
  }
}

const getRoleLabel = (role: string): string => {
  return roleService.getRoleLabel(role)
}

const getRoleClass = (role: string): string => {
  const roleClasses: Record<string, string> = {
    ROLE_PROJECT_MANAGER: 'role-project-manager',
    ROLE_MANAGER: 'role-manager',
    ROLE_COLLABORATOR: 'role-collaborator',
    ROLE_USER: 'role-user',
  }
  return roleClasses[role] || 'role-default'
}

const getPermissionLabel = (key: string): string => {
  const permissionLabels: Record<string, string> = {
    canManageProjects: 'Gérer les projets',
    canSuperviseTasks: 'Superviser les tâches',
    canAssignTasks: 'Assigner les tâches',
    canViewAllTasks: 'Voir toutes les tâches',
    canViewReports: 'Voir les rapports',
    canManageUsers: 'Gérer les utilisateurs',
    canManageSkills: 'Gérer les compétences',
    canViewNotifications: 'Voir les notifications',
    canManageNotifications: 'Gérer les notifications',
  }
  return permissionLabels[key] || key
}

const getPreviewPermissions = (): UserPermissions => {
  // Simuler les permissions basées sur le nouveau rôle
  const rolePermissions: Record<string, Partial<UserPermissions>> = {
    ROLE_PROJECT_MANAGER: {
      canManageProjects: true,
      canSuperviseTasks: true,
      canAssignTasks: true,
      canViewAllTasks: true,
      canViewReports: true,
      canManageUsers: true,
      canManageSkills: true,
      canViewNotifications: true,
      canManageNotifications: true,
    },
    ROLE_MANAGER: {
      canManageProjects: false,
      canSuperviseTasks: true,
      canAssignTasks: false,
      canViewAllTasks: true,
      canViewReports: true,
      canManageUsers: false,
      canManageSkills: false,
      canViewNotifications: true,
      canManageNotifications: true,
    },
    ROLE_COLLABORATOR: {
      canManageProjects: false,
      canSuperviseTasks: false,
      canAssignTasks: false,
      canViewAllTasks: false,
      canViewReports: false,
      canManageUsers: false,
      canManageSkills: false,
      canViewNotifications: true,
      canManageNotifications: false,
    },
  }

  return (
    (rolePermissions[newRole.value] as UserPermissions) ||
    editingUser.value?.permissions ||
    ({} as UserPermissions)
  )
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('fr-FR')
}

onMounted(() => {
  loadUsers()
  loadAvailableRoles()
})
</script>

<style scoped>
.users-view {
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

.users-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
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

.users-table-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid #ecf0f1;
}

.table-header h2 {
  margin: 0;
  color: #2c3e50;
}

.users-table {
  overflow-x: auto;
}

.users-table table {
  width: 100%;
  border-collapse: collapse;
}

.users-table th,
.users-table td {
  padding: 16px;
  text-align: left;
  border-bottom: 1px solid #ecf0f1;
}

.users-table th {
  background: #f8f9fa;
  font-weight: 600;
  color: #2c3e50;
}

.user-cell {
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

.user-company {
  font-size: 12px;
  color: #7f8c8d;
}

.role-badge {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 11px;
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

.permissions-summary {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.permission-tag {
  background: #e3f2fd;
  color: #1976d2;
  padding: 2px 6px;
  border-radius: 8px;
  font-size: 10px;
  font-weight: 500;
}

.date-cell {
  color: #7f8c8d;
  font-size: 14px;
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

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid #ecf0f1;
}

.modal-header h3 {
  margin: 0;
  color: #2c3e50;
}

.modal-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #7f8c8d;
}

.modal-body {
  padding: 24px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #2c3e50;
}

.form-control {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
}

.user-display {
  padding: 10px 12px;
  background: #f8f9fa;
  border-radius: 6px;
  color: #2c3e50;
}

.permissions-preview {
  background: #f8f9fa;
  border-radius: 6px;
  padding: 16px;
}

.permission-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #e9ecef;
}

.permission-item:last-child {
  border-bottom: none;
}

.permission-name {
  color: #2c3e50;
  font-weight: 500;
}

.permission-value {
  font-weight: bold;
}

.permission-value.granted {
  color: #27ae60;
}

.permission-value.denied {
  color: #e74c3c;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 24px;
  border-top: 1px solid #ecf0f1;
}

@media (max-width: 768px) {
  .users-stats {
    grid-template-columns: repeat(2, 1fr);
  }

  .table-header {
    flex-direction: column;
    gap: 16px;
    align-items: stretch;
  }

  .users-table {
    font-size: 14px;
  }

  .users-table th,
  .users-table td {
    padding: 12px 8px;
  }

  .permissions-summary {
    flex-direction: column;
  }
}
</style>
