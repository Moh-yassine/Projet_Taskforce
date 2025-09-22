<template>
  <div class="premium-container">
    <div class="premium-header">
      <div class="header-content">
        <h1>🎉 TaskForce Premium</h1>
        <p class="subtitle">Bienvenue dans votre espace premium</p>
        <div class="subscription-info" v-if="subscription">
          <div class="status-badge active">
            <span class="status-icon">✅</span>
            <span>Abonnement actif</span>
          </div>
          <p class="renewal-date">
            Renouvellement le {{ formatDate(subscription.currentPeriodEnd) }}
          </p>
        </div>
      </div>
    </div>

    <div class="premium-content">
      <div class="features-grid" v-if="features">
        <div
          v-for="(feature, key) in features"
          :key="key"
          class="feature-card"
          :class="{ enabled: feature.enabled }"
        >
          <div class="feature-icon">
            <span v-if="feature.enabled">✅</span>
            <span v-else>❌</span>
          </div>
          <div class="feature-content">
            <h3>{{ feature.name }}</h3>
            <p>{{ feature.description }}</p>
          </div>
        </div>
      </div>

      <div class="premium-actions">
        <div class="action-card">
          <h3>📊 Rapports Avancés</h3>
          <p>Accédez à des analyses détaillées et des rapports personnalisés</p>
          <button class="action-btn" @click="openAdvancedReports">Voir les rapports</button>
        </div>

        <div class="action-card">
          <h3>🎨 Personnalisation</h3>
          <p>Personnalisez votre interface avec votre marque</p>
          <button class="action-btn" @click="openCustomization">Personnaliser</button>
        </div>

        <div class="action-card">
          <h3>🔧 API Access</h3>
          <p>Intégrez TaskForce avec vos outils existants</p>
          <button class="action-btn" @click="openApiDocs">Documentation API</button>
        </div>

        <div class="action-card">
          <h3>💬 Support Prioritaire</h3>
          <p>Obtenez de l'aide rapidement avec notre support prioritaire</p>
          <button class="action-btn" @click="openSupport">Contacter le support</button>
        </div>
      </div>

      <div class="subscription-management">
        <div class="management-card">
          <h3>Gestion de l'abonnement</h3>
          <div class="management-actions">
            <button class="secondary-btn" @click="viewBilling">Voir la facturation</button>
            <button class="danger-btn" @click="cancelSubscription">Annuler l'abonnement</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Cancel Subscription Modal -->
    <div v-if="showCancelModal" class="modal-overlay" @click="closeCancelModal">
      <div class="modal-content" @click.stop>
        <h3>Annuler l'abonnement</h3>
        <p>Êtes-vous sûr de vouloir annuler votre abonnement TaskForce Premium ?</p>
        <p class="warning">
          Vous perdrez l'accès à toutes les fonctionnalités premium à la fin de votre période de
          facturation.
        </p>
        <div class="modal-actions">
          <button class="secondary-btn" @click="closeCancelModal">Garder l'abonnement</button>
          <button class="danger-btn" @click="confirmCancel" :disabled="cancelling">
            {{ cancelling ? 'Annulation...' : "Confirmer l'annulation" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import {
  paymentService,
  type SubscriptionData,
  type PremiumFeatures,
} from '@/services/paymentService'

const router = useRouter()

// Reactive data
const features = ref<PremiumFeatures | null>(null)
const subscription = ref<SubscriptionData | null>(null)
const loading = ref(true)
const error = ref('')
const showCancelModal = ref(false)
const cancelling = ref(false)

// Load premium data
const loadPremiumData = async () => {
  try {
    loading.value = true

    // Load subscription status
    const subscriptionStatus = await paymentService.getSubscriptionStatus()
    subscription.value = subscriptionStatus.subscription

    // Load premium features
    const featuresResult = await paymentService.getPremiumFeatures()
    if (featuresResult.success) {
      features.value = featuresResult.features || null
    } else {
      error.value = featuresResult.error || 'Erreur lors du chargement des fonctionnalités'
    }
  } catch (err: any) {
    error.value = err.message || 'Erreur lors du chargement des données premium'
  } finally {
    loading.value = false
  }
}

// Format date
const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

// Premium actions
const openAdvancedReports = () => {
  // TODO: Implement advanced reports
  alert('Fonctionnalité en cours de développement')
}

const openCustomization = () => {
  // TODO: Implement customization
  alert('Fonctionnalité en cours de développement')
}

const openApiDocs = () => {
  // TODO: Implement API documentation
  alert('Documentation API en cours de développement')
}

const openSupport = () => {
  // TODO: Implement support contact
  alert('Support prioritaire - Contactez-nous à support@taskforce.com')
}

const viewBilling = () => {
  // TODO: Implement billing view
  alert('Gestion de la facturation en cours de développement')
}

// Cancel subscription
const cancelSubscription = () => {
  showCancelModal.value = true
}

const closeCancelModal = () => {
  showCancelModal.value = false
}

const confirmCancel = async () => {
  try {
    cancelling.value = true
    const result = await paymentService.cancelSubscription()

    if (result.success) {
      alert('Abonnement annulé avec succès')
      router.push('/dashboard')
    } else {
      alert("Erreur lors de l'annulation: " + result.error)
    }
  } catch (err: any) {
    alert("Erreur lors de l'annulation: " + err.message)
  } finally {
    cancelling.value = false
    showCancelModal.value = false
  }
}

// Lifecycle
onMounted(() => {
  loadPremiumData()
})
</script>

<style scoped>
.premium-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem;
}

.premium-header {
  text-align: center;
  margin-bottom: 3rem;
  color: white;
}

.header-content h1 {
  font-size: 3rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.subtitle {
  font-size: 1.25rem;
  opacity: 0.9;
  margin-bottom: 1.5rem;
}

.subscription-info {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
}

.status-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255, 255, 255, 0.2);
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 600;
}

.status-badge.active {
  background: rgba(34, 197, 94, 0.2);
  border: 1px solid rgba(34, 197, 94, 0.3);
}

.renewal-date {
  font-size: 0.9rem;
  opacity: 0.8;
}

.premium-content {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.feature-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  transition:
    transform 0.2s,
    box-shadow 0.2s;
}

.feature-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
}

.feature-card.enabled {
  border-left: 4px solid #10b981;
}

.feature-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.feature-content h3 {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.feature-content p {
  color: #6b7280;
  line-height: 1.5;
}

.premium-actions {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.action-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  text-align: center;
  transition: transform 0.2s;
}

.action-card:hover {
  transform: translateY(-2px);
}

.action-card h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.75rem;
}

.action-card p {
  color: #6b7280;
  margin-bottom: 1.5rem;
  line-height: 1.5;
}

.action-btn {
  background: #1e40af;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
  width: 100%;
}

.action-btn:hover {
  background: #1d4ed8;
}

.subscription-management {
  display: flex;
  justify-content: center;
}

.management-card {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  text-align: center;
  max-width: 400px;
  width: 100%;
}

.management-card h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 1.5rem;
}

.management-actions {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.secondary-btn {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
}

.secondary-btn:hover {
  background: #e5e7eb;
}

.danger-btn {
  background: #dc2626;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
}

.danger-btn:hover:not(:disabled) {
  background: #b91c1c;
}

.danger-btn:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

/* Modal styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  max-width: 500px;
  width: 90%;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.modal-content h3 {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 1rem;
}

.modal-content p {
  color: #6b7280;
  margin-bottom: 1rem;
  line-height: 1.5;
}

.warning {
  color: #dc2626;
  font-weight: 600;
  background: #fef2f2;
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid #fecaca;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
}

.modal-actions .secondary-btn,
.modal-actions .danger-btn {
  flex: 1;
}

@media (max-width: 768px) {
  .premium-container {
    padding: 1rem;
  }

  .header-content h1 {
    font-size: 2rem;
  }

  .features-grid,
  .premium-actions {
    grid-template-columns: 1fr;
  }

  .modal-actions {
    flex-direction: column;
  }
}
</style>
