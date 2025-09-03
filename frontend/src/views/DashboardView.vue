<template>
  <div class="dashboard-container">
    <header class="dashboard-header">
      <div class="header-content">
        <h1>Tableau de bord TaskForce</h1>
        <button @click="handleLogout" class="btn btn-logout">
          Se déconnecter
        </button>
      </div>
    </header>

    <main class="dashboard-main">
      <div class="welcome-section">
        <h2>Bienvenue, {{ user?.firstName }} !</h2>
        <p>Gérez vos projets et tâches depuis votre tableau de bord personnel.</p>
      </div>

      <div class="quick-actions">
        <h3>Actions rapides</h3>
        <div class="actions-grid">
          <div class="action-card">
            <h4>Créer un projet</h4>
            <p>Commencez un nouveau projet</p>
            <button class="btn btn-primary">Créer</button>
          </div>
          <div class="action-card">
            <h4>Voir mes tâches</h4>
            <p>Consultez vos tâches en cours</p>
            <button class="btn btn-secondary">Voir</button>
          </div>
          <div class="action-card">
            <h4>Inviter des membres</h4>
            <p>Ajoutez des collaborateurs</p>
            <button class="btn btn-secondary">Inviter</button>
          </div>
        </div>
      </div>

      <div class="user-info">
        <h3>Informations du compte</h3>
        <div class="info-grid">
          <div class="info-item">
            <strong>Nom complet:</strong> {{ user?.firstName }} {{ user?.lastName }}
          </div>
          <div class="info-item">
            <strong>Email:</strong> {{ user?.email }}
          </div>
          <div class="info-item" v-if="user?.company">
            <strong>Entreprise:</strong> {{ user?.company }}
          </div>
          <div class="info-item">
            <strong>Membre depuis:</strong> {{ formatDate(user?.createdAt) }}
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { authService, type User } from '@/services/authService'

const router = useRouter()
const user = ref<User | null>(null)

onMounted(() => {
  if (!authService.isAuthenticated()) {
    router.push('/login')
    return
  }
  
  user.value = authService.getCurrentUser()
})

const handleLogout = () => {
  authService.logout()
  router.push('/')
}

const formatDate = (dateString?: string) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('fr-FR')
}
</script>

<style scoped>
:root {
  --primary-color: #0079bf;
  --primary-hover: #005a8b;
  --text-primary: #2c3e50;
  --text-secondary: #6c757d;
  --background-light: #f8f9fa;
  --white: #ffffff;
  --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.dashboard-container {
  min-height: 100vh;
  background: var(--background-light);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.dashboard-header {
  background: var(--white);
  box-shadow: var(--shadow);
  padding: 1rem 0;
}

.header-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-content h1 {
  color: var(--text-primary);
  font-size: 1.8rem;
  font-weight: 700;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  text-decoration: none;
  display: inline-block;
  text-align: center;
}

.btn-primary {
  background: var(--primary-color);
  color: var(--white);
}

.btn-primary:hover {
  background: var(--primary-hover);
  transform: translateY(-2px);
}

.btn-secondary {
  background: var(--white);
  color: var(--text-primary);
  border: 2px solid var(--text-secondary);
}

.btn-secondary:hover {
  background: var(--text-secondary);
  color: var(--white);
  transform: translateY(-2px);
}

.btn-logout {
  background: #dc3545;
  color: var(--white);
}

.btn-logout:hover {
  background: #c82333;
  transform: translateY(-2px);
}

.dashboard-main {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.welcome-section {
  background: var(--white);
  padding: 2rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  box-shadow: var(--shadow);
  text-align: center;
}

.welcome-section h2 {
  color: var(--text-primary);
  font-size: 2rem;
  margin-bottom: 1rem;
}

.welcome-section p {
  color: var(--text-secondary);
  font-size: 1.1rem;
}

.quick-actions {
  background: var(--white);
  padding: 2rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  box-shadow: var(--shadow);
}

.quick-actions h3 {
  color: var(--text-primary);
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.action-card {
  padding: 1.5rem;
  border: 1px solid #e1e5e9;
  border-radius: 8px;
  text-align: center;
  transition: all 0.3s ease;
}

.action-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--shadow);
}

.action-card h4 {
  color: var(--text-primary);
  font-size: 1.2rem;
  margin-bottom: 0.5rem;
}

.action-card p {
  color: var(--text-secondary);
  margin-bottom: 1rem;
}

.user-info {
  background: var(--white);
  padding: 2rem;
  border-radius: 12px;
  box-shadow: var(--shadow);
}

.user-info h3 {
  color: var(--text-primary);
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.info-item {
  padding: 1rem;
  background: var(--background-light);
  border-radius: 8px;
  color: var(--text-primary);
}

@media (max-width: 768px) {
  .header-content {
    padding: 0 1rem;
  }
  
  .header-content h1 {
    font-size: 1.5rem;
  }
  
  .dashboard-main {
    padding: 1rem;
  }
  
  .actions-grid {
    grid-template-columns: 1fr;
  }
  
  .info-grid {
    grid-template-columns: 1fr;
  }
}
</style>
