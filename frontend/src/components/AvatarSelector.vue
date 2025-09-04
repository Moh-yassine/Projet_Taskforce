<template>
  <div class="avatar-selector">
    <div class="avatar-preview">
      <div v-if="selectedAvatar" class="avatar-display">
        <img :src="selectedAvatar.url" :alt="`Avatar ${selectedAvatar.style}`" class="avatar-image" />
      </div>
      <div v-else class="avatar-placeholder">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
          <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
        </svg>
      </div>
    </div>

    <button @click="openAvatarSelection" class="btn-avatar-select">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
      </svg>
      {{ selectedAvatar ? 'Changer d\'avatar' : 'Choisir un avatar' }}
    </button>

    <!-- Modal de sélection d'avatar -->
    <div v-if="isModalOpen" class="modal-overlay" @click="closeModal">
      <div class="modal-content avatar-modal" @click.stop>
        <div class="modal-header">
          <h2>Choisissez votre avatar</h2>
          <button @click="closeModal" class="close-btn">&times;</button>
        </div>
        
        <div class="modal-body">
          <p class="modal-description">Sélectionnez un avatar parmi notre collection</p>
          
          <div class="avatar-grid">
            <div 
              v-for="(avatar, index) in avatars" 
              :key="index" 
              @click="selectAvatar(avatar)" 
              :class="[
                'avatar-option',
                selectedAvatar?.url === avatar.url ? 'selected' : ''
              ]"
            >
              <img :src="avatar.url" :alt="`Avatar ${avatar.style}`" class="avatar-option-img" />
              <div v-if="selectedAvatar?.url === avatar.url" class="selected-indicator">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
              </div>
            </div>
          </div>
        </div>
        
        <div class="modal-actions">
          <button @click="closeModal" class="btn btn-secondary">
            Annuler
          </button>
          <button @click="confirmSelection" class="btn btn-primary" :disabled="!selectedAvatar">
            Confirmer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'

interface Avatar {
  url: string
  style: string
  seed: string
}

interface Props {
  modelValue?: string
}

interface Emits {
  (e: 'update:modelValue', value: string): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

const avatarStyles = [
  'avataaars',
  'big-smile',
  'bottts',
  'identicon',
  'initials',
  'lorelei',
  'micah',
  'miniavs',
  'pixel-art',
  'thumbs'
]

const avatars = ref<Avatar[]>([])
const selectedAvatar = ref<Avatar | null>(null)
const isModalOpen = ref(false)

const generateRandomSeed = () => {
  return Math.random().toString(36).substring(2, 15)
}

const openAvatarSelection = () => {
  if (avatars.value.length === 0) {
    generateAvatars()
  }
  isModalOpen.value = true
}

const closeModal = () => {
  isModalOpen.value = false
}

const confirmSelection = () => {
  if (selectedAvatar.value) {
    emit('update:modelValue', selectedAvatar.value.url)
  }
  isModalOpen.value = false
}

const generateAvatars = () => {
  const newAvatars: Avatar[] = []
  const maxAvatars = 20

  const avatarsPerStyle = Math.ceil(maxAvatars / avatarStyles.length)

  avatarStyles.forEach(style => {
    for (let i = 0; i < avatarsPerStyle && newAvatars.length < maxAvatars; i++) {
      const seed = generateRandomSeed()
      newAvatars.push({
        url: `https://api.dicebear.com/9.x/${style}/svg?seed=${seed}`,
        style: style,
        seed: seed
      })
    }
  })

  avatars.value = newAvatars.sort(() => Math.random() - 0.5)
}

const selectAvatar = (avatar: Avatar) => {
  selectedAvatar.value = avatar
}

onMounted(() => {
  if (props.modelValue) {
    selectedAvatar.value = {
      url: props.modelValue,
      style: 'unknown',
      seed: 'unknown'
    }
  }
})

watch(() => props.modelValue, (newValue) => {
  if (newValue && selectedAvatar.value?.url !== newValue) {
    selectedAvatar.value = {
      url: newValue,
      style: 'unknown',
      seed: 'unknown'
    }
  } else if (!newValue) {
    selectedAvatar.value = null
  }
})
</script>

<style scoped>
.avatar-selector {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.avatar-preview {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  overflow: hidden;
  border: 3px solid #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--primary-color);
}

.avatar-display {
  width: 100%;
  height: 100%;
}

.avatar-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-avatar-select {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: #f3f4f6;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  color: #374151;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-avatar-select:hover {
  background: #e5e7eb;
  border-color: #9ca3af;
}

/* Modal styles */
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
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.avatar-modal {
  max-width: 500px;
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

.modal-description {
  color: #6b7280;
  margin-bottom: 1.5rem;
  text-align: center;
}

.avatar-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.75rem;
  margin-bottom: 1.5rem;
  max-height: 300px;
  overflow-y: auto;
  padding: 0.5rem;
}

.avatar-option {
  position: relative;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  overflow: hidden;
  cursor: pointer;
  border: 3px solid transparent;
  transition: all 0.2s ease;
}

.avatar-option:hover {
  border-color: var(--primary-color);
  transform: scale(1.05);
}

.avatar-option.selected {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px rgba(0, 121, 191, 0.2);
}

.avatar-option-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.selected-indicator {
  position: absolute;
  top: -2px;
  right: -2px;
  background: var(--primary-color);
  color: white;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
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
  background: var(--primary-color);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-hover);
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
  .avatar-grid {
    grid-template-columns: repeat(4, 1fr);
    gap: 0.5rem;
  }
  
  .avatar-option {
    width: 50px;
    height: 50px;
  }
  
  .modal-content {
    margin: 1rem;
    max-width: calc(100vw - 2rem);
  }
  
  .modal-actions {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .btn {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .avatar-grid {
    grid-template-columns: repeat(3, 1fr);
  }
  
  .avatar-option {
    width: 45px;
    height: 45px;
  }
  
  .modal-header h2 {
    font-size: 1.25rem;
  }
  
  .modal-description {
    font-size: 0.9rem;
  }
}
</style>
