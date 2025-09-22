/**
 * Configuration des optimisations de performance
 */

export const PERFORMANCE_CONFIG = {
  // Configuration du lazy loading
  lazyLoading: {
    // Délai avant le chargement des images lazy
    imageLoadDelay: 200,
    // Marge pour déclencher le chargement (en pixels)
    rootMargin: '50px 0px',
    // Seuil de visibilité pour déclencher le chargement
    threshold: 0.1,
  },

  // Configuration du cache
  cache: {
    // Taille maximale du cache en mémoire
    maxSize: 100,
    // Durée de vie par défaut des entrées de cache (en ms)
    defaultTTL: 5 * 60 * 1000, // 5 minutes
    // Durée de vie du cache API (en ms)
    apiCacheTTL: 2 * 60 * 1000, // 2 minutes
    // Durée de vie du cache des images (en ms)
    imageCacheTTL: 30 * 60 * 1000, // 30 minutes
  },

  // Configuration de la précharge
  preloading: {
    // Précharger les routes critiques
    criticalRoutes: ['/dashboard', '/projects', '/tasks', '/profile'],
    // Précharger les images critiques
    criticalImages: ['/images/logo.png', '/images/hero-bg.jpg'],
    // Précharger les polices
    criticalFonts: [
      'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
    ],
  },

  // Configuration PWA
  pwa: {
    // Stratégie de cache pour les API
    apiCacheStrategy: 'NetworkFirst',
    // Stratégie de cache pour les assets statiques
    staticCacheStrategy: 'CacheFirst',
    // Durée de vie du cache des assets (en secondes)
    staticCacheMaxAge: 60 * 60 * 24 * 365, // 1 an
    // Durée de vie du cache API (en secondes)
    apiCacheMaxAge: 60 * 5, // 5 minutes
    // Timeout pour les requêtes réseau (en secondes)
    networkTimeout: 3,
  },

  // Configuration des optimisations de rendu
  rendering: {
    // Utiliser requestIdleCallback pour les tâches non critiques
    useIdleCallback: true,
    // Délai pour les tâches différées (en ms)
    idleCallbackTimeout: 5000,
    // Utiliser Intersection Observer pour le lazy loading
    useIntersectionObserver: true,
    // Utiliser ResizeObserver pour les optimisations de layout
    useResizeObserver: true,
  },

  // Configuration des optimisations de bundle
  bundle: {
    // Taille maximale des chunks (en KB)
    maxChunkSize: 1000,
    // Seuil d'avertissement pour la taille des chunks
    chunkSizeWarningLimit: 500,
    // Activer le code splitting automatique
    enableAutoCodeSplitting: true,
    // Activer le tree shaking
    enableTreeShaking: true,
    // Activer la minification
    enableMinification: true,
  },

  // Configuration des optimisations d'images
  images: {
    // Formats d'image supportés (par ordre de préférence)
    supportedFormats: ['webp', 'avif', 'jpg', 'png'],
    // Qualités d'image disponibles
    qualities: {
      low: 60,
      medium: 80,
      high: 95,
    },
    // Tailles d'image prédéfinies
    sizes: {
      thumbnail: '150x150',
      small: '300x300',
      medium: '600x600',
      large: '1200x1200',
    },
    // Activer le lazy loading par défaut
    enableLazyLoading: true,
    // Activer la compression automatique
    enableCompression: true,
  },

  // Configuration des optimisations de réseau
  network: {
    // Activer la compression Gzip
    enableGzip: true,
    // Activer la compression Brotli
    enableBrotli: true,
    // Seuil de compression (en bytes)
    compressionThreshold: 1024,
    // Ratio de compression minimum
    minCompressionRatio: 0.8,
    // Activer HTTP/2 Server Push
    enableServerPush: true,
    // Activer la préconnexion DNS
    enableDNSPrefetch: true,
  },

  // Configuration des optimisations de développement
  development: {
    // Activer le hot module replacement
    enableHMR: true,
    // Activer les source maps
    enableSourceMaps: true,
    // Activer les devtools Vue
    enableVueDevtools: true,
    // Activer le mode strict
    enableStrictMode: true,
  },

  // Configuration des optimisations de production
  production: {
    // Désactiver les console.log
    removeConsoleLogs: true,
    // Désactiver les debugger
    removeDebugger: true,
    // Activer la minification CSS
    minifyCSS: true,
    // Activer la minification HTML
    minifyHTML: true,
    // Activer la compression des assets
    compressAssets: true,
    // Activer les optimisations de performance
    enablePerformanceOptimizations: true,
  },

  // Configuration des métriques de performance
  metrics: {
    // Activer le tracking des Core Web Vitals
    enableCoreWebVitals: true,
    // Activer le tracking des métriques personnalisées
    enableCustomMetrics: true,
    // Seuils de performance
    thresholds: {
      // First Contentful Paint (en ms)
      FCP: 1800,
      // Largest Contentful Paint (en ms)
      LCP: 2500,
      // First Input Delay (en ms)
      FID: 100,
      // Cumulative Layout Shift
      CLS: 0.1,
      // Time to Interactive (en ms)
      TTI: 3800,
    },
  },
}

/**
 * Configuration des optimisations par environnement
 */
export const ENVIRONMENT_CONFIG = {
  development: {
    ...PERFORMANCE_CONFIG,
    development: {
      ...PERFORMANCE_CONFIG.development,
      enableHMR: true,
      enableSourceMaps: true,
      enableVueDevtools: true,
    },
    production: {
      ...PERFORMANCE_CONFIG.production,
      removeConsoleLogs: false,
      removeDebugger: false,
      minifyCSS: false,
      minifyHTML: false,
    },
  },

  production: {
    ...PERFORMANCE_CONFIG,
    development: {
      ...PERFORMANCE_CONFIG.development,
      enableHMR: false,
      enableSourceMaps: false,
      enableVueDevtools: false,
    },
    production: {
      ...PERFORMANCE_CONFIG.production,
      removeConsoleLogs: true,
      removeDebugger: true,
      minifyCSS: true,
      minifyHTML: true,
    },
  },
}

/**
 * Fonction pour obtenir la configuration selon l'environnement
 */
export function getPerformanceConfig(env: 'development' | 'production' = 'development') {
  return ENVIRONMENT_CONFIG[env] || ENVIRONMENT_CONFIG.development
}

/**
 * Fonction pour vérifier si une optimisation est activée
 */
export function isOptimizationEnabled(
  optimization: string,
  env: 'development' | 'production' = 'development',
): boolean {
  const config = getPerformanceConfig(env)

  switch (optimization) {
    case 'lazyLoading':
      return config.lazyLoading !== undefined
    case 'pwa':
      return config.pwa !== undefined
    case 'compression':
      return config.network.enableGzip || config.network.enableBrotli
    case 'minification':
      return config.production.minifyCSS && config.production.minifyHTML
    case 'codeSplitting':
      return config.bundle.enableAutoCodeSplitting
    case 'treeShaking':
      return config.bundle.enableTreeShaking
    default:
      return false
  }
}

/**
 * Fonction pour obtenir les métriques de performance actuelles
 */
export function getPerformanceMetrics() {
  if (typeof window === 'undefined') {
    return null
  }

  const navigation = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming

  return {
    // Temps de chargement
    loadTime: navigation.loadEventEnd - navigation.loadEventStart,
    // Temps de DOM Content Loaded
    domContentLoaded: navigation.domContentLoadedEventEnd - navigation.domContentLoadedEventStart,
    // Temps de First Paint
    firstPaint: performance.getEntriesByName('first-paint')[0]?.startTime || 0,
    // Temps de First Contentful Paint
    firstContentfulPaint: performance.getEntriesByName('first-contentful-paint')[0]?.startTime || 0,
    // Temps de Largest Contentful Paint
    largestContentfulPaint:
      performance.getEntriesByName('largest-contentful-paint')[0]?.startTime || 0,
    // Temps de First Input Delay
    firstInputDelay: performance.getEntriesByName('first-input')[0]?.processingStart || 0,
    // Cumulative Layout Shift
    cumulativeLayoutShift: performance.getEntriesByName('layout-shift')[0]?.value || 0,
  }
}
