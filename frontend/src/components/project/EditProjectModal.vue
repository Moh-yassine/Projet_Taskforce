<template>
  <div class="modal-overlay" @click="closeModal">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h2>Modifier le projet</h2>
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

        <div class="form-actions">
          <button type="button" @click="closeModal" class="btn btn-secondary">Annuler</button>
          <button type="submit" class="btn btn-primary" :disabled="isLoading">
            <span v-if="isLoading" class="loading-spinner"></span>
            {{ isLoading ? 'Sauvegarde...' : 'Sauvegarder' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { authService } from '@/services/authService'

const props = defineProps<{
  project: any
}>()

const emit = defineEmits(['close', 'project-updated'])

const formData = ref({
  name: '',
  description: '',
  startDate: '',
  endDate: '',
  status: 'planning',
})

const isLoading = ref(false)

onMounted(() => {
  formData.value = {
    name: props.project.name || '',
    description: props.project.description || '',
    startDate: props.project.startDate
      ? new Date(props.project.startDate).toISOString().split('T')[0]
      : '',
    endDate: props.project.endDate
      ? new Date(props.project.endDate).toISOString().split('T')[0]
      : '',
    status: props.project.status || 'planning',
  }
})

const handleSubmit = async () => {
  if (isLoading.value) return

  isLoading.value = true

  try {
    const response = await fetch(`http://localhost:8000/api/projects/${props.project.id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${authService.getAuthToken()}`,
      },
      body: JSON.stringify(formData.value),
    })

    if (response.ok) {
      emit('project-updated')
    } else {
      const error = await response.json()
      alert(`Erreur: ${error.message || 'Erreur lors de la sauvegarde'}`)
    }
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error)
    alert('Erreur lors de la sauvegarde')
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
  max-width: 500px;
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
