/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_API_BASE_URL: string
  readonly BASE_URL: string
  // more env variables...
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}

// Extensions pour les types de performance
interface PerformanceEntry {
  processingStart?: number
  value?: number
}

// Extensions pour les types de navigateur
interface Navigator {
  deviceMemory?: number
  connection?: {
    effectiveType?: string
  }
}

// Types pour les modules manquants
declare module '@/services/*' {
  const content: any
  export default content
}

declare module '@/composables/*' {
  const content: any
  export default content
}

declare module '@/components/*' {
  const content: any
  export default content
}

declare module '@/views/*' {
  const content: any
  export default content
}

// Types pour les plugins Vite
declare module '@vitejs/plugin-vue' {
  const content: any
  export default content
}

declare module '@vitejs/plugin-legacy' {
  const content: any
  export default content
}

declare module 'vite-plugin-pwa' {
  const content: any
  export const VitePWA: any
}

declare module 'vite-plugin-compression' {
  const content: any
  export default content
}

declare module 'rollup-plugin-visualizer' {
  const content: any
  export const visualizer: any
}

declare module 'vite-plugin-checker' {
  const content: any
  export default content
}
