<template>
  <div class="modal-overlay" @click="closeModal">
    <div class="modal-content task-modal" @click.stop>
      <div class="modal-header">
        <h2>{{ isEdit ? 'Modifier la tâche' : 'Créer une nouvelle tâche' }}</h2>
        <button @click="closeModal" class="close-btn">&times;</button>
      </div>
      
      <form @submit.prevent="handleSubmit" class="task-form">
        <div class="form-section">
          <h3>Informations de base</h3>
          
          <div class="form-group">
            <label for="title">Titre de la tâche *</label>
            <input
              id="title"
              v-model="formData.title"
              type="text"
              required
              placeholder="Entrez le titre de la tâche"
              class="form-input"
            />
          </div>
          
          <div class="form-group">
            <label for="description">Description</label>
            <textarea
              id="description"
              v-model="formData.description"
              rows="4"
              placeholder="Décrivez la tâche en détail"
              class="form-textarea"
            ></textarea>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label for="priority">Priorité *</label>
              <select
                id="priority"
                v-model="formData.priority"
                required
                class="form-select"
              >
                <option value="">Sélectionnez une priorité</option>
                <option value="low">Basse</option>
                <option value="medium">Moyenne</option>
                <option value="high">Haute</option>
                <option value="urgent">Urgente</option>
              </select>
            </div>
            
            <div class="form-group">
              <label for="status">Statut</label>
              <select
                id="status"
                v-model="formData.status"
                class="form-select"
              >
                <option value="todo">À faire</option>
                <option value="in_progress">En cours</option>
                <option value="completed">Terminé</option>
              </select>
            </div>
          </div>
        </div>
        
        <div class="form-section">
          <h3>Dates et historique</h3>
          
          <div class="form-row">
            <div class="form-group">
              <label for="startDate">Date de début</label>
              <input
                id="startDate"
                v-model="formData.startDate"
                type="date"
                class="form-input"
              />
            </div>
            
            <div class="form-group">
              <label for="dueDate">Date d'échéance</label>
              <input
                id="dueDate"
                v-model="formData.dueDate"
                type="date"
                class="form-input"
              />
            </div>
          </div>
          
        </div>
        
        <div class="form-section">
          <h3>Assignation et compétences</h3>
          
          <div class="form-group">
            <label for="assignee">Assigner à</label>
            <select
              id="assignee"
              v-model="formData.assigneeId"
              class="form-select"
            >
              <option value="">Non assigné</option>
              <option 
                v-for="user in availableUsers" 
                :key="user.id"
                :value="user.id"
              >
                {{ user.firstName }} {{ user.lastName }} ({{ user.role }})
              </option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Compétences requises</label>
            <div class="skills-container">
              <div class="skills-input">
                <input
                  v-model="newSkill"
                  type="text"
                  placeholder="Ajouter une compétence"
                  class="form-input"
                  @keyup.enter="addSkill"
                />
                <button type="button" @click="addSkill" class="add-skill-btn">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                  </svg>
                </button>
              </div>
              <div class="skills-list">
                <span 
                  v-for="(skill, index) in formData.skills" 
                  :key="index"
                  class="skill-tag"
                >
                  {{ skill.name }}
                  <button type="button" @click="removeSkill(index)" class="remove-skill">
                    &times;
                  </button>
                </span>
              </div>
            </div>
          </div>
        </div>
        
        <div class="form-section">
          <h3>Charge de travail</h3>
          
          <div class="form-row">
            <div class="form-group">
              <label for="estimatedHours">Heures estimées</label>
              <input
                id="estimatedHours"
                v-model.number="formData.estimatedHours"
                type="number"
                min="0"
                step="0.5"
                placeholder="0"
                class="form-input"
              />
            </div>
            
            <div class="form-group">
              <label for="actualHours">Heures réelles</label>
              <input
                id="actualHours"
                v-model.number="formData.actualHours"
                type="number"
                min="0"
                step="0.5"
                placeholder="0"
                class="form-input"
                :disabled="!isEdit"
              />
            </div>
          </div>
        </div>
        
        <div class="modal-actions">
          <button type="button" @click="closeModal" class="btn btn-secondary">
            Annuler
          </button>
          <button type="submit" class="btn btn-primary" :disabled="!isFormValid">
            {{ isEdit ? 'Enregistrer les modifications' : 'Créer la tâche' }}
          </button>
        </div>
      </form>
    </div>
  </div>
  
  <!-- Popup de confirmation -->
  <div v-if="showSuccessMessage" class="success-popup">
    <div class="success-content">
      <div class="success-icon">✅</div>
      <div class="success-message">
        {{ isEdit ? 'Modifications enregistrées avec succès !' : 'Tâche créée avec succès !' }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'

interface Task {
  id?: number
  title: string
  description: string
  status: string
  priority: string
  assigneeId?: number
  skills: Array<{
    id: number
    name: string
  }>
  startDate?: string
  dueDate?: string
  estimatedHours?: number
  actualHours?: number
  history?: Array<{
    date: string
    action: string
    user: string
  }>
}

interface User {
  id: number
  firstName: string
  lastName: string
  role: string
}

interface Props {
  task?: Task | null
  project?: any
}

interface Emits {
  (e: 'close'): void
  (e: 'save', task: Task): void
}

const props = withDefaults(defineProps<Props>(), {
  task: null,
  project: null
})

const emit = defineEmits<Emits>()

// État du formulaire
const formData = ref<Task>({
  title: '',
  description: '',
  status: 'todo',
  priority: '',
  assigneeId: undefined,
  skills: [],
  startDate: '',
  dueDate: '',
  estimatedHours: 0,
  actualHours: 0,
  history: []
})

const newSkill = ref('')
const availableUsers = ref<User[]>([])
const showSuccessMessage = ref(false)

// Computed pour déterminer si on est en mode édition
const isEdit = computed(() => !!props.task)

// Validation du formulaire
const isFormValid = computed(() => {
  return formData.value.title?.trim() !== '' && formData.value.priority !== ''
})

// Fonction pour initialiser les données du formulaire
const initializeFormData = () => {
  if (props.task) {
    formData.value = { 
      ...props.task,
      assigneeId: props.task.assignee?.id || null,
      // Garder les compétences comme objets
      skills: props.task.skills || []
    }
  } else {
    // Nouvelle tâche - initialiser avec des valeurs par défaut
    formData.value = {
      title: '',
      description: '',
      status: 'todo',
      priority: 'medium',
      assigneeId: null,
      skills: [],
      startDate: '',
      dueDate: '',
      estimatedHours: 0,
      actualHours: 0
    }
  }
}

// Watcher pour réagir aux changements de props
watch(() => props.task, () => {
  initializeFormData()
}, { immediate: true })

// Initialisation
onMounted(() => {
  loadAvailableUsers()
  initializeFormData()
})

// Charger les utilisateurs disponibles
const loadAvailableUsers = async () => {
  try {
    // TODO: Implémenter le service utilisateurs
    // availableUsers.value = await userService.getProjectUsers(props.project?.id)
    
    // Données de test
    availableUsers.value = [
      { id: 1, firstName: 'John', lastName: 'Doe', role: 'Développeur' },
      { id: 2, firstName: 'Jane', lastName: 'Smith', role: 'Designer' },
      { id: 3, firstName: 'Bob', lastName: 'Johnson', role: 'Manager' },
      { id: 4, firstName: 'Alice', lastName: 'Brown', role: 'Responsable' }
    ]
  } catch (error) {
    console.error('Erreur lors du chargement des utilisateurs:', error)
  }
}

// Gestion des compétences
const addSkill = () => {
  if (newSkill.value.trim()) {
    // Vérifier si la compétence existe déjà
    const skillExists = formData.value.skills.some(skill => 
      skill.name === newSkill.value.trim()
    )
    
    if (!skillExists) {
      // Ajouter la compétence comme objet (sans ID pour les nouvelles)
      formData.value.skills.push({
        id: 0, // ID temporaire, sera assigné par le backend
        name: newSkill.value.trim()
      })
      newSkill.value = ''
    }
  }
}

const removeSkill = (index: number) => {
  formData.value.skills.splice(index, 1)
}

// Soumission du formulaire
const handleSubmit = () => {
  if (isFormValid.value) {
    const taskData = {
      ...formData.value,
      // Convertir les compétences en chaînes de caractères pour le backend
      skills: formData.value.skills.map(skill => skill.name),
      updatedAt: new Date().toISOString()
    }
    
    if (isEdit.value && props.task?.id) {
      taskData.id = props.task.id
    }
    
    emit('save', taskData)
    
    // Afficher la popup de confirmation
    showSuccessMessage.value = true
    
    // Fermer la popup après 2 secondes
    setTimeout(() => {
      showSuccessMessage.value = false
    }, 2000)
  }
}

const closeModal = () => {
  emit('close')
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
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
  z-index: 2000;
  padding: 1rem;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.task-modal {
  max-width: 700px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 1.5rem 0;
  border-bottom: 1px solid #e5e7eb;
  margin-bottom: 1.5rem;
}

.modal-header h2 {
  margin: 0;
  color: #1f2937;
  font-size: 1.5rem;
  font-weight: 600;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #6b7280;
  padding: 0.25rem;
  border-radius: 4px;
  transition: all 0.2s;
}

.close-btn:hover {
  background: #f3f4f6;
  color: #374151;
}

.task-form {
  padding: 0 1.5rem 1.5rem;
}

.form-section {
  margin-bottom: 2rem;
}

.form-section h3 {
  margin: 0 0 1rem 0;
  color: #374151;
  font-size: 1.1rem;
  font-weight: 600;
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 0.5rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: #374151;
  font-weight: 500;
  font-size: 0.9rem;
}

.form-input,
.form-textarea,
.form-select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.9rem;
  transition: all 0.2s ease;
}

.form-input:focus,
.form-textarea:focus,
.form-select:focus {
  outline: none;
  border-color: #0079bf;
  box-shadow: 0 0 0 3px rgba(0, 121, 191, 0.1);
}

.form-textarea {
  resize: vertical;
  min-height: 80px;
}

.skills-container {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.skills-input {
  display: flex;
  gap: 0.5rem;
}

.skills-input .form-input {
  flex: 1;
}

.add-skill-btn {
  padding: 0.75rem;
  background: #0079bf;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.add-skill-btn:hover {
  background: #005a8b;
}

.skills-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.skill-tag {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: #e0e7ff;
  color: #3730a3;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
}

.remove-skill {
  background: none;
  border: none;
  color: #3730a3;
  cursor: pointer;
  font-size: 1rem;
  padding: 0;
  width: 16px;
  height: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: all 0.2s ease;
}

.remove-skill:hover {
  background: #c7d2fe;
  color: #1e1b4b;
}

.history-list {
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 0.5rem;
}

.history-item {
  display: grid;
  grid-template-columns: 1fr 2fr 1fr;
  gap: 1rem;
  padding: 0.5rem;
  border-bottom: 1px solid #f3f4f6;
  font-size: 0.8rem;
}

.history-item:last-child {
  border-bottom: none;
}

.history-date {
  color: #6b7280;
  font-weight: 500;
}

.history-action {
  color: #374151;
}

.history-user {
  color: #6b7280;
  text-align: right;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
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
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover {
  background: #e5e7eb;
}

/* Responsive */
@media (max-width: 768px) {
  .modal-content {
    margin: 1rem;
    max-width: calc(100vw - 2rem);
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .modal-actions {
    flex-direction: column;
  }
  
  .btn {
    width: 100%;
  }
  
  .history-item {
    grid-template-columns: 1fr;
    gap: 0.25rem;
  }
  
  .history-user {
    text-align: left;
  }
}

/* Popup de confirmation */
.success-popup {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  animation: fadeIn 0.3s ease-in-out;
}

.success-content {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  text-align: center;
  max-width: 400px;
  animation: slideIn 0.3s ease-out;
}

.success-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.success-message {
  font-size: 1.1rem;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 0.5rem;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
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
</style>
