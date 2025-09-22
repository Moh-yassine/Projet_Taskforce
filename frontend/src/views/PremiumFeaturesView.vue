<template>
  <div class="premium-features">
    <!-- Message de chargement -->
    <div v-if="loading" class="loading-section">
      <div class="container">
        <div class="loading-card">
          <div class="spinner"></div>
          <h2>Vérification de votre abonnement...</h2>
          <p v-if="paymentStatus === 'success'">
            Paiement reçu ! Configuration de votre compte premium en cours...
          </p>
          <p v-else>Veuillez patienter pendant que nous vérifions votre statut.</p>
        </div>
      </div>
    </div>

    <!-- Message d'erreur -->
    <div v-else-if="paymentStatus === 'error'" class="error-section">
      <div class="container">
        <div class="error-card">
          <h2>❌ Problème de paiement</h2>
          <p>
            Nous n'avons pas pu confirmer votre paiement. Veuillez réessayer ou contacter le
            support.
          </p>
          <button @click="router.push('/dashboard')" class="retry-btn">Retour au dashboard</button>
        </div>
      </div>
    </div>

    <!-- Message d'annulation -->
    <div v-else-if="paymentStatus === 'cancelled'" class="cancelled-section">
      <div class="container">
        <div class="cancelled-card">
          <h2>⚠️ Paiement annulé</h2>
          <p>Vous avez annulé le processus de paiement. Vous pouvez réessayer à tout moment.</p>
          <button @click="router.push('/dashboard')" class="retry-btn">Retour au dashboard</button>
        </div>
      </div>
    </div>

    <!-- Contenu principal -->
    <div v-else>
      <div class="hero-section">
        <div class="container">
          <h1 class="hero-title">🎉 Bienvenue dans TaskForce Premium !</h1>
          <p class="hero-subtitle">
            Vous avez maintenant accès à toutes les fonctionnalités avancées
          </p>
        </div>
      </div>

      <div class="features-grid">
        <div class="container">
          <div class="features-header">
            <h2>Fonctionnalités Premium</h2>
            <p>Découvrez tous les avantages de votre abonnement</p>
          </div>

          <div class="features-list">
            <div class="feature-card">
              <div class="feature-icon">📊</div>
              <h3>Rapports Avancés</h3>
              <p>
                Analyses détaillées de vos projets avec graphiques interactifs et métriques en temps
                réel.
              </p>
              <button class="feature-btn" @click="openReports">Accéder aux rapports</button>
            </div>

            <div class="feature-card">
              <div class="feature-icon">🎯</div>
              <h3>Projets Illimités</h3>
              <p>Créez autant de projets que vous le souhaitez sans aucune limitation.</p>
              <button class="feature-btn" @click="openProjects">Gérer les projets</button>
            </div>

            <div class="feature-card">
              <div class="feature-icon">⚡</div>
              <h3>Support Prioritaire</h3>
              <p>Obtenez une réponse sous 24h pour toutes vos questions et problèmes.</p>
              <button class="feature-btn" @click="openSupport">Contacter le support</button>
            </div>

            <div class="feature-card">
              <div class="feature-icon">🎨</div>
              <h3>Tableaux de Bord Personnalisés</h3>
              <p>Créez des dashboards sur mesure selon vos besoins et préférences.</p>
              <button class="feature-btn" @click="openDashboard">Personnaliser</button>
            </div>

            <div class="feature-card">
              <div class="feature-icon">🔌</div>
              <h3>Accès API</h3>
              <p>Intégrez TaskForce avec vos outils existants via notre API REST complète.</p>
              <button class="feature-btn" @click="openAPI">Documentation API</button>
            </div>

            <div class="feature-card">
              <div class="feature-icon">🎛️</div>
              <h3>Personnalisation Interface</h3>
              <p>
                Adaptez l'interface selon vos préférences avec des thèmes et layouts personnalisés.
              </p>
              <button class="feature-btn" @click="openSettings">Paramètres</button>
            </div>
          </div>
        </div>
      </div>

      <div class="subscription-info">
        <div class="container">
          <div class="subscription-card">
            <h3>Informations d'Abonnement</h3>
            <div class="subscription-details" v-if="subscription">
              <div class="detail-item">
                <span class="label">Plan :</span>
                <span class="value premium-badge">{{ subscription.plan.toUpperCase() }}</span>
              </div>
              <div class="detail-item">
                <span class="label">Prix :</span>
                <span class="value">€{{ (subscription.amount / 100).toFixed(2) }}/mois</span>
              </div>
              <div class="detail-item">
                <span class="label">Statut :</span>
                <span class="value status-active">{{ subscription.status }}</span>
              </div>
              <div class="detail-item">
                <span class="label">Prochaine facturation :</span>
                <span class="value">{{ formatDate(subscription.currentPeriodEnd) }}</span>
              </div>
            </div>
            <div class="subscription-actions">
              <button class="btn-secondary" @click="manageSubscription">Gérer l'abonnement</button>
              <button class="btn-danger" @click="cancelSubscription">Annuler l'abonnement</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { paymentService } from '@/services/paymentService'

const router = useRouter()
const route = useRoute()
const subscription = ref(null)
const loading = ref(true)
const paymentStatus = ref('')

onMounted(async () => {
  // Vérifier les paramètres de l'URL
  const urlParams = new URLSearchParams(window.location.search)
  const paymentParam = urlParams.get('payment')

  if (paymentParam === 'success') {
    paymentStatus.value = 'success'
    // Attendre un peu pour que Stripe traite le paiement
    await new Promise((resolve) => setTimeout(resolve, 3000))
  } else if (paymentParam === 'cancelled') {
    paymentStatus.value = 'cancelled'
  }

  try {
    // Vérifier le statut d'abonnement
    const response = await paymentService.getSubscriptionStatus()
    if (response.hasActiveSubscription) {
      subscription.value = response.subscription
      loading.value = false
    } else {
      // Si pas d'abonnement et pas de paramètre de paiement, rediriger vers le dashboard
      if (!paymentParam) {
        router.push('/dashboard')
        return
      }

      // Si on vient d'un paiement mais pas encore d'abonnement, continuer à vérifier
      if (paymentParam === 'success') {
        await checkPaymentStatus()
      } else {
        router.push('/dashboard')
      }
    }
  } catch (error) {
    console.error("Erreur lors du chargement de l'abonnement:", error)
    if (!paymentParam) {
      router.push('/dashboard')
    }
  }
})

const checkPaymentStatus = async () => {
  let attempts = 0
  const maxAttempts = 10

  while (attempts < maxAttempts) {
    try {
      const response = await paymentService.getSubscriptionStatus()
      if (response.hasActiveSubscription) {
        subscription.value = response.subscription
        loading.value = false
        return
      }

      // Attendre 2 secondes avant de réessayer
      await new Promise((resolve) => setTimeout(resolve, 2000))
      attempts++
    } catch (error) {
      console.error('Erreur lors de la vérification du statut:', error)
      attempts++
    }
  }

  // Si après 10 tentatives, pas d'abonnement trouvé
  loading.value = false
  paymentStatus.value = 'error'
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

const openReports = () => {
  router.push('/reports')
}

const openProjects = () => {
  router.push('/projects')
}

const openSupport = () => {
  // Ouvrir une modal de support ou rediriger vers une page de contact
  alert('Fonctionnalité de support en cours de développement')
}

const openDashboard = () => {
  router.push('/dashboard')
}

const openAPI = () => {
  // Ouvrir la documentation API
  window.open('/api/docs', '_blank')
}

const openSettings = () => {
  router.push('/settings')
}

const manageSubscription = () => {
  router.push('/payment')
}

const cancelSubscription = () => {
  if (confirm('Êtes-vous sûr de vouloir annuler votre abonnement ?')) {
    // Logique d'annulation d'abonnement
    alert("Fonctionnalité d'annulation en cours de développement")
  }
}
</script>

<style scoped>
.premium-features {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.hero-section {
  padding: 80px 0;
  text-align: center;
  color: white;
}

.hero-title {
  font-size: 3rem;
  font-weight: bold;
  margin-bottom: 1rem;
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
}

.hero-subtitle {
  font-size: 1.5rem;
  opacity: 0.9;
  margin-bottom: 2rem;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.features-grid {
  padding: 80px 0;
  background: white;
}

.features-header {
  text-align: center;
  margin-bottom: 60px;
}

.features-header h2 {
  font-size: 2.5rem;
  color: #333;
  margin-bottom: 1rem;
}

.features-header p {
  font-size: 1.2rem;
  color: #666;
}

.features-list {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 30px;
}

.feature-card {
  background: white;
  border-radius: 15px;
  padding: 30px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  text-align: center;
  transition:
    transform 0.3s ease,
    box-shadow 0.3s ease;
  border: 2px solid #f0f0f0;
}

.feature-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  border-color: #667eea;
}

.feature-icon {
  font-size: 3rem;
  margin-bottom: 20px;
}

.feature-card h3 {
  font-size: 1.5rem;
  color: #333;
  margin-bottom: 15px;
}

.feature-card p {
  color: #666;
  line-height: 1.6;
  margin-bottom: 25px;
}

.feature-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 25px;
  font-weight: bold;
  cursor: pointer;
  transition: transform 0.2s ease;
}

.feature-btn:hover {
  transform: scale(1.05);
}

.subscription-info {
  padding: 80px 0;
  background: #f8f9fa;
}

.subscription-card {
  background: white;
  border-radius: 15px;
  padding: 40px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  max-width: 600px;
  margin: 0 auto;
}

.subscription-card h3 {
  font-size: 2rem;
  color: #333;
  margin-bottom: 30px;
  text-align: center;
}

.subscription-details {
  margin-bottom: 30px;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 0;
  border-bottom: 1px solid #eee;
}

.detail-item:last-child {
  border-bottom: none;
}

.label {
  font-weight: bold;
  color: #333;
}

.value {
  color: #666;
}

.premium-badge {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 5px 15px;
  border-radius: 20px;
  font-size: 0.9rem;
  font-weight: bold;
}

.status-active {
  color: #28a745;
  font-weight: bold;
}

.subscription-actions {
  display: flex;
  gap: 15px;
  justify-content: center;
  flex-wrap: wrap;
}

.btn-secondary {
  background: #6c757d;
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 25px;
  font-weight: bold;
  cursor: pointer;
  transition: background 0.3s ease;
}

.btn-secondary:hover {
  background: #5a6268;
}

.btn-danger {
  background: #dc3545;
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 25px;
  font-weight: bold;
  cursor: pointer;
  transition: background 0.3s ease;
}

.btn-danger:hover {
  background: #c82333;
}

/* Styles pour les messages de statut */
.loading-section,
.error-section,
.cancelled-section {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
}

.loading-card,
.error-card,
.cancelled-card {
  background: white;
  border-radius: 20px;
  padding: 3rem;
  text-align: center;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  max-width: 500px;
  width: 100%;
}

.loading-card h2,
.error-card h2,
.cancelled-card h2 {
  font-size: 2rem;
  margin-bottom: 1rem;
  color: #333;
}

.loading-card p,
.error-card p,
.cancelled-card p {
  font-size: 1.2rem;
  color: #666;
  margin-bottom: 2rem;
  line-height: 1.6;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 2rem;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

.retry-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 1rem 2rem;
  border-radius: 15px;
  font-size: 1.1rem;
  font-weight: bold;
  cursor: pointer;
  transition: transform 0.2s ease;
}

.retry-btn:hover {
  transform: translateY(-2px);
}

.error-card {
  border-left: 5px solid #dc3545;
}

.cancelled-card {
  border-left: 5px solid #ffc107;
}

@media (max-width: 768px) {
  .hero-title {
    font-size: 2rem;
  }

  .hero-subtitle {
    font-size: 1.2rem;
  }

  .features-list {
    grid-template-columns: 1fr;
  }

  .subscription-actions {
    flex-direction: column;
  }

  .loading-card,
  .error-card,
  .cancelled-card {
    padding: 2rem;
  }

  .loading-card h2,
  .error-card h2,
  .cancelled-card h2 {
    font-size: 1.5rem;
  }
}
</style>
