<template>
  <div class="lazy-image-container" :class="containerClass">
    <!-- Placeholder while loading -->
    <div 
      v-if="!loaded && !error" 
      class="image-placeholder"
      :style="placeholderStyle"
    >
      <div class="loading-spinner"></div>
      <span v-if="showPlaceholderText" class="placeholder-text">Chargement...</span>
    </div>

    <!-- Error state -->
    <div 
      v-if="error" 
      class="image-error"
      :style="errorStyle"
    >
      <div class="error-icon">⚠️</div>
      <span v-if="showErrorText" class="error-text">{{ errorText }}</span>
    </div>

    <!-- Actual image -->
    <img
      v-if="shouldShowImage"
      ref="imageElement"
      :src="actualSrc"
      :alt="alt"
      :class="imageClass"
      :style="imageStyle"
      @load="handleLoad"
      @error="handleError"
      loading="lazy"
      decoding="async"
    />

    <!-- Caption -->
    <figcaption v-if="caption" class="image-caption">
      {{ caption }}
    </figcaption>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'

interface Props {
  src: string
  alt: string
  width?: number | string
  height?: number | string
  placeholder?: string
  errorSrc?: string
  caption?: string
  containerClass?: string
  imageClass?: string
  showPlaceholderText?: boolean
  showErrorText?: boolean
  errorText?: string
  aspectRatio?: string
  blur?: boolean
  fadeIn?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  width: 'auto',
  height: 'auto',
  placeholder: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2Y3ZjdmNyIvPjx0ZXh0IHg9IjUwIiB5PSI1MCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+SW1hZ2U8L3RleHQ+PC9zdmc+',
  errorSrc: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI2Y3ZjdmNyIvPjx0ZXh0IHg9IjUwIiB5PSI1MCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiBmaWxsPSIjZGY2MzY5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+RXJyb3I8L3RleHQ+PC9zdmc+',
  showPlaceholderText: false,
  showErrorText: true,
  errorText: 'Impossible de charger l\'image',
  aspectRatio: 'auto',
  blur: true,
  fadeIn: true
})

const imageElement = ref<HTMLImageElement | null>(null)
const loaded = ref(false)
const error = ref(false)
const inView = ref(false)
const observer = ref<IntersectionObserver | null>(null)

const actualSrc = computed(() => {
  if (error.value && props.errorSrc) {
    return props.errorSrc
  }
  return props.src
})

const shouldShowImage = computed(() => {
  return inView.value && (loaded.value || error.value)
})

const placeholderStyle = computed(() => ({
  width: typeof props.width === 'number' ? `${props.width}px` : props.width,
  height: typeof props.height === 'number' ? `${props.height}px` : props.height,
  aspectRatio: props.aspectRatio !== 'auto' ? props.aspectRatio : undefined,
  backgroundImage: `url(${props.placeholder})`,
  backgroundSize: 'cover',
  backgroundPosition: 'center',
  filter: props.blur ? 'blur(5px)' : 'none'
}))

const errorStyle = computed(() => ({
  width: typeof props.width === 'number' ? `${props.width}px` : props.width,
  height: typeof props.height === 'number' ? `${props.height}px` : props.height,
  aspectRatio: props.aspectRatio !== 'auto' ? props.aspectRatio : undefined,
  backgroundImage: `url(${props.errorSrc})`,
  backgroundSize: 'cover',
  backgroundPosition: 'center'
}))

const imageStyle = computed(() => ({
  width: typeof props.width === 'number' ? `${props.width}px` : props.width,
  height: typeof props.height === 'number' ? `${props.height}px` : props.height,
  aspectRatio: props.aspectRatio !== 'auto' ? props.aspectRatio : undefined,
  opacity: props.fadeIn && loaded.value ? 1 : 0,
  transition: props.fadeIn ? 'opacity 0.3s ease-in-out' : 'none'
}))

const handleLoad = () => {
  loaded.value = true
  error.value = false
  
  if (props.fadeIn && imageElement.value) {
    nextTick(() => {
      if (imageElement.value) {
        imageElement.value.style.opacity = '1'
      }
    })
  }
}

const handleError = () => {
  error.value = true
  loaded.value = false
}

const setupIntersectionObserver = () => {
  if (!imageElement.value) return

  observer.value = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          inView.value = true
          observer.value?.disconnect()
        }
      })
    },
    {
      rootMargin: '50px',
      threshold: 0.1
    }
  )

  observer.value.observe(imageElement.value)
}

onMounted(() => {
  nextTick(() => {
    setupIntersectionObserver()
  })
})

onUnmounted(() => {
  if (observer.value) {
    observer.value.disconnect()
  }
})
</script>

<style scoped>
.lazy-image-container {
  position: relative;
  display: inline-block;
  overflow: hidden;
}

.image-placeholder,
.image-error {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background-color: #f3f4f6;
  border-radius: 4px;
  min-height: 100px;
  color: #6b7280;
}

.loading-spinner {
  width: 24px;
  height: 24px;
  border: 2px solid #e5e7eb;
  border-top: 2px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 0.5rem;
}

.placeholder-text,
.error-text {
  font-size: 0.875rem;
  text-align: center;
}

.error-icon {
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
}

.image-caption {
  font-size: 0.875rem;
  color: #6b7280;
  text-align: center;
  margin-top: 0.5rem;
  font-style: italic;
}

img {
  display: block;
  max-width: 100%;
  height: auto;
  border-radius: 4px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .image-placeholder,
  .image-error {
    min-height: 80px;
  }
  
  .loading-spinner {
    width: 20px;
    height: 20px;
  }
  
  .error-icon {
    font-size: 1.25rem;
  }
  
  .placeholder-text,
  .error-text {
    font-size: 0.75rem;
  }
}
</style>