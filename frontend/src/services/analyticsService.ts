// Analytics Service for TaskForce
export class AnalyticsService {
  private static instance: AnalyticsService
  private isInitialized = false
  private measurementId = 'G-M292NPQBQB' // Google Analytics 4 Measurement ID
  private gtmId = 'GTM-KDFJSSTN' // Google Tag Manager ID

  private constructor() {}

  public static getInstance(): AnalyticsService {
    if (!AnalyticsService.instance) {
      AnalyticsService.instance = new AnalyticsService()
    }
    return AnalyticsService.instance
  }

  public initialize(): void {
    if (this.isInitialized) return

    // Initialize Google Analytics 4
    this.initializeGA4()

    // Initialize Google Tag Manager
    this.initializeGTM()

    // Initialize performance monitoring
    this.initializePerformanceMonitoring()

    this.isInitialized = true
  }

  private initializeGA4(): void {
    // Load GA4 script
    const script = document.createElement('script')
    script.async = true
    script.src = `https://www.googletagmanager.com/gtag/js?id=${this.measurementId}`
    document.head.appendChild(script)

    // Initialize gtag
    window.dataLayer = window.dataLayer || []
    function gtag(...args: any[]) {
      window.dataLayer.push(args)
    }
    window.gtag = gtag

    gtag('js', new Date())
    gtag('config', this.measurementId, {
      page_title: document.title,
      page_location: window.location.href,
      send_page_view: true,
      // Enhanced measurement settings
      enhanced_measurement: true,
      // Privacy settings
      anonymize_ip: true,
      allow_google_signals: false,
      allow_ad_personalization_signals: false,
    })
  }

  private initializeGTM(): void {
    // GTM script is already loaded in index.html
    // This method can be used for additional GTM configuration
  }

  private initializePerformanceMonitoring(): void {
    // Core Web Vitals monitoring
    this.loadWebVitals()
  }

  private async loadWebVitals(): Promise<void> {
    try {
      // Dynamic import for web-vitals in browser environment
      const webVitals = await import('web-vitals')

      // Check if the functions exist before calling them
      if (typeof webVitals.getCLS === 'function') {
        webVitals.getCLS(this.sendWebVitalToGA.bind(this))
      }
      if (typeof webVitals.getFID === 'function') {
        webVitals.getFID(this.sendWebVitalToGA.bind(this))
      }
      if (typeof webVitals.getFCP === 'function') {
        webVitals.getFCP(this.sendWebVitalToGA.bind(this))
      }
      if (typeof webVitals.getLCP === 'function') {
        webVitals.getLCP(this.sendWebVitalToGA.bind(this))
      }
      if (typeof webVitals.getTTFB === 'function') {
        webVitals.getTTFB(this.sendWebVitalToGA.bind(this))
      }
    } catch (error) {
      console.warn('Failed to load Web Vitals:', error)
      // Fallback: basic performance monitoring without web-vitals
      this.setupBasicPerformanceMonitoring()
    }
  }

  private setupBasicPerformanceMonitoring(): void {
    // Basic performance monitoring fallback
    window.addEventListener('load', () => {
      const navigation = performance.getEntriesByType(
        'navigation',
      )[0] as PerformanceNavigationTiming
      if (navigation) {
        const loadTime = navigation.loadEventEnd - navigation.fetchStart
        this.trackPerformance('Page Load Time', loadTime)
      }
    })
  }

  private sendWebVitalToGA(metric: any): void {
    if (typeof gtag !== 'undefined') {
      gtag('event', metric.name, {
        value: Math.round(metric.value),
        event_label: metric.id,
        non_interaction: true,
        custom_map: {
          metric_id: metric.id,
          metric_value: metric.value,
          metric_delta: metric.delta,
        },
      })
    }
  }

  // Public methods for tracking events
  public trackPageView(pageTitle: string, pageLocation: string): void {
    if (typeof gtag !== 'undefined') {
      gtag('config', this.measurementId, {
        page_title: pageTitle,
        page_location: pageLocation,
      })
    }
  }

  public trackEvent(eventName: string, parameters?: Record<string, any>): void {
    if (typeof gtag !== 'undefined') {
      gtag('event', eventName, parameters)
    }
  }

  public trackUserAction(action: string, category: string, label?: string, value?: number): void {
    this.trackEvent(action, {
      event_category: category,
      event_label: label,
      value: value,
    })
  }

  public trackTaskAction(
    action: 'create' | 'update' | 'complete' | 'delete',
    taskId?: string,
    projectId?: string,
  ): void {
    this.trackEvent('task_action', {
      action_type: action,
      task_id: taskId,
      project_id: projectId,
      event_category: 'task_management',
    })
  }

  public trackProjectAction(
    action: 'create' | 'update' | 'delete' | 'archive',
    projectId?: string,
  ): void {
    this.trackEvent('project_action', {
      action_type: action,
      project_id: projectId,
      event_category: 'project_management',
    })
  }

  public trackUserEngagement(feature: string, action: string, duration?: number): void {
    this.trackEvent('user_engagement', {
      feature_name: feature,
      engagement_action: action,
      engagement_duration: duration,
      event_category: 'user_behavior',
    })
  }

  public trackError(errorType: string, errorMessage: string, errorLocation?: string): void {
    this.trackEvent('error_occurred', {
      error_type: errorType,
      error_message: errorMessage,
      error_location: errorLocation,
      event_category: 'errors',
    })
  }

  public trackPerformance(metric: string, value: number, unit: string = 'ms'): void {
    this.trackEvent('performance_metric', {
      metric_name: metric,
      metric_value: value,
      metric_unit: unit,
      event_category: 'performance',
    })
  }

  // Conversion tracking
  public trackConversion(conversionType: string, value?: number, currency?: string): void {
    this.trackEvent('conversion', {
      conversion_type: conversionType,
      value: value,
      currency: currency,
      event_category: 'conversions',
    })
  }

  public trackPremiumUpgrade(planType: string, value: number): void {
    this.trackConversion('premium_upgrade', value, 'EUR')
    this.trackEvent('purchase', {
      transaction_id: `upgrade_${Date.now()}`,
      value: value,
      currency: 'EUR',
      items: [
        {
          item_id: planType,
          item_name: `Premium ${planType}`,
          category: 'subscription',
          quantity: 1,
          price: value,
        },
      ],
    })
  }

  // Custom dimensions (requires GA4 configuration)
  public setCustomDimension(dimensionIndex: number, value: string): void {
    if (typeof gtag !== 'undefined') {
      gtag('config', this.measurementId, {
        [`custom_map_${dimensionIndex}`]: value,
      })
    }
  }

  // User properties
  public setUserProperty(property: string, value: string): void {
    if (typeof gtag !== 'undefined') {
      gtag('config', this.measurementId, {
        user_properties: {
          [property]: value,
        },
      })
    }
  }

  // Enhanced ecommerce tracking
  public trackPurchase(
    transactionId: string,
    items: Array<{
      item_id: string
      item_name: string
      category: string
      quantity: number
      price: number
    }>,
    value: number,
    currency: string = 'EUR',
  ): void {
    this.trackEvent('purchase', {
      transaction_id: transactionId,
      value: value,
      currency: currency,
      items: items,
    })
  }
}

// Export singleton instance
export const analyticsService = AnalyticsService.getInstance()

// Utility functions
export function trackEvent(eventName: string, parameters?: Record<string, any>): void {
  analyticsService.trackEvent(eventName, parameters)
}

export function trackPageView(pageTitle: string, pageLocation: string): void {
  analyticsService.trackPageView(pageTitle, pageLocation)
}

export function trackUserAction(
  action: string,
  category: string,
  label?: string,
  value?: number,
): void {
  analyticsService.trackUserAction(action, category, label, value)
}

// Declare global types
declare global {
  interface Window {
    dataLayer: any[]
    gtag: (...args: any[]) => void
  }
}
