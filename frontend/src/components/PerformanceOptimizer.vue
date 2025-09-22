<template>
  <div class="performance-optimizer">
    <!-- Preload critical resources -->
    <template v-if="preloadResources.length">
      <link
        v-for="resource in preloadResources"
        :key="resource.href"
        rel="preload"
        :href="resource.href"
        :as="resource.as"
        :type="resource.type"
        :crossorigin="resource.crossorigin"
      />
    </template>

    <!-- Prefetch non-critical resources -->
    <template v-if="prefetchResources.length">
      <link
        v-for="resource in prefetchResources"
        :key="resource.href"
        rel="prefetch"
        :href="resource.href"
        :as="resource.as"
        :type="resource.type"
      />
    </template>

    <!-- DNS prefetch for external domains -->
    <template v-if="dnsPrefetchDomains.length">
      <link v-for="domain in dnsPrefetchDomains" :key="domain" rel="dns-prefetch" :href="domain" />
    </template>

    <!-- Performance monitoring -->
    <div v-if="showPerformanceMetrics" class="performance-metrics">
      <div class="metric">
        <span class="label">FCP:</span>
        <span class="value">{{ metrics.fcp }}ms</span>
      </div>
      <div class="metric">
        <span class="label">LCP:</span>
        <span class="value">{{ metrics.lcp }}ms</span>
      </div>
      <div class="metric">
        <span class="label">CLS:</span>
        <span class="value">{{ metrics.cls }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { analyticsService } from '../services/analyticsService'

interface Resource {
  href: string
  as: string
  type?: string
  crossorigin?: string
}

interface Props {
  preloadResources?: Resource[]
  prefetchResources?: Resource[]
  dnsPrefetchDomains?: string[]
  showPerformanceMetrics?: boolean
  enableResourceHints?: boolean
  enableLazyLoading?: boolean
  enableImageOptimization?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  preloadResources: () => [],
  prefetchResources: () => [],
  dnsPrefetchDomains: () => [
    'https://fonts.googleapis.com',
    'https://fonts.gstatic.com',
    'https://www.googletagmanager.com',
    'https://www.google-analytics.com',
  ],
  showPerformanceMetrics: false,
  enableResourceHints: true,
  enableLazyLoading: true,
  enableImageOptimization: true,
})

const metrics = ref({
  fcp: 0,
  lcp: 0,
  cls: 0,
  fid: 0,
  ttfb: 0,
})

const observer = ref<PerformanceObserver | null>(null)

onMounted(() => {
  if (props.enableResourceHints) {
    setupResourceHints()
  }

  if (props.showPerformanceMetrics) {
    setupPerformanceMonitoring()
  }

  if (props.enableLazyLoading) {
    setupLazyLoading()
  }

  if (props.enableImageOptimization) {
    optimizeImages()
  }
})

onUnmounted(() => {
  if (observer.value) {
    observer.value.disconnect()
  }
})

const setupResourceHints = () => {
  // Critical CSS preload
  const criticalCSS = document.createElement('link')
  criticalCSS.rel = 'preload'
  criticalCSS.href = '/src/assets/main.css'
  criticalCSS.as = 'style'
  criticalCSS.onload = () => {
    criticalCSS.rel = 'stylesheet'
  }
  document.head.appendChild(criticalCSS)

  // Critical JavaScript preload
  const criticalJS = document.createElement('link')
  criticalJS.rel = 'preload'
  criticalJS.href = '/src/main.ts'
  criticalJS.as = 'script'
  document.head.appendChild(criticalJS)
}

const setupPerformanceMonitoring = () => {
  // Performance Observer for navigation timing
  if ('PerformanceObserver' in window) {
    observer.value = new PerformanceObserver((list) => {
      list.getEntries().forEach((entry) => {
        switch (entry.name) {
          case 'first-contentful-paint':
            metrics.value.fcp = Math.round(entry.startTime)
            analyticsService.trackPerformance('FCP', entry.startTime)
            break
          case 'largest-contentful-paint':
            metrics.value.lcp = Math.round(entry.startTime)
            analyticsService.trackPerformance('LCP', entry.startTime)
            break
          case 'layout-shift':
            metrics.value.cls += entry.value
            analyticsService.trackPerformance('CLS', entry.value)
            break
        }
      })
    })

    try {
      // Use individual entry types to avoid compatibility issues
      if ('paint' in PerformanceObserver.supportedEntryTypes) {
        observer.value.observe({ entryTypes: ['paint'] })
      }
      if ('largest-contentful-paint' in PerformanceObserver.supportedEntryTypes) {
        observer.value.observe({ entryTypes: ['largest-contentful-paint'] })
      }
      if ('layout-shift' in PerformanceObserver.supportedEntryTypes) {
        observer.value.observe({ entryTypes: ['layout-shift'] })
      }
    } catch (error) {
      console.warn('Performance Observer not supported:', error)
      setupBasicPerformanceMonitoring()
    }
  } else {
    setupBasicPerformanceMonitoring()
  }

  // Navigation timing
  window.addEventListener('load', () => {
    const navigation = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming
    if (navigation) {
      metrics.value.ttfb = Math.round(navigation.responseStart - navigation.requestStart)
      analyticsService.trackPerformance('TTFB', metrics.value.ttfb)
    }
  })
}

const setupBasicPerformanceMonitoring = () => {
  // Fallback performance monitoring without PerformanceObserver
  window.addEventListener('load', () => {
    const navigation = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming
    if (navigation) {
      const loadTime = navigation.loadEventEnd - navigation.fetchStart
      const domContentLoaded = navigation.domContentLoadedEventEnd - navigation.fetchStart

      metrics.value.fcp = Math.round(loadTime)
      metrics.value.lcp = Math.round(loadTime)
      metrics.value.ttfb = Math.round(navigation.responseStart - navigation.requestStart)

      analyticsService.trackPerformance('Page Load Time', loadTime)
      analyticsService.trackPerformance('DOM Content Loaded', domContentLoaded)
    }
  })
}

const setupLazyLoading = () => {
  // Intersection Observer for lazy loading
  if ('IntersectionObserver' in window) {
    const lazyObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const element = entry.target as HTMLElement

            // Lazy load images
            if (element.tagName === 'IMG' && element.dataset.src) {
              element.setAttribute('src', element.dataset.src)
              element.removeAttribute('data-src')
              lazyObserver.unobserve(element)
            }

            // Lazy load background images
            if (element.dataset.bg) {
              element.style.backgroundImage = `url(${element.dataset.bg})`
              element.removeAttribute('data-bg')
              lazyObserver.unobserve(element)
            }
          }
        })
      },
      {
        rootMargin: '50px 0px',
        threshold: 0.01,
      },
    )

    // Observe all lazy elements
    document.querySelectorAll('[data-src], [data-bg]').forEach((el) => {
      lazyObserver.observe(el)
    })
  }
}

const optimizeImages = () => {
  // Convert images to WebP if supported
  if (supportsWebP()) {
    document.querySelectorAll('img[data-webp]').forEach((img) => {
      const webpSrc = img.getAttribute('data-webp')
      if (webpSrc) {
        img.setAttribute('src', webpSrc)
      }
    })
  }

  // Add responsive image attributes
  document.querySelectorAll('img').forEach((img) => {
    if (!img.hasAttribute('loading')) {
      img.setAttribute('loading', 'lazy')
    }

    if (!img.hasAttribute('decoding')) {
      img.setAttribute('decoding', 'async')
    }
  })
}

const supportsWebP = (): boolean => {
  const canvas = document.createElement('canvas')
  canvas.width = 1
  canvas.height = 1
  return canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0
}

// Utility function to preload a resource
const preloadResource = (href: string, as: string, type?: string) => {
  const link = document.createElement('link')
  link.rel = 'preload'
  link.href = href
  link.as = as
  if (type) link.type = type
  document.head.appendChild(link)
}

// Utility function to prefetch a resource
const prefetchResource = (href: string, as: string) => {
  const link = document.createElement('link')
  link.rel = 'prefetch'
  link.href = href
  link.as = as
  document.head.appendChild(link)
}

// Expose utility functions
defineExpose({
  preloadResource,
  prefetchResource,
  metrics,
})
</script>

<style scoped>
.performance-optimizer {
  position: absolute;
  top: 0;
  left: 0;
  width: 0;
  height: 0;
  overflow: hidden;
  pointer-events: none;
}

.performance-metrics {
  position: fixed;
  top: 10px;
  right: 10px;
  background: rgba(0, 0, 0, 0.8);
  color: white;
  padding: 10px;
  border-radius: 5px;
  font-family: monospace;
  font-size: 12px;
  z-index: 1000;
  pointer-events: auto;
}

.metric {
  display: flex;
  justify-content: space-between;
  margin-bottom: 5px;
}

.metric:last-child {
  margin-bottom: 0;
}

.label {
  margin-right: 10px;
}

.value {
  color: #4ade80;
}

/* Hide metrics in production */
@media (prefers-reduced-motion: reduce) {
  .performance-metrics {
    display: none;
  }
}
</style>
