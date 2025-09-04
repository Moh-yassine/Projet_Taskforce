<template>
  <div class="modal-overlay" @click="closeModal">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h2>Assigner un utilisateur à la tâche</h2>
        <button @click="closeModal" class="close-btn">&times;</button>
      </div>

      <div class="modal-body">
        <div class="search-section">
          <input
            v-model="searchTerm"
            type="text"
            placeholder="Rechercher un utilisateur..."
            class="search-input"
          />
        </div>

        <div class="users-list">
          <div 
            v-for="user in filteredUsers" 
            :key="user.id"
            class="user-item"
            @click="assignUser(user)"
          >
            <div class="user-avatar">
              {{ user.firstName.charAt(0) }}{{ user.lastName.charAt(0) }}
            </div>
            <div class="user-info">
              <span class="user-name">{{ user.fullName }}</span>
              <span class="user-email">{{ user.email }}</span>
              <div class="user-skills">
                <span 
                  v-for="skill in user.skills.slice(0, 3)" 
                  :key="skill.id"
                  class="skill-tag"
                >
                  {{ skill.name }}
                </span>
              </div>
            </div>
            <div class="user-workload">
              <span class="workload-label">Charge de travail:</span>
              <span class="workload-value">{{ user.currentWorkload || 0 }}h</span>
            </div>
          </div>
        </div>

        <div class="no-users" v-if="filteredUsers.length === 0">
          <p>Aucun utilisateur disponible</p>
        </div>
      </div>

      <div class="modal-footer">
        <button @click="closeModal" class="btn btn-secondary">
          Annuler
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { authService } from '@/services/authService'

const props = defineProps<{
  taskId: number
}>()

const emit = defineEmits(['close', 'user-assigned'])

const users = ref([])
const searchTerm = ref('')

const filteredUsers = computed(() => {
  if (!searchTerm.value) return users.value
  
  return users.value.filter(user => 
    user.fullName.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
    user.email.toLowerCase().includes(searchTerm.value.toLowerCase())
  )
})

onMounted(async () => {
  await loadAvailableUsers()
})

const loadAvailableUsers = async () => {
  try {
    const response = await fetch('http://localhost:8000/api/users/available', {
      headers: {
        'Authorization': `Bearer ${authService.getAuthToken()}`
      }
    })
    if (response.ok) {
      users.value = await response.json()
    }
  } catch (error) {
    console.error('Erreur lors du chargement des utilisateurs:', error)
  }
}

const assignUser = async (user: any) => {
  try {
    const response = await fetch(`http://localhost:8000/api/tasks/${props.taskId}/assign`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${authService.getAuthToken()}`
      },
      body: JSON.stringify({ userId: user.id })
    })

    if (response.ok) {
      emit('user-assigned')
    } else {
      const error = await response.json()
      alert(`Erreur: ${error.message || 'Erreur lors de l\'assignation'}`)
    }
  } catch (error) {
    console.error('Erreur lors de l\'assignation:', error)
    alert('Erreur lors de l\'assignation')
  }
}

const closeModal = () => {
  emit('close')
}
</script>

<style scoped>
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
  padding: 1rem;
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e1e5e9;
}

.modal-header h2 {
  color: #172b4d;
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #6b778c;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 4px;
  transition: background-color 0.2s ease;
}

.close-btn:hover {
  background: #f4f5f7;
}

.modal-body {
  padding: 1.5rem;
}

.search-section {
  margin-bottom: 1.5rem;
}

.search-input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #dfe1e6;
  border-radius: 4px;
  font-size: 0.9rem;
  transition: border-color 0.2s ease;
}

.search-input:focus {
  outline: none;
  border-color: #0079bf;
  box-shadow: 0 0 0 2px rgba(0, 121, 191, 0.2);
}

.users-list {
  max-height: 400px;
  overflow-y: auto;
}

.user-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  margin-bottom: 0.75rem;
  border: 1px solid #e1e5e9;
}

.user-item:hover {
  background: #f4f5f7;
  border-color: #0079bf;
  transform: translateY(-1px);
}

.user-avatar {
  width: 40px;
  height: 40px;
  background: #0079bf;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.9rem;
  flex-shrink: 0;
}

.user-info {
  flex: 1;
  min-width: 0;
}

.user-name {
  display: block;
  font-weight: 600;
  color: #172b4d;
  font-size: 0.9rem;
  margin-bottom: 0.25rem;
}

.user-email {
  display: block;
  color: #6b778c;
  font-size: 0.8rem;
  margin-bottom: 0.5rem;
}

.user-skills {
  display: flex;
  gap: 0.25rem;
  flex-wrap: wrap;
}

.skill-tag {
  background: #f4f5f7;
  color: #6b778c;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.7rem;
  font-weight: 500;
}

.user-workload {
  text-align: right;
  flex-shrink: 0;
}

.workload-label {
  display: block;
  font-size: 0.7rem;
  color: #6b778c;
  margin-bottom: 0.25rem;
}

.workload-value {
  display: block;
  font-weight: 600;
  color: #172b4d;
  font-size: 0.9rem;
}

.no-users {
  text-align: center;
  padding: 2rem;
  color: #6b778c;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem;
  border-top: 1px solid #e1e5e9;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 4px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-secondary {
  background: #f4f5f7;
  color: #172b4d;
  border: 1px solid #dfe1e6;
}

.btn-secondary:hover {
  background: #ebecf0;
}

@media (max-width: 768px) {
  .modal-content {
    margin: 1rem;
    max-height: calc(100vh - 2rem);
  }
  
  .user-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.75rem;
  }
  
  .user-workload {
    text-align: left;
    align-self: flex-start;
  }
  
  .modal-footer {
    flex-direction: column;
  }
  
  .btn {
    width: 100%;
  }
}
</style>
