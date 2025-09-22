<template>
  <div class="seo-headings">
    <!-- Main heading structure -->
    <h1 v-if="headings.h1" class="main-heading" :class="headings.h1.class">
      {{ headings.h1.text }}
    </h1>
    
    <h2 v-if="headings.h2" class="sub-heading" :class="headings.h2.class">
      {{ headings.h2.text }}
    </h2>
    
    <h3 v-if="headings.h3" class="section-heading" :class="headings.h3.class">
      {{ headings.h3.text }}
    </h3>
    
    <h4 v-if="headings.h4" class="subsection-heading" :class="headings.h4.class">
      {{ headings.h4.text }}
    </h4>
    
    <h5 v-if="headings.h5" class="detail-heading" :class="headings.h5.class">
      {{ headings.h5.text }}
    </h5>
    
    <h6 v-if="headings.h6" class="minor-heading" :class="headings.h6.class">
      {{ headings.h6.text }}
    </h6>

    <!-- Breadcrumb navigation -->
    <nav v-if="breadcrumbs.length > 0" class="breadcrumb-nav" aria-label="Breadcrumb">
      <ol class="breadcrumb-list">
        <li v-for="(crumb, index) in breadcrumbs" :key="index" class="breadcrumb-item">
          <router-link 
            v-if="crumb.to && index < breadcrumbs.length - 1" 
            :to="crumb.to" 
            class="breadcrumb-link"
          >
            {{ crumb.text }}
          </router-link>
          <span v-else class="breadcrumb-current">{{ crumb.text }}</span>
          <span v-if="index < breadcrumbs.length - 1" class="breadcrumb-separator">/</span>
        </li>
      </ol>
    </nav>

    <!-- Page description -->
    <p v-if="description" class="page-description">
      {{ description }}
    </p>

    <!-- Schema.org structured data for headings -->
    <script 
      v-if="schemaData" 
      type="application/ld+json"
      v-html="JSON.stringify(schemaData)"
    ></script>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface HeadingData {
  text: string
  class?: string
}

interface BreadcrumbItem {
  text: string
  to?: string
}

const props = defineProps<{
  h1?: HeadingData
  h2?: HeadingData
  h3?: HeadingData
  h4?: HeadingData
  h5?: HeadingData
  h6?: HeadingData
  breadcrumbs?: BreadcrumbItem[]
  description?: string
  schema?: any
}>()

const headings = computed(() => ({
  h1: props.h1,
  h2: props.h2,
  h3: props.h3,
  h4: props.h4,
  h5: props.h5,
  h6: props.h6
}))

const breadcrumbs = computed(() => props.breadcrumbs || [])
const description = computed(() => props.description)

const schemaData = computed(() => {
  if (!props.schema) return null
  
  // Auto-generate schema for breadcrumbs if not provided
  if (breadcrumbs.value.length > 0 && !props.schema.breadcrumbList) {
    return {
      ...props.schema,
      breadcrumbList: {
        "@type": "BreadcrumbList",
        "itemListElement": breadcrumbs.value.map((crumb, index) => ({
          "@type": "ListItem",
          "position": index + 1,
          "name": crumb.text,
          "item": crumb.to ? `${window.location.origin}${crumb.to}` : undefined
        }))
      }
    }
  }
  
  return props.schema
})
</script>

<style scoped>
.seo-headings {
  margin-bottom: 2rem;
}

/* Heading styles */
.main-heading {
  font-size: 3rem;
  font-weight: 800;
  color: #1a202c;
  margin: 0 0 1rem 0;
  line-height: 1.1;
  text-align: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.sub-heading {
  font-size: 2.25rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0 0 0.75rem 0;
  line-height: 1.2;
}

.section-heading {
  font-size: 1.875rem;
  font-weight: 600;
  color: #4a5568;
  margin: 0 0 0.5rem 0;
  line-height: 1.3;
  border-left: 4px solid #667eea;
  padding-left: 1rem;
}

.subsection-heading {
  font-size: 1.5rem;
  font-weight: 600;
  color: #4a5568;
  margin: 0 0 0.5rem 0;
  line-height: 1.4;
}

.detail-heading {
  font-size: 1.25rem;
  font-weight: 500;
  color: #718096;
  margin: 0 0 0.5rem 0;
  line-height: 1.4;
}

.minor-heading {
  font-size: 1.125rem;
  font-weight: 500;
  color: #718096;
  margin: 0 0 0.5rem 0;
  line-height: 1.4;
}

/* Breadcrumb styles */
.breadcrumb-nav {
  margin-bottom: 1rem;
}

.breadcrumb-list {
  display: flex;
  align-items: center;
  list-style: none;
  margin: 0;
  padding: 0;
  flex-wrap: wrap;
}

.breadcrumb-item {
  display: flex;
  align-items: center;
  font-size: 0.875rem;
}

.breadcrumb-link {
  color: #667eea;
  text-decoration: none;
  transition: color 0.2s ease;
}

.breadcrumb-link:hover {
  color: #764ba2;
  text-decoration: underline;
}

.breadcrumb-current {
  color: #4a5568;
  font-weight: 500;
}

.breadcrumb-separator {
  color: #a0aec0;
  margin: 0 0.5rem;
  font-weight: 300;
}

/* Page description */
.page-description {
  font-size: 1.125rem;
  color: #4a5568;
  line-height: 1.6;
  margin: 0 0 1.5rem 0;
  text-align: center;
  max-width: 800px;
  margin-left: auto;
  margin-right: auto;
}

/* Responsive design */
@media (max-width: 768px) {
  .main-heading {
    font-size: 2.25rem;
  }
  
  .sub-heading {
    font-size: 1.875rem;
  }
  
  .section-heading {
    font-size: 1.5rem;
  }
  
  .subsection-heading {
    font-size: 1.25rem;
  }
  
  .detail-heading {
    font-size: 1.125rem;
  }
  
  .minor-heading {
    font-size: 1rem;
  }
  
  .page-description {
    font-size: 1rem;
    padding: 0 1rem;
  }
  
  .breadcrumb-list {
    font-size: 0.8rem;
  }
}

@media (max-width: 480px) {
  .main-heading {
    font-size: 1.875rem;
  }
  
  .sub-heading {
    font-size: 1.5rem;
  }
  
  .section-heading {
    font-size: 1.25rem;
    padding-left: 0.75rem;
  }
  
  .breadcrumb-list {
    font-size: 0.75rem;
  }
  
  .breadcrumb-separator {
    margin: 0 0.25rem;
  }
}
</style>