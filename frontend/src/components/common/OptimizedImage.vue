<template>
  <div class="optimized-image-container" :style="containerStyle">
    <img
      ref="imageRef"
      :class="imageClasses"
      :alt="alt"
      :data-src="src"
      :style="imageStyle"
      @load="onImageLoad"
      @error="onImageError"
    />
    <div v-if="showPlaceholder" class="image-placeholder">
      <div class="placeholder-content">
        <svg v-if="!hasError" class="placeholder-icon" viewBox="0 0 24 24">
          <path
            fill="currentColor"
            d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"
          />
        </svg>
        <svg v-else class="placeholder-icon error" viewBox="0 0 24 24">
          <path
            fill="currentColor"
            d="M21 5v6.59l-3-3.01-4 4.01-4-4-4 4-3-3.01V5c0-1.1.9-2 2-2h14c1.1 0 2 .9 2 2zm-3 6.42l3 3.01V19c0 1.1-.9 2-2 2H5c-1.1 0-2-.9-2-2v-6.58l3 2.99 4-4 4 4 4-3.99z"
          />
        </svg>
        <span class="placeholder-text">{{
          hasError ? 'Erreur de chargement' : 'Chargement...'
        }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useLazyLoading } from '@/composables/usePerformance'

interface Props {
  src: string
  alt: string
  width?: number | string
  height?: number | string
  lazy?: boolean
  placeholder?: string
  fit?: 'cover' | 'contain' | 'fill' | 'scale-down' | 'none'
  quality?: 'low' | 'medium' | 'high'
  priority?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  lazy: true,
  fit: 'cover',
  quality: 'medium',
  priority: false,
})

const emit = defineEmits<{
  load: [event: Event]
  error: [event: Event]
}>()

const imageRef = ref<HTMLImageElement>()
const isLoaded = ref(false)
const hasError = ref(false)
const showPlaceholder = ref(true)

const { observeImage, disconnect } = useLazyLoading()

const containerStyle = computed(() => ({
  width: typeof props.width === 'number' ? `${props.width}px` : props.width,
  height: typeof props.height === 'number' ? `${props.height}px` : props.height,
  position: 'relative',
  overflow: 'hidden',
  backgroundColor: '#f3f4f6',
}))

const imageStyle = computed(() => ({
  width: '100%',
  height: '100%',
  objectFit: props.fit,
  transition: 'opacity 0.3s ease-in-out',
  opacity: isLoaded.value ? 1 : 0,
}))

const imageClasses = computed(() => [
  'optimized-image',
  {
    lazy: props.lazy && !props.priority,
    loaded: isLoaded.value,
    error: hasError.value,
  },
])

const onImageLoad = (event: Event) => {
  isLoaded.value = true
  showPlaceholder.value = false
  emit('load', event)
}

const onImageError = (event: Event) => {
  hasError.value = true
  showPlaceholder.value = true
  emit('error', event)
}

onMounted(async () => {
  await nextTick()

  if (imageRef.value) {
    if (props.priority || !props.lazy) {
      // Chargement immédiat pour les images prioritaires
      imageRef.value.src = props.src
    } else {
      // Lazy loading pour les autres images
      observeImage(imageRef.value)
    }
  }
})

onUnmounted(() => {
  disconnect()
})
</script>

<style scoped>
.optimized-image-container {
  position: relative;
  background-color: #f3f4f6;
  border-radius: 8px;
  overflow: hidden;
}

.optimized-image {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: opacity 0.3s ease-in-out;
}

.optimized-image.lazy {
  opacity: 0;
}

.optimized-image.loaded {
  opacity: 1;
}

.image-placeholder {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f9fafb;
  color: #6b7280;
}

.placeholder-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.placeholder-icon {
  width: 24px;
  height: 24px;
  opacity: 0.5;
}

.placeholder-icon.error {
  color: #ef4444;
}

.placeholder-text {
  font-size: 12px;
  font-weight: 500;
  text-align: center;
}

/* Animations */
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.optimized-image.loaded {
  animation: fadeIn 0.3s ease-in-out;
}

/* Responsive */
@media (max-width: 768px) {
  .placeholder-icon {
    width: 20px;
    height: 20px;
  }

  .placeholder-text {
    font-size: 11px;
  }
}
</style>
