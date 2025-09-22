import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'

interface SEOMeta {
  title?: string
  description?: string
  keywords?: string
  ogTitle?: string
  ogDescription?: string
  ogImage?: string
  ogUrl?: string
  twitterTitle?: string
  twitterDescription?: string
  twitterImage?: string
  canonical?: string
}

const defaultSEO = {
  title: 'TaskForce - Gestion de Projets et Équipes',
  description:
    "TaskForce est une plateforme moderne de gestion de projets et d'équipes. Organisez vos tâches, gérez vos équipes et suivez vos projets en temps réel.",
  keywords:
    'gestion de projets, équipes, tâches, collaboration, productivité, management, organisation, planning, suivi',
  ogTitle: 'TaskForce - Gestion de Projets et Équipes',
  ogDescription:
    "Plateforme moderne de gestion de projets et d'équipes. Organisez vos tâches et collaborez efficacement.",
  ogImage: 'https://taskforce.app/og-image.jpg',
  ogUrl: 'https://taskforce.app',
  twitterTitle: 'TaskForce - Gestion de Projets et Équipes',
  twitterDescription:
    "Plateforme moderne de gestion de projets et d'équipes. Organisez vos tâches et collaborez efficacement.",
  twitterImage: 'https://taskforce.app/og-image.jpg',
}

export function useSEO() {
  const route = useRoute()
  const currentSEO = ref<SEOMeta>({ ...defaultSEO })

  const updateSEO = (meta: SEOMeta) => {
    currentSEO.value = { ...defaultSEO, ...meta }
    applySEOTags()
  }

  const applySEOTags = () => {
    const {
      title,
      description,
      keywords,
      ogTitle,
      ogDescription,
      ogImage,
      ogUrl,
      twitterTitle,
      twitterDescription,
      twitterImage,
      canonical,
    } = currentSEO.value

    // Update document title
    if (title) {
      document.title = title
    }

    // Update meta tags
    updateMetaTag('description', description)
    updateMetaTag('keywords', keywords)
    updateMetaTag('author', 'TaskForce Team')
    updateMetaTag('robots', 'index, follow')

    // Open Graph tags
    updateMetaTag('og:title', ogTitle, 'property')
    updateMetaTag('og:description', ogDescription, 'property')
    updateMetaTag('og:image', ogImage, 'property')
    updateMetaTag('og:url', ogUrl || window.location.href, 'property')
    updateMetaTag('og:type', 'website', 'property')
    updateMetaTag('og:site_name', 'TaskForce', 'property')
    updateMetaTag('og:locale', 'fr_FR', 'property')

    // Twitter tags
    updateMetaTag('twitter:card', 'summary_large_image', 'name')
    updateMetaTag('twitter:title', twitterTitle, 'name')
    updateMetaTag('twitter:description', twitterDescription, 'name')
    updateMetaTag('twitter:image', twitterImage, 'name')
    updateMetaTag('twitter:url', ogUrl || window.location.href, 'name')

    // Canonical URL
    if (canonical) {
      updateLinkTag('canonical', canonical)
    }

    // Update Google Analytics
    if (typeof gtag !== 'undefined') {
      gtag('config', 'GA_MEASUREMENT_ID', {
        page_title: title,
        page_location: window.location.href,
      })
    }
  }

  const updateMetaTag = (name: string, content: string | undefined, attribute: string = 'name') => {
    if (!content) return

    let meta = document.querySelector(`meta[${attribute}="${name}"]`) as HTMLMetaElement
    if (!meta) {
      meta = document.createElement('meta')
      meta.setAttribute(attribute, name)
      document.head.appendChild(meta)
    }
    meta.setAttribute('content', content)
  }

  const updateLinkTag = (rel: string, href: string) => {
    let link = document.querySelector(`link[rel="${rel}"]`) as HTMLLinkElement
    if (!link) {
      link = document.createElement('link')
      link.setAttribute('rel', rel)
      document.head.appendChild(link)
    }
    link.setAttribute('href', href)
  }

  // Auto-update SEO based on route
  watch(
    route,
    (newRoute) => {
      const routeSEO = getRouteSEO(newRoute.name as string, newRoute.path)
      updateSEO(routeSEO)
    },
    { immediate: true },
  )

  const getRouteSEO = (routeName: string | undefined, path: string): SEOMeta => {
    const baseUrl = 'https://taskforce.app'

    switch (routeName) {
      case 'home':
        return {
          title: 'TaskForce - Plateforme de Gestion de Projets et Équipes',
          description:
            'Découvrez TaskForce, la solution complète pour gérer vos projets et équipes. Organisez, planifiez et suivez vos tâches en temps réel.',
          keywords:
            'gestion de projets, équipes, tâches, collaboration, productivité, management, organisation, planning, suivi, plateforme',
          canonical: baseUrl,
        }

      case 'login':
        return {
          title: 'Connexion - TaskForce',
          description:
            'Connectez-vous à votre espace TaskForce pour accéder à vos projets et équipes.',
          keywords: 'connexion, login, TaskForce, accès, authentification',
          canonical: `${baseUrl}/login`,
        }

      case 'signup':
        return {
          title: 'Inscription - TaskForce',
          description:
            "Créez votre compte TaskForce et commencez à gérer vos projets efficacement dès aujourd'hui.",
          keywords: 'inscription, compte, TaskForce, création, nouveau utilisateur',
          canonical: `${baseUrl}/register`,
        }

      case 'dashboard':
        return {
          title: 'Tableau de Bord - TaskForce',
          description:
            "Accédez à votre tableau de bord TaskForce pour suivre l'avancement de vos projets et tâches.",
          keywords: 'tableau de bord, dashboard, projets, tâches, suivi, avancement',
          canonical: `${baseUrl}/dashboard`,
        }

      case 'tasks':
        return {
          title: 'Gestion des Tâches - TaskForce',
          description:
            "Organisez et gérez toutes vos tâches avec TaskForce. Créez, assignez et suivez l'avancement de vos tâches.",
          keywords: 'tâches, gestion, organisation, assignation, suivi, avancement',
          canonical: `${baseUrl}/tasks`,
        }

      case 'projects':
        return {
          title: 'Gestion des Projets - TaskForce',
          description:
            'Planifiez et gérez vos projets avec TaskForce. Créez des projets, organisez les équipes et suivez les délais.',
          keywords: 'projets, gestion, planification, équipes, délais, organisation',
          canonical: `${baseUrl}/projects`,
        }

      case 'reports':
        return {
          title: 'Rapports et Analytics - TaskForce',
          description:
            'Analysez les performances de vos équipes et projets avec les rapports détaillés TaskForce.',
          keywords: 'rapports, analytics, performances, statistiques, analyse, équipes',
          canonical: `${baseUrl}/reports`,
        }

      case 'admin':
        return {
          title: 'Administration - TaskForce',
          description:
            "Panel d'administration TaskForce pour gérer les utilisateurs, rôles et paramètres de l'organisation.",
          keywords: 'administration, gestion utilisateurs, rôles, paramètres, organisation',
          canonical: `${baseUrl}/admin`,
        }

      default:
        return {
          title: 'TaskForce - Gestion de Projets et Équipes',
          description: "TaskForce est une plateforme moderne de gestion de projets et d'équipes.",
          canonical: baseUrl + path,
        }
    }
  }

  return {
    currentSEO,
    updateSEO,
    applySEOTags,
  }
}

// Analytics helper functions
export function trackEvent(eventName: string, parameters?: Record<string, any>) {
  if (typeof gtag !== 'undefined') {
    gtag('event', eventName, parameters)
  }
}

export function trackPageView(pageTitle: string, pageLocation: string) {
  if (typeof gtag !== 'undefined') {
    gtag('config', 'GA_MEASUREMENT_ID', {
      page_title: pageTitle,
      page_location: pageLocation,
    })
  }
}

// Declare gtag function for TypeScript
declare global {
  function gtag(...args: any[]): void
}
