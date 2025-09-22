import { ref, onMounted, onUnmounted } from 'vue'

/**
 * Composable pour optimiser les performances de l'application
 */
export function usePerformance() {
  const isOnline = ref(navigator.onLine)
  const connectionSpeed = ref<'slow' | 'fast' | 'unknown'>('unknown')
  const isLowEndDevice = ref(false)

  // Détection de la vitesse de connexion
  const detectConnectionSpeed = () => {
    if ('connection' in navigator) {
      const connection = (navigator as Navigator & { connection?: { effectiveType?: string } })
        .connection
      if (connection) {
        const effectiveType = connection.effectiveType
        if (effectiveType === 'slow-2g' || effectiveType === '2g') {
          connectionSpeed.value = 'slow'
        } else if (effectiveType === '3g' || effectiveType === '4g') {
          connectionSpeed.value = 'fast'
        }
      }
    }
  }

  // Détection d'appareil bas de gamme
  const detectLowEndDevice = () => {
    const memory = (navigator as Navigator & { deviceMemory?: number }).deviceMemory
    const cores = navigator.hardwareConcurrency

    // Considérer comme bas de gamme si moins de 4GB RAM ou moins de 4 cores
    isLowEndDevice.value = (memory && memory < 4) || (cores && cores < 4)
  }

  // Gestion des événements de connexion
  const handleOnline = () => {
    isOnline.value = true
  }

  const handleOffline = () => {
    isOnline.value = false
  }

  onMounted(() => {
    detectConnectionSpeed()
    detectLowEndDevice()

    window.addEventListener('online', handleOnline)
    window.addEventListener('offline', handleOffline)
  })

  onUnmounted(() => {
    window.removeEventListener('online', handleOnline)
    window.removeEventListener('offline', handleOffline)
  })

  return {
    isOnline,
    connectionSpeed,
    isLowEndDevice,
  }
}

/**
 * Composable pour le lazy loading d'images
 */
export function useLazyLoading() {
  const observer = ref<IntersectionObserver | null>(null)
  const loadedImages = ref(new Set<string>())

  const createObserver = () => {
    if (typeof IntersectionObserver === 'undefined') {
      return null
    }

    return new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const img = entry.target as HTMLImageElement
            const src = img.dataset.src

            if (src && !loadedImages.value.has(src)) {
              img.src = src
              img.classList.remove('lazy')
              img.classList.add('loaded')
              loadedImages.value.add(src)
              observer.value?.unobserve(img)
            }
          }
        })
      },
      {
        rootMargin: '50px 0px',
        threshold: 0.1,
      },
    )
  }

  const observeImage = (img: HTMLImageElement) => {
    if (!observer.value) {
      observer.value = createObserver()
    }

    if (observer.value) {
      observer.value.observe(img)
    }
  }

  const disconnect = () => {
    if (observer.value) {
      observer.value.disconnect()
    }
  }

  return {
    observeImage,
    disconnect,
    loadedImages,
  }
}

/**
 * Composable pour la précharge de ressources critiques
 */
export function usePreloading() {
  const preloadedResources = ref(new Set<string>())

  const preloadImage = (src: string): Promise<void> => {
    return new Promise((resolve, reject) => {
      if (preloadedResources.value.has(src)) {
        resolve()
        return
      }

      const img = new Image()
      img.onload = () => {
        preloadedResources.value.add(src)
        resolve()
      }
      img.onerror = reject
      img.src = src
    })
  }

  const preloadScript = (src: string): Promise<void> => {
    return new Promise((resolve, reject) => {
      if (preloadedResources.value.has(src)) {
        resolve()
        return
      }

      const link = document.createElement('link')
      link.rel = 'preload'
      link.as = 'script'
      link.href = src
      link.onload = () => {
        preloadedResources.value.add(src)
        resolve()
      }
      link.onerror = () => reject(new Error(`Failed to preload script: ${src}`))
      document.head.appendChild(link)
    })
  }

  const preloadStylesheet = (href: string): Promise<void> => {
    return new Promise((resolve, reject) => {
      if (preloadedResources.value.has(href)) {
        resolve()
        return
      }

      const link = document.createElement('link')
      link.rel = 'preload'
      link.as = 'style'
      link.href = href
      link.onload = () => {
        preloadedResources.value.add(href)
        resolve()
      }
      link.onerror = () => reject(new Error(`Failed to preload stylesheet: ${href}`))
      document.head.appendChild(link)
    })
  }

  return {
    preloadImage,
    preloadScript,
    preloadStylesheet,
    preloadedResources,
  }
}

/**
 * Composable pour la gestion du cache
 */
export function useCache() {
  const cache = ref(new Map<string, { value: unknown; timestamp: number; ttl: number }>())
  const maxCacheSize = 100

  const set = (key: string, value: unknown, ttl?: number) => {
    // Nettoyer le cache si nécessaire
    if (cache.value.size >= maxCacheSize) {
      const firstKey = cache.value.keys().next().value
      cache.value.delete(firstKey)
    }

    const item = {
      value,
      timestamp: Date.now(),
      ttl: ttl || 5 * 60 * 1000, // 5 minutes par défaut
    }

    cache.value.set(key, item)
  }

  const get = (key: string) => {
    const item = cache.value.get(key)

    if (!item) {
      return null
    }

    // Vérifier l'expiration
    if (Date.now() - item.timestamp > item.ttl) {
      cache.value.delete(key)
      return null
    }

    return item.value
  }

  const clear = () => {
    cache.value.clear()
  }

  const has = (key: string) => {
    const item = cache.value.get(key)
    return item && Date.now() - item.timestamp <= item.ttl
  }

  return {
    set,
    get,
    clear,
    has,
    cache,
  }
}
