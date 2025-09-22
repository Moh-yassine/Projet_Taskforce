// SEO Configuration for TaskForce
export interface SEOConfig {
  title: string
  description: string
  keywords: string[]
  author: string
  robots: string
  canonical: string
  ogTitle?: string
  ogDescription?: string
  ogImage?: string
  ogType?: string
  ogUrl?: string
  twitterCard?: string
  twitterTitle?: string
  twitterDescription?: string
  twitterImage?: string
  twitterSite?: string
  twitterCreator?: string
}

export const defaultSEOConfig: SEOConfig = {
  title: 'TaskForce - Gestion de Projets et Tâches',
  description: 'TaskForce est une plateforme de gestion de projets et de tâches collaborative. Organisez vos équipes, suivez vos projets et améliorez votre productivité.',
  keywords: [
    'gestion de projets',
    'gestion de tâches',
    'collaboration',
    'équipe',
    'productivité',
    'organisation',
    'planification',
    'suivi de projet',
    'TaskForce',
    'outil de gestion'
  ],
  author: 'TaskForce Team',
  robots: 'index, follow',
  canonical: 'https://taskforce.app',
  ogTitle: 'TaskForce - Gestion de Projets et Tâches',
  ogDescription: 'Plateforme collaborative de gestion de projets et de tâches pour améliorer la productivité de votre équipe.',
  ogImage: 'https://taskforce.app/og-image.jpg',
  ogType: 'website',
  ogUrl: 'https://taskforce.app',
  twitterCard: 'summary_large_image',
  twitterTitle: 'TaskForce - Gestion de Projets et Tâches',
  twitterDescription: 'Plateforme collaborative de gestion de projets et de tâches.',
  twitterImage: 'https://taskforce.app/twitter-image.jpg',
  twitterSite: '@TaskForceApp',
  twitterCreator: '@TaskForceApp'
}

// Page-specific SEO configurations
export const pageSEOConfigs: Record<string, Partial<SEOConfig>> = {
  home: {
    title: 'TaskForce - Accueil',
    description: 'Découvrez TaskForce, la plateforme de gestion de projets qui révolutionne la collaboration en équipe.',
    keywords: ['accueil', 'TaskForce', 'gestion de projets', 'collaboration', 'équipe']
  },
  login: {
    title: 'Connexion - TaskForce',
    description: 'Connectez-vous à votre compte TaskForce pour accéder à vos projets et tâches.',
    robots: 'noindex, nofollow'
  },
  signup: {
    title: 'Inscription - TaskForce',
    description: 'Créez votre compte TaskForce et commencez à gérer vos projets efficacement.',
    robots: 'noindex, nofollow'
  },
  dashboard: {
    title: 'Tableau de bord - TaskForce',
    description: 'Votre tableau de bord TaskForce pour suivre vos projets et tâches en cours.',
    robots: 'noindex, nofollow'
  },
  projects: {
    title: 'Mes Projets - TaskForce',
    description: 'Gérez et suivez tous vos projets TaskForce depuis un seul endroit.',
    robots: 'noindex, nofollow'
  },
  tasks: {
    title: 'Mes Tâches - TaskForce',
    description: 'Consultez et gérez vos tâches assignées dans TaskForce.',
    robots: 'noindex, nofollow'
  },
  premium: {
    title: 'Fonctionnalités Premium - TaskForce',
    description: 'Débloquez toutes les fonctionnalités avancées de TaskForce avec notre plan Premium.',
    keywords: ['premium', 'fonctionnalités avancées', 'TaskForce Pro', 'abonnement']
  },
  reports: {
    title: 'Rapports - TaskForce',
    description: 'Analysez les performances de vos projets avec les rapports détaillés TaskForce.',
    robots: 'noindex, nofollow'
  }
}

// Utility functions for SEO
export const generatePageTitle = (pageName: string, baseTitle?: string): string => {
  const config = pageSEOConfigs[pageName]
  if (config?.title) {
    return config.title
  }
  return baseTitle || defaultSEOConfig.title
}

export const generatePageDescription = (pageName: string, baseDescription?: string): string => {
  const config = pageSEOConfigs[pageName]
  if (config?.description) {
    return config.description
  }
  return baseDescription || defaultSEOConfig.description
}

export const generatePageKeywords = (pageName: string, additionalKeywords: string[] = []): string[] => {
  const config = pageSEOConfigs[pageName]
  const baseKeywords = config?.keywords || defaultSEOConfig.keywords
  return [...baseKeywords, ...additionalKeywords]
}

export const generateCanonicalUrl = (path: string): string => {
  const baseUrl = defaultSEOConfig.canonical
  return `${baseUrl}${path.startsWith('/') ? path : `/${path}`}`
}

// Structured data schemas
export const generateOrganizationSchema = () => ({
  '@context': 'https://schema.org',
  '@type': 'Organization',
  name: 'TaskForce',
  url: 'https://taskforce.app',
  logo: 'https://taskforce.app/logo.png',
  description: defaultSEOConfig.description,
  sameAs: [
    'https://twitter.com/TaskForceApp',
    'https://linkedin.com/company/taskforce'
  ]
})

export const generateSoftwareApplicationSchema = () => ({
  '@context': 'https://schema.org',
  '@type': 'SoftwareApplication',
  name: 'TaskForce',
  applicationCategory: 'BusinessApplication',
  operatingSystem: 'Web',
  description: defaultSEOConfig.description,
  url: 'https://taskforce.app',
  author: {
    '@type': 'Organization',
    name: defaultSEOConfig.author
  },
  offers: {
    '@type': 'Offer',
    price: '0',
    priceCurrency: 'EUR'
  }
})

export const generateBreadcrumbSchema = (breadcrumbs: Array<{name: string, url: string}>) => ({
  '@context': 'https://schema.org',
  '@type': 'BreadcrumbList',
  itemListElement: breadcrumbs.map((crumb, index) => ({
    '@type': 'ListItem',
    position: index + 1,
    name: crumb.name,
    item: crumb.url
  }))
})

export const generateFAQSchema = (faqs: Array<{question: string, answer: string}>) => ({
  '@context': 'https://schema.org',
  '@type': 'FAQPage',
  mainEntity: faqs.map(faq => ({
    '@type': 'Question',
    name: faq.question,
    acceptedAnswer: {
      '@type': 'Answer',
      text: faq.answer
    }
  }))
})