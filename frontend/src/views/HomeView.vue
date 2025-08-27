<script setup lang="ts">
// Base structure - functionality will be added later
</script>

<template>
  <div class="home">
    <!-- Header -->
    <header class="header">
      <div class="header-content">
        <div class="logo-container">
          <div class="logo">
            <div class="logo-icon">TF</div>
          </div>
          <h1 class="logo-text">TaskForce</h1>
        </div>
        
        <div class="header-actions">
          <a href="#" class="btn btn-secondary">Se connecter</a>
          <a href="#" class="btn btn-primary">S'inscrire gratuitement</a>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
      <div class="content-wrapper">
        <!-- Left Side - Text Content -->
        <div class="text-content">
          <h2 class="hero-title">
            Capturez, organisez et gérez vos tâches de manière intelligente
          </h2>
          <p class="hero-description">
            Échappez au désordre et au chaos - libérez votre productivité avec TaskForce et sa répartition automatique des tâches.
          </p>

          <!-- Signup Form -->
          <div class="signup-section">
            <div class="signup-form">
              <input 
                type="email" 
                placeholder="Votre email" 
                class="email-input"
              />
              <button class="btn btn-primary signup-btn">
                S'inscrire - c'est gratuit !
              </button>
            </div>
            <p class="privacy-text">
              En saisissant mon email, j'accepte la 
              <a href="#" class="privacy-link">
                Politique de confidentialité TaskForce
              </a>.
            </p>
          </div>
        </div>

        <!-- Right Side - iPhone Mockup -->
        <div class="phone-mockup">
          <div class="iphone-frame">
            <div class="iphone-screen">
              <!-- Status Bar -->
              <div class="status-bar">
                <div class="status-left">
                  <span class="time">9:41</span>
                </div>
                <div class="status-right">
                  <span class="signal">📶</span>
                  <span class="wifi">📶</span>
                  <span class="battery">🔋</span>
                </div>
              </div>

              <!-- App Header -->
              <div class="app-header">
                <h3 class="app-title">TaskForce</h3>
                <div class="app-actions">
                  <span class="action-icon">🔍</span>
                  <span class="action-icon">➕</span>
                </div>
              </div>

              <!-- Navigation Tabs -->
              <div class="nav-tabs">
                <div class="tab active">
                  <span class="tab-icon">📋</span>
                  <span class="tab-text">Tableaux</span>
                </div>
                <div class="tab">
                  <span class="tab-icon">📥</span>
                  <span class="tab-text">Boîte de réception</span>
                </div>
                <div class="tab">
                  <span class="tab-icon">🔍</span>
                  <span class="tab-text">Recherche</span>
                </div>
                <div class="tab">
                  <span class="tab-icon">🔔</span>
                  <span class="tab-text">Notifications</span>
                </div>
              </div>

              <!-- Task List -->
              <div class="task-list">
                <div class="task-item high-priority">
                  <div class="task-content">
                    <h4 class="task-title">Votre séjour à Paris</h4>
                    <div class="task-meta">
                      <span class="priority-badge high">Haute priorité</span>
                      <span class="due-date">Échéance: 2 jours</span>
                    </div>
                  </div>
                  <div class="task-actions">
                    <span class="action-btn">✓</span>
                  </div>
                </div>

                <div class="task-item medium-priority">
                  <div class="task-content">
                    <h4 class="task-title">Préparer l'analyse des campagnes</h4>
                    <div class="task-meta">
                      <span class="priority-badge medium">Moyenne priorité</span>
                      <span class="due-date">Échéance: 5 jours</span>
                    </div>
                  </div>
                  <div class="task-actions">
                    <span class="action-btn">✓</span>
                  </div>
                </div>

                <div class="task-item low-priority">
                  <div class="task-content">
                    <h4 class="task-title">Présentation marketing reportée</h4>
                    <div class="task-meta">
                      <span class="priority-badge low">Basse priorité</span>
                      <span class="due-date">Échéance: 1 semaine</span>
                    </div>
                  </div>
                  <div class="task-actions">
                    <span class="action-btn">✓</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
/* Styles will be added later */
</style>
