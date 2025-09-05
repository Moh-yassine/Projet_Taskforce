<template>
  <div class="modal-overlay" @click="closeModal">
    <div class="modal-content assignment-modal" @click.stop>
      <div class="modal-header">
        <h2>Assigner la tâche</h2>
        <button @click="closeModal" class="close-btn">&times;</button>
      </div>
      
      <div class="modal-body">
        <div class="task-info">
          <h3>{{ task?.title }}</h3>
          <p>{{ task?.description }}</p>
        </div>
        
        <div class="assignment-section">
          <h4>Assigner à un collaborateur</h4>
          
          <div class="user-selection">
            <div 
              v-for="user in availableUsers" 
              :key="user.id"
              @click="selectUser(user)"
              :class="[
                'user-card',
                { 'selected': selectedUser?.id === user.id }
              ]"
            >
              <div class="user-avatar">
                <img v-if="user.avatar" :src="user.avatar" :alt="user.firstName" />
                <div v-else class="avatar-placeholder">
                  {{ getInitials(user.firstName, user.lastName) }}
                </div>
              </div>
              <div class="user-info">
                <div class="user-name">{{ user.firstName }} {{ user.lastName }}</div>
                <div class="user-role">{{ user.role }}</div>
                <div class="user-skills">
                  <span 
                    v-for="skill in user.skills.slice(0, 3)" 
                    :key="skill.id"
                    class="skill-tag"
                  >
                    {{ skill.name }}
                  </span>
                  <span v-if="user.skills.length > 3" class="more-skills">
                    +{{ user.skills.length - 3 }}
                  </span>
                </div>
              </div>
              <div class="user-metrics">
                <div class="metric">
                  <span class="metric-label">Charge actuelle</span>
                  <span class="metric-value">{{ user.currentWorkload }}%</span>
                </div>
                <div class="metric">
                  <span class="metric-label">Disponibilité</span>
                  <span class="metric-value" :class="getAvailabilityClass(user.availability)">
                    {{ user.availability }}%
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="assignment-reason">
          <h4>Raison de l'assignation</h4>
          <textarea
            v-model="assignmentReason"
            rows="3"
            placeholder="Expliquez pourquoi cette personne est la mieux adaptée pour cette tâche..."
            class="form-textarea"
          ></textarea>
        </div>
        
        <div class="skill-match" v-if="selectedUser">
          <h4>Correspondance des compétences</h4>
          <div class="match-analysis">
            <div class="match-score">
              <div class="score-circle" :class="getMatchClass(skillMatchScore)">
                {{ skillMatchScore }}%
              </div>
              <span>Correspondance</span>
            </div>
            <div class="match-details">
              <div class="match-item">
                <span class="match-label">Compétences requises:</span>
                <span class="match-value">{{ task?.skills?.length || 0 }}</span>
              </div>
              <div class="match-item">
                <span class="match-label">Compétences correspondantes:</span>
                <span class="match-value">{{ matchingSkills.length }}</span>
              </div>
              <div class="match-item">
                <span class="match-label">Compétences manquantes:</span>
                <span class="match-value">{{ missingSkills.length }}</span>
              </div>
            </div>
          </div>
          
          <div class="skills-comparison">
            <div class="skills-section">
              <h5>Compétences requises</h5>
              <div class="skills-list">
                <span 
                  v-for="skill in task?.skills || []" 
                  :key="skill"
                  :class="[
                    'skill-tag',
                    { 'matched': matchingSkills.includes(skill) }
                  ]"
                >
                  {{ skill }}
                </span>
              </div>
            </div>
            
            <div class="skills-section">
              <h5>Compétences du collaborateur</h5>
              <div class="skills-list">
                <span 
                  v-for="skill in selectedUser.skills" 
                  :key="skill.id"
                  :class="[
                    'skill-tag',
                    { 'matched': matchingSkills.includes(skill.name) }
                  ]"
                >
                  {{ skill.name }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="modal-actions">
        <button @click="closeModal" class="btn btn-secondary">
          Annuler
        </button>
        <button 
          @click="handleAssignment" 
          class="btn btn-primary"
          :disabled="!selectedUser"
        >
          Assigner la tâche
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Task {
  id: number
  title: string
  description: string
  skills: string[]
}

interface Skill {
  id: number
  name: string
  level: number
}

interface User {
  id: number
  firstName: string
  lastName: string
  role: string
  avatar?: string
  skills: Skill[]
  currentWorkload: number
  availability: number
}

interface Props {
  task?: Task | null
  project?: any
}

interface Emits {
  (e: 'close'): void
  (e: 'assign', data: { userId: number, reason: string }): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// État du composant
const availableUsers = ref<User[]>([])
const selectedUser = ref<User | null>(null)
const assignmentReason = ref('')

// Calculs
const matchingSkills = computed(() => {
  if (!selectedUser.value || !props.task?.skills) return []
  
  return props.task.skills.filter(taskSkill =>
    selectedUser.value!.skills.some(userSkill => 
      userSkill.name.toLowerCase() === taskSkill.toLowerCase()
    )
  )
})

const missingSkills = computed(() => {
  if (!selectedUser.value || !props.task?.skills) return props.task?.skills || []
  
  return props.task.skills.filter(taskSkill =>
    !selectedUser.value!.skills.some(userSkill => 
      userSkill.name.toLowerCase() === taskSkill.toLowerCase()
    )
  )
})

const skillMatchScore = computed(() => {
  if (!selectedUser.value || !props.task?.skills?.length) return 0
  
  const matchCount = matchingSkills.value.length
  const totalRequired = props.task.skills.length
  
  return Math.round((matchCount / totalRequired) * 100)
})

// Initialisation
onMounted(() => {
  loadAvailableUsers()
})

// Charger les utilisateurs disponibles
const loadAvailableUsers = async () => {
  try {
    // TODO: Implémenter le service utilisateurs
    // availableUsers.value = await userService.getProjectUsers(props.project?.id)
    
    // Données de test
    availableUsers.value = [
      {
        id: 1,
        firstName: 'John',
        lastName: 'Doe',
        role: 'Développeur',
        skills: [
          { id: 1, name: 'Vue.js', level: 8 },
          { id: 2, name: 'TypeScript', level: 7 },
          { id: 3, name: 'CSS', level: 6 }
        ],
        currentWorkload: 60,
        availability: 40
      },
      {
        id: 2,
        firstName: 'Jane',
        lastName: 'Smith',
        role: 'Designer',
        skills: [
          { id: 4, name: 'UI/UX', level: 9 },
          { id: 5, name: 'Figma', level: 8 },
          { id: 6, name: 'Photoshop', level: 7 }
        ],
        currentWorkload: 30,
        availability: 70
      },
      {
        id: 3,
        firstName: 'Bob',
        lastName: 'Johnson',
        role: 'Manager',
        skills: [
          { id: 7, name: 'Gestion de projet', level: 9 },
          { id: 8, name: 'Agile', level: 8 },
          { id: 9, name: 'Communication', level: 9 }
        ],
        currentWorkload: 80,
        availability: 20
      },
      {
        id: 4,
        firstName: 'Alice',
        lastName: 'Brown',
        role: 'Responsable',
        skills: [
          { id: 10, name: 'Stratégie', level: 9 },
          { id: 11, name: 'Leadership', level: 8 },
          { id: 12, name: 'Analyse', level: 7 }
        ],
        currentWorkload: 50,
        availability: 50
      }
    ]
  } catch (error) {
    console.error('Erreur lors du chargement des utilisateurs:', error)
  }
}

// Méthodes
const selectUser = (user: User) => {
  selectedUser.value = user
}

const getInitials = (firstName: string, lastName: string) => {
  return (firstName.charAt(0) + lastName.charAt(0)).toUpperCase()
}

const getAvailabilityClass = (availability: number) => {
  if (availability >= 70) return 'high'
  if (availability >= 40) return 'medium'
  return 'low'
}

const getMatchClass = (score: number) => {
  if (score >= 80) return 'excellent'
  if (score >= 60) return 'good'
  if (score >= 40) return 'fair'
  return 'poor'
}

const handleAssignment = () => {
  if (selectedUser.value) {
    emit('assign', {
      userId: selectedUser.value.id,
      reason: assignmentReason.value
    })
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

.assignment-modal {
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

.modal-body {
  padding: 0 1.5rem;
}

.task-info {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

.task-info h3 {
  margin: 0 0 0.5rem 0;
  color: #1f2937;
  font-size: 1.1rem;
  font-weight: 600;
}

.task-info p {
  margin: 0;
  color: #6b7280;
  font-size: 0.9rem;
}

.assignment-section {
  margin-bottom: 1.5rem;
}

.assignment-section h4 {
  margin: 0 0 1rem 0;
  color: #374151;
  font-size: 1rem;
  font-weight: 600;
}

.user-selection {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1rem;
}

.user-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.user-card:hover {
  border-color: #0079bf;
  background: #f8f9fa;
}

.user-card.selected {
  border-color: #0079bf;
  background: #e6f3ff;
}

.user-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
}

.user-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  width: 100%;
  height: 100%;
  background: #0079bf;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.9rem;
}

.user-info {
  flex: 1;
  min-width: 0;
}

.user-name {
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.user-role {
  font-size: 0.8rem;
  color: #6b7280;
  margin-bottom: 0.5rem;
}

.user-skills {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
}

.skill-tag {
  padding: 0.125rem 0.375rem;
  background: #e0e7ff;
  color: #3730a3;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 500;
}

.skill-tag.matched {
  background: #d1fae5;
  color: #065f46;
}

.more-skills {
  font-size: 0.7rem;
  color: #6b7280;
}

.user-metrics {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  min-width: 100px;
}

.metric {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.metric-label {
  font-size: 0.7rem;
  color: #6b7280;
  margin-bottom: 0.25rem;
}

.metric-value {
  font-size: 0.9rem;
  font-weight: 600;
  color: #1f2937;
}

.metric-value.high {
  color: #059669;
}

.metric-value.medium {
  color: #d97706;
}

.metric-value.low {
  color: #dc2626;
}

.assignment-reason {
  margin-bottom: 1.5rem;
}

.assignment-reason h4 {
  margin: 0 0 0.5rem 0;
  color: #374151;
  font-size: 1rem;
  font-weight: 600;
}

.form-textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.9rem;
  resize: vertical;
  min-height: 80px;
}

.form-textarea:focus {
  outline: none;
  border-color: #0079bf;
  box-shadow: 0 0 0 3px rgba(0, 121, 191, 0.1);
}

.skill-match {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

.skill-match h4 {
  margin: 0 0 1rem 0;
  color: #374151;
  font-size: 1rem;
  font-weight: 600;
}

.match-analysis {
  display: flex;
  align-items: center;
  gap: 2rem;
  margin-bottom: 1rem;
}

.match-score {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.score-circle {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.9rem;
  color: white;
}

.score-circle.excellent {
  background: #059669;
}

.score-circle.good {
  background: #0891b2;
}

.score-circle.fair {
  background: #d97706;
}

.score-circle.poor {
  background: #dc2626;
}

.match-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.match-item {
  display: flex;
  justify-content: space-between;
  font-size: 0.9rem;
}

.match-label {
  color: #6b7280;
}

.match-value {
  font-weight: 600;
  color: #1f2937;
}

.skills-comparison {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.skills-section h5 {
  margin: 0 0 0.5rem 0;
  color: #374151;
  font-size: 0.9rem;
  font-weight: 600;
}

.skills-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
  padding: 1rem 1.5rem 1.5rem;
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
  
  .user-selection {
    grid-template-columns: 1fr;
  }
  
  .user-card {
    flex-direction: column;
    text-align: center;
  }
  
  .user-metrics {
    flex-direction: row;
    justify-content: space-around;
    width: 100%;
  }
  
  .match-analysis {
    flex-direction: column;
    gap: 1rem;
  }
  
  .skills-comparison {
    grid-template-columns: 1fr;
  }
  
  .modal-actions {
    flex-direction: column;
  }
  
  .btn {
    width: 100%;
  }
}
</style>
