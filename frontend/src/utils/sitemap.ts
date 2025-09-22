// Sitemap generation utilities for TaskForce

export interface SitemapUrl {
  loc: string
  lastmod: string
  changefreq: 'always' | 'hourly' | 'daily' | 'weekly' | 'monthly' | 'yearly' | 'never'
  priority: number
}

export interface SitemapConfig {
  baseUrl: string
  urls: SitemapUrl[]
}

// Default sitemap configuration
export const defaultSitemapConfig: SitemapConfig = {
  baseUrl: 'https://taskforce.app',
  urls: [
    {
      loc: '/',
      lastmod: new Date().toISOString().split('T')[0],
      changefreq: 'daily',
      priority: 1.0
    },
    {
      loc: '/login',
      lastmod: new Date().toISOString().split('T')[0],
      changefreq: 'monthly',
      priority: 0.8
    },
    {
      loc: '/signup',
      lastmod: new Date().toISOString().split('T')[0],
      changefreq: 'monthly',
      priority: 0.8
    },
    {
      loc: '/dashboard',
      lastmod: new Date().toISOString().split('T')[0],
      changefreq: 'daily',
      priority: 0.9
    },
    {
      loc: '/tasks',
      lastmod: new Date().toISOString().split('T')[0],
      changefreq: 'daily',
      priority: 0.7
    },
    {
      loc: '/my-tasks',
      lastmod: new Date().toISOString().split('T')[0],
      changefreq: 'daily',
      priority: 0.7
    },
    {
      loc: '/premium',
      lastmod: new Date().toISOString().split('T')[0],
      changefreq: 'weekly',
      priority: 0.6
    },
    {
      loc: '/reports',
      lastmod: new Date().toISOString().split('T')[0],
      changefreq: 'weekly',
      priority: 0.5
    }
  ]
}

// Generate XML sitemap
export const generateSitemapXML = (config: SitemapConfig = defaultSitemapConfig): string => {
  const urls = config.urls.map(url => `
  <url>
    <loc>${config.baseUrl}${url.loc}</loc>
    <lastmod>${url.lastmod}</lastmod>
    <changefreq>${url.changefreq}</changefreq>
    <priority>${url.priority}</priority>
  </url>`).join('')

  return `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">${urls}
</urlset>`
}

// Generate robots.txt content
export const generateRobotsTxt = (baseUrl: string = 'https://taskforce.app'): string => {
  return `User-agent: *
Allow: /

# Sitemap
Sitemap: ${baseUrl}/sitemap.xml

# Crawl-delay for respectful crawling
Crawl-delay: 1

# Disallow admin areas
Disallow: /admin/
Disallow: /api/
Disallow: /_nuxt/
Disallow: /dist/

# Allow important pages
Allow: /dashboard
Allow: /projects
Allow: /tasks

# Block specific file types
Disallow: *.json
Disallow: *.xml
Disallow: *.txt

# Allow search engines to index images
Allow: /*.jpg
Allow: /*.jpeg
Allow: /*.png
Allow: /*.gif
Allow: /*.webp
Allow: /*.svg

# Allow CSS and JS files
Allow: /*.css
Allow: /*.js`
}

// Add dynamic project URLs to sitemap
export const addProjectUrls = (projects: Array<{id: number, name: string, updatedAt: string}>, config: SitemapConfig): SitemapConfig => {
  const projectUrls: SitemapUrl[] = projects.map(project => ({
    loc: `/project/${project.id}/tasks`,
    lastmod: new Date(project.updatedAt).toISOString().split('T')[0],
    changefreq: 'weekly',
    priority: 0.6
  }))

  return {
    ...config,
    urls: [...config.urls, ...projectUrls]
  }
}

// Add dynamic task URLs to sitemap
export const addTaskUrls = (tasks: Array<{id: number, title: string, updatedAt: string}>, config: SitemapConfig): SitemapConfig => {
  const taskUrls: SitemapUrl[] = tasks.map(task => ({
    loc: `/task/${task.id}`,
    lastmod: new Date(task.updatedAt).toISOString().split('T')[0],
    changefreq: 'weekly',
    priority: 0.5
  }))

  return {
    ...config,
    urls: [...config.urls, ...taskUrls]
  }
}

// Update sitemap with current date
export const updateSitemapDates = (config: SitemapConfig, path?: string): SitemapConfig => {
  const today = new Date().toISOString().split('T')[0]
  
  if (path) {
    return {
      ...config,
      urls: config.urls.map(url => 
        url.loc === path ? { ...url, lastmod: today } : url
      )
    }
  }

  return {
    ...config,
    urls: config.urls.map(url => ({ ...url, lastmod: today }))
  }
}

// Validate sitemap URL
export const validateSitemapUrl = (url: SitemapUrl): boolean => {
  return !!(
    url.loc &&
    url.lastmod &&
    url.changefreq &&
    url.priority >= 0 &&
    url.priority <= 1
  )
}

// Filter URLs for authenticated pages
export const filterPublicUrls = (config: SitemapConfig): SitemapConfig => {
  const publicPaths = ['/', '/login', '/signup', '/premium']
  
  return {
    ...config,
    urls: config.urls.filter(url => publicPaths.includes(url.loc))
  }
}

// Generate sitemap index for large sites
export const generateSitemapIndex = (sitemaps: Array<{loc: string, lastmod: string}>): string => {
  const sitemapEntries = sitemaps.map(sitemap => `
  <sitemap>
    <loc>${sitemap.loc}</loc>
    <lastmod>${sitemap.lastmod}</lastmod>
  </sitemap>`).join('')

  return `<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">${sitemapEntries}
</sitemapindex>`
}

// Utility to save sitemap to file (for Node.js environments)
export const saveSitemapToFile = async (config: SitemapConfig, filePath: string): Promise<void> => {
  if (typeof window === 'undefined') {
    // Node.js environment
    const fs = await import('fs')
    const sitemapXML = generateSitemapXML(config)
    fs.writeFileSync(filePath, sitemapXML, 'utf8')
  } else {
    // Browser environment - trigger download
    const sitemapXML = generateSitemapXML(config)
    const blob = new Blob([sitemapXML], { type: 'application/xml' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = 'sitemap.xml'
    link.click()
    URL.revokeObjectURL(url)
  }
}