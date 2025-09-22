<template>
  <div class="seo-content">
    <!-- Structured content for SEO -->
    <article v-if="structuredContent" class="structured-article">
      <header class="article-header">
        <h1 v-if="structuredContent.title" class="article-title">
          {{ structuredContent.title }}
        </h1>
        <p v-if="structuredContent.description" class="article-description">
          {{ structuredContent.description }}
        </p>
      </header>

      <div v-if="structuredContent.sections" class="article-sections">
        <section 
          v-for="(section, index) in structuredContent.sections" 
          :key="index"
          class="article-section"
        >
          <h2 v-if="section.title" class="section-title">
            {{ section.title }}
          </h2>
          <div v-if="section.content" class="section-content" v-html="section.content">
          </div>
          <ul v-if="section.listItems" class="section-list">
            <li v-for="(item, itemIndex) in section.listItems" :key="itemIndex">
              {{ item }}
            </li>
          </ul>
        </section>
      </div>

      <!-- FAQ Schema -->
      <div v-if="structuredContent.faq" class="faq-section">
        <h2>Questions fréquemment posées</h2>
        <div class="faq-list">
          <details 
            v-for="(faq, index) in structuredContent.faq" 
            :key="index"
            class="faq-item"
          >
            <summary class="faq-question">{{ faq.question }}</summary>
            <div class="faq-answer">{{ faq.answer }}</div>
          </details>
        </div>
      </div>
    </article>

    <!-- Default content if no structured content provided -->
    <div v-else class="default-content">
      <slot></slot>
    </div>

    <!-- JSON-LD Schema -->
    <script 
      v-if="structuredContent && structuredContent.schema" 
      type="application/ld+json"
      v-html="JSON.stringify(structuredContent.schema)"
    ></script>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface StructuredContent {
  title?: string
  description?: string
  sections?: Array<{
    title?: string
    content?: string
    listItems?: string[]
  }>
  faq?: Array<{
    question: string
    answer: string
  }>
  schema?: any
}

const props = defineProps<{
  content?: StructuredContent
  title?: string
  description?: string
}>()

const structuredContent = computed(() => {
  if (props.content) {
    return props.content
  }
  
  if (props.title || props.description) {
    return {
      title: props.title,
      description: props.description
    }
  }
  
  return null
})
</script>

<style scoped>
.seo-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
  line-height: 1.6;
}

.structured-article {
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.article-header {
  padding: 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.article-title {
  font-size: 2.5rem;
  font-weight: 700;
  margin: 0 0 1rem 0;
  line-height: 1.2;
}

.article-description {
  font-size: 1.2rem;
  margin: 0;
  opacity: 0.9;
}

.article-sections {
  padding: 2rem;
}

.article-section {
  margin-bottom: 2rem;
}

.section-title {
  font-size: 1.8rem;
  font-weight: 600;
  color: #2d3748;
  margin: 0 0 1rem 0;
  border-bottom: 2px solid #e2e8f0;
  padding-bottom: 0.5rem;
}

.section-content {
  color: #4a5568;
  margin-bottom: 1rem;
}

.section-content :deep(p) {
  margin-bottom: 1rem;
}

.section-content :deep(strong) {
  color: #2d3748;
  font-weight: 600;
}

.section-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.section-list li {
  padding: 0.75rem 0;
  border-bottom: 1px solid #e2e8f0;
  position: relative;
  padding-left: 1.5rem;
}

.section-list li:before {
  content: "✓";
  position: absolute;
  left: 0;
  color: #48bb78;
  font-weight: bold;
}

.faq-section {
  background: #f7fafc;
  padding: 2rem;
  margin-top: 2rem;
}

.faq-section h2 {
  font-size: 1.8rem;
  font-weight: 600;
  color: #2d3748;
  margin: 0 0 1.5rem 0;
}

.faq-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.faq-item {
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.faq-question {
  padding: 1rem 1.5rem;
  background: #edf2f7;
  font-weight: 600;
  color: #2d3748;
  cursor: pointer;
  margin: 0;
  transition: background-color 0.2s ease;
}

.faq-question:hover {
  background: #e2e8f0;
}

.faq-answer {
  padding: 1rem 1.5rem;
  color: #4a5568;
  line-height: 1.6;
}

.default-content {
  color: #4a5568;
}

/* Responsive */
@media (max-width: 768px) {
  .seo-content {
    padding: 1rem;
  }
  
  .article-header {
    padding: 1.5rem;
  }
  
  .article-title {
    font-size: 2rem;
  }
  
  .article-description {
    font-size: 1.1rem;
  }
  
  .article-sections {
    padding: 1.5rem;
  }
  
  .section-title {
    font-size: 1.5rem;
  }
  
  .faq-section {
    padding: 1.5rem;
  }
}
</style>