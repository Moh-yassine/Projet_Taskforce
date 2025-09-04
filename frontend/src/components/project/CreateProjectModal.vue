<template>
  <div class="modal-overlay" @click="closeModal">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h2>Créer un nouveau projet</h2>
        <button @click="closeModal" class="close-btn">&times;</button>
      </div>

      <form @submit.prevent="handleSubmit" class="modal-form">
        <div class="form-group">
          <label for="name">Nom du projet *</label>
          <input
            id="name"
            v-model="formData.name"
            type="text"
            required
            placeholder="Nom du projet"
            class="form-input"
          />
        </div>

        <div class="form-group">
          <label for="description">Description</label>
          <textarea
            id="description"
            v-model="formData.description"
            placeholder="Description du projet"
            rows="3"
            class="form-input"
          ></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="startDate">Date de début *</label>
            <input
              id="startDate"
              v-model="formData.startDate"
              type="date"
              required
              class="form-input"
            />
          </div>
          <div class="form-group">
            <label for="endDate">Date de fin *</label>
            <input
              id="endDate"
              v-model="formData.endDate"
              type="date"
              required
              class="form-input"
            />
          </div>
        </div>

        <div class="form-group">
          <label for="status">Statut</label>
          <select id="status" v-model="formData.status" class="form-input">
            <option value="planning">Planification</option>
            <option value="active">Actif</option>
            <option value="on-hold">En pause</option>
            <option value="completed">Terminé</option>
          </select>
        </div>

        <div class="form-group">
          <label>Membres de l'équipe</label>
          <div class="team-members">
            <div 
              v-for="member in availableUsers" 
              :key="member.id"
              class="member-item"
              :class="{ 'selected': selectedMembers.includes(member.id) }"
              @click="toggleMember(member.id)"
            >
              <div class="member-avatar">
                {{ member.firstName.charAt(0) }}{{ member.lastName.charAt(0) }}
              </div>
              <div class="member-info">
                <span class="member-name">{{ member.fullName }}</span>
                <span class="member-email">{{ member.email }}</span>
              </div>
              <div class="member-skills">
                <span 
                  v-for="skill in member.skills.slice(0, 3)" 
                  :key="skill.id"
                  class="skill-tag"
                >
                  {{ skill.name }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button type="button" @click="closeModal" class="btn btn-secondary">
            Annuler
          </button>
          <button type="submit" class="btn btn-primary" :disabled="isLoading">
            <span v-if="isLoading" class="loading-spinner"></span>
            {{ isLoading ? 'Création...' : 'Créer le projet' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { authService } from '@/services/authService'

const emit = defineEmits(['close', 'project-created'])

const formData = ref({
  name: '',
  description: '',
  startDate: '',
  endDate: '',
  status: 'planning',
  teamMembers: []
})

const availableUsers = ref([])
const selectedMembers = ref([])
const isLoading = ref(false)

onMounted(async () => {
  await loadAvailableUsers()
  setDefaultDates()
})

const setDefaultDates = () => {
  const today = new Date()
  const nextMonth = new Date(today.getFullYear(), today.getMonth() + 1, today.getDate())
  
  formData.value.startDate = today.toISOString().split('T')[0]
  formData.value.endDate = nextMonth.toISOString().split('T')[0]
}

const loadAvailableUsers = async () => {
  try {
    const response = await fetch('http://localhost:8000/api/users', {
      headers: {
        'Authorization': `Bearer ${authService.getAuthToken()}`
      }
    })
    if (response.ok) {
      availableUsers.value = await response.json()
    }
  } catch (error) {
    console.error('Erreur lors du chargement des utilisateurs:', error)
  }
}

const toggleMember = (memberId: number) => {
  const index = selectedMembers.value.indexOf(memberId)
  if (index > -1) {
    selectedMembers.value.splice(index, 1)
  } else {
    selectedMembers.value.push(memberId)
  }
}

const handleSubmit = async () => {
  if (isLoading.value) return

  isLoading.value = true

  try {
    const projectData = {
      ...formData.value,
      teamMembers: selectedMembers.value
    }

    const response = await fetch('http://localhost:8000/api/projects', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${authService.getAuthToken()}`
      },
      body: JSON.stringify(projectData)
    })

    if (response.ok) {
      const result = await response.json()
      emit('project-created', result)
    } else {
      const error = await response.json()
      alert(`Erreur: ${error.message || 'Erreur lors de la création du projet'}`)
    }
  } catch (error) {
    console.error('Erreur lors de la création du projet:', error)
    alert('Erreur lors de la création du projet')
  } finally {
    isLoading.value = false
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

.modal-form {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #172b4d;
  font-size: 0.9rem;
}

.form-input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #dfe1e6;
  border-radius: 4px;
  font-size: 0.9rem;
  transition: border-color 0.2s ease;
}

.form-input:focus {
  outline: none;
  border-color: #0079bf;
  box-shadow: 0 0 0 2px rgba(0, 121, 191, 0.2);
}

textarea.form-input {
  resize: vertical;
  min-height: 80px;
}

.team-members {
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid #dfe1e6;
  border-radius: 4px;
  padding: 0.5rem;
}

.member-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.2s ease;
  margin-bottom: 0.5rem;
}

.member-item:hover {
  background: #f4f5f7;
}

.member-item.selected {
  background: #e4f0f6;
  border: 1px solid #0079bf;
}

.member-avatar {
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
}

.member-info {
  flex: 1;
}

.member-name {
  display: block;
  font-weight: 600;
  color: #172b4d;
  font-size: 0.9rem;
}

.member-email {
  display: block;
  color: #6b778c;
  font-size: 0.8rem;
}

.member-skills {
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

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 2rem;
  padding-top: 1.5rem;
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

.btn-primary {
  background: #0079bf;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #005a8b;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: #f4f5f7;
  color: #172b4d;
  border: 1px solid #dfe1e6;
}

.btn-secondary:hover {
  background: #ebecf0;
}

.loading-spinner {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid transparent;
  border-top: 2px solid currentColor;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-right: 0.5rem;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 768px) {
  .modal-content {
    margin: 1rem;
    max-height: calc(100vh - 2rem);
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .form-actions {
    flex-direction: column;
  }
  
  .btn {
    width: 100%;
  }
}
</style>
