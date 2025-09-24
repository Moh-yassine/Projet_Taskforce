<template>
  <div class="payment-container">
    <!-- Header -->
    <div class="payment-header">
      <button @click="goBack" class="back-btn">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
          <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        Retour au dashboard
      </button>
      <h1>Passer à Premium</h1>
    </div>

    <!-- Contenu principal -->
    <div class="payment-content">
      <!-- Section des avantages Premium -->
      <div class="premium-benefits">
        <div class="benefits-header">
          <div class="premium-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
          </div>
          <h2>Débloquez tout le potentiel de TaskForce</h2>
          <p class="benefits-subtitle">Accédez à des fonctionnalités avancées pour optimiser votre gestion de projets</p>
        </div>

        <div class="benefits-grid">
          <div class="benefit-item">
            <div class="benefit-icon">📊</div>
            <h3>Rapports Avancés</h3>
            <p>Analyses détaillées et métriques de performance</p>
          </div>
          <div class="benefit-item">
            <div class="benefit-icon">🎯</div>
            <h3>Mode Observateur</h3>
            <p>Limitez les actions de certains utilisateurs sur vos tableaux</p>
          </div>
          <div class="benefit-item">
            <div class="benefit-icon">🚀</div>
            <h3>Projets Illimités</h3>
            <p>Créez autant de projets que nécessaire</p>
          </div>
          <div class="benefit-item">
            <div class="benefit-icon">⚡</div>
            <h3>Support Prioritaire</h3>
            <p>Assistance client avec réponse garantie sous 24h</p>
          </div>
          <div class="benefit-item">
            <div class="benefit-icon">🔧</div>
            <h3>API Access</h3>
            <p>Intégrations tierces et automatisation</p>
          </div>
          <div class="benefit-item">
            <div class="benefit-icon">🎨</div>
            <h3>Personnalisation</h3>
            <p>Interface personnalisée avec votre marque</p>
          </div>
        </div>
      </div>

      <!-- Section de paiement -->
      <div class="payment-section">
        <div class="pricing-card">
          <div class="pricing-header">
            <h3>TaskForce Premium</h3>
            <div class="price">
              <span class="currency">€</span>
              <span class="amount">29.99</span>
              <span class="period">/mois</span>
            </div>
          </div>

          <div class="pricing-features">
            <div class="feature-item">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
              </svg>
              <span>Toutes les fonctionnalités Premium</span>
            </div>
            <div class="feature-item">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
              </svg>
              <span>Mode Observateur avancé</span>
            </div>
            <div class="feature-item">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
              </svg>
              <span>Support prioritaire 24/7</span>
            </div>
            <div class="feature-item">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
              </svg>
              <span>Annulation à tout moment</span>
            </div>
          </div>

          <!-- Formulaire de paiement Stripe -->
          <div class="payment-form">
            <div v-if="!paymentConfig" class="loading">
              <div class="spinner"></div>
              <p>Chargement de la configuration de paiement...</p>
            </div>

            <div v-else-if="!paymentConfig.hasActiveSubscription" class="checkout-form">
              <div class="payment-info">
                <h4>Paiement sécurisé avec Stripe</h4>
                <p>Vous serez redirigé vers une page de paiement sécurisée de Stripe pour finaliser votre abonnement.</p>
                
                
                <div class="security-badges">
                  <div class="security-badge">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                    </svg>
                    <span>Paiement sécurisé</span>
                  </div>
                  <div class="security-badge">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                      <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>SSL 256-bit</span>
                  </div>
                </div>
              </div>
              
              <button 
                @click="handlePayment" 
                :disabled="isProcessing"
                class="payment-btn"
              >
                <span v-if="isProcessing" class="spinner-small"></span>
                <span v-else>Commencer l'abonnement Premium</span>
              </button>
            </div>

            <div v-else class="already-premium">
              <div class="premium-badge">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
                <span>Vous êtes déjà Premium !</span>
              </div>
              <p>Profitez de toutes les fonctionnalités avancées de TaskForce.</p>
              <button @click="goToDashboard" class="dashboard-btn">
                Retour au dashboard
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Messages d'erreur -->
    <div v-if="errorMessage" class="error-message">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
      </svg>
      <span>{{ errorMessage }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { paymentService, type PaymentConfig } from '@/services/paymentService'

const router = useRouter()

// État du composant
const paymentConfig = ref<PaymentConfig | null>(null)
const isProcessing = ref(false)
const errorMessage = ref('')

// Initialisation
onMounted(async () => {
  try {
    await initializePayment()
  } catch (error) {
    console.error('Erreur lors de l\'initialisation:', error)
    errorMessage.value = 'Erreur lors du chargement de la page de paiement'
  }
})

const initializePayment = async () => {
  try {
    // Récupérer la configuration de paiement
    paymentConfig.value = await paymentService.getConfig()
  } catch (error) {
    console.error('Erreur lors de l\'initialisation du paiement:', error)
    errorMessage.value = 'Erreur lors de l\'initialisation du paiement'
  }
}

const handlePayment = async () => {
  isProcessing.value = true
  errorMessage.value = ''

  try {
    // Créer la session de paiement Stripe Checkout
    const result = await paymentService.createSubscription()
    
    if (result.success && result.checkout_url) {
      // Rediriger vers Stripe Checkout
      window.location.href = result.checkout_url
    } else {
      throw new Error(result.error || 'Erreur lors de la création de la session de paiement')
    }
  } catch (error) {
    console.error('Erreur lors du paiement:', error)
    errorMessage.value = error instanceof Error ? error.message : 'Erreur lors du paiement'
    isProcessing.value = false
  }
}

const goBack = () => {
  router.push('/dashboard')
}

const goToDashboard = () => {
  router.push('/dashboard')
}
</script>

<style scoped>
:root {
  --deep-dark: #0D1B2A;
  --deep-navy: #1B263B;
  --deep-blue: #415A77;
  --deep-light: #778DA9;
  --deep-pale: #E0E1DD;
  --primary-color: var(--deep-blue);
  --primary-hover: var(--deep-navy);
  --white: #ffffff;
  --text-primary: var(--deep-dark);
  --text-secondary: var(--deep-blue);
  --success-color: #10b981;
  --error-color: #ef4444;
  --warning-color: #f59e0b;
}

.payment-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  padding: 2rem;
}

.payment-header {
  max-width: 1200px;
  margin: 0 auto 3rem auto;
  display: flex;
  align-items: center;
  gap: 2rem;
}

.back-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: var(--white);
  color: var(--text-primary);
  border: 2px solid var(--deep-light);
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
}

.back-btn:hover {
  background: var(--deep-light);
  color: var(--white);
  transform: translateY(-1px);
}

.payment-header h1 {
  color: var(--text-primary);
  font-size: 2.5rem;
  font-weight: 800;
  margin: 0;
}

.payment-content {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 3rem;
  align-items: start;
}

.premium-benefits {
  background: var(--white);
  border-radius: 16px;
  padding: 2.5rem;
  box-shadow: 0 10px 40px rgba(27, 38, 59, 0.1);
}

.benefits-header {
  text-align: center;
  margin-bottom: 3rem;
}

.premium-icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #ffd700, #ffed4e);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem auto;
  color: #333;
  box-shadow: 0 8px 25px rgba(255, 215, 0, 0.3);
}

.benefits-header h2 {
  color: var(--text-primary);
  font-size: 2rem;
  font-weight: 700;
  margin: 0 0 1rem 0;
}

.benefits-subtitle {
  color: var(--text-secondary);
  font-size: 1.1rem;
  margin: 0;
  line-height: 1.6;
}

.benefits-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.benefit-item {
  padding: 1.5rem;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  border-radius: 12px;
  border: 2px solid transparent;
  transition: all 0.3s ease;
}

.benefit-item:hover {
  border-color: var(--primary-color);
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(65, 90, 119, 0.15);
}

.benefit-icon {
  font-size: 2rem;
  margin-bottom: 1rem;
}

.benefit-item h3 {
  color: var(--text-primary);
  font-size: 1.2rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
}

.benefit-item p {
  color: var(--text-secondary);
  margin: 0;
  line-height: 1.5;
}

.payment-section {
  position: sticky;
  top: 2rem;
}

.pricing-card {
  background: var(--white);
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 10px 40px rgba(27, 38, 59, 0.1);
  border: 3px solid transparent;
  background-clip: padding-box;
  position: relative;
}

.pricing-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, #ffd700, #ffed4e, #ffd700);
  border-radius: 16px;
  padding: 3px;
  mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  mask-composite: exclude;
  -webkit-mask-composite: xor;
  z-index: -1;
}

.pricing-header {
  text-align: center;
  margin-bottom: 2rem;
}

.pricing-header h3 {
  color: var(--text-primary);
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 1rem 0;
}

.price {
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 0.25rem;
}

.currency {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-primary);
}

.amount {
  font-size: 3rem;
  font-weight: 800;
  color: var(--primary-color);
}

.period {
  font-size: 1rem;
  color: var(--text-secondary);
}

.pricing-features {
  margin-bottom: 2rem;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 0;
  color: var(--text-primary);
}

.feature-item svg {
  color: var(--success-color);
  flex-shrink: 0;
}

.payment-form {
  margin-top: 2rem;
}

.loading {
  text-align: center;
  padding: 2rem;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--deep-light);
  border-top: 4px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem auto;
}

.spinner-small {
  width: 20px;
  height: 20px;
  border: 2px solid transparent;
  border-top: 2px solid currentColor;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  display: inline-block;
  margin-right: 0.5rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.checkout-form {
  text-align: center;
}

.payment-info {
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  border-radius: 12px;
  border: 2px solid var(--deep-light);
}

.payment-info h4 {
  color: var(--text-primary);
  font-size: 1.2rem;
  font-weight: 600;
  margin: 0 0 1rem 0;
}

.payment-info p {
  color: var(--text-secondary);
  margin: 0 0 1.5rem 0;
  line-height: 1.5;
}

.security-badges {
  display: flex;
  justify-content: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.security-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: var(--white);
  border-radius: 20px;
  border: 1px solid var(--deep-light);
  font-size: 0.8rem;
  color: var(--text-primary);
}

.security-badge svg {
  color: var(--success-color);
}

.simulation-notice {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem;
  background: linear-gradient(135deg, #fef3c7, #fde68a);
  border: 2px solid #f59e0b;
  border-radius: 8px;
  margin: 1rem 0;
}

.notice-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.notice-content {
  flex: 1;
}

.notice-content strong {
  color: #92400e;
  font-weight: 600;
  display: block;
  margin-bottom: 0.5rem;
}

.notice-content p {
  color: #92400e;
  margin: 0;
  font-size: 0.9rem;
  line-height: 1.4;
}

.payment-btn {
  width: 100%;
  padding: 1rem 2rem;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
  color: var(--white);
  border: none;
  border-radius: 8px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.payment-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(65, 90, 119, 0.3);
}

.payment-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.already-premium {
  text-align: center;
  padding: 2rem;
}

.premium-badge {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #ffd700, #ffed4e);
  color: #333;
  padding: 1rem 2rem;
  border-radius: 25px;
  font-weight: 700;
  margin-bottom: 1rem;
  box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
}

.premium-badge svg {
  color: #333;
}

.already-premium p {
  color: var(--text-secondary);
  margin-bottom: 1.5rem;
}

.dashboard-btn {
  padding: 0.75rem 2rem;
  background: var(--primary-color);
  color: var(--white);
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.dashboard-btn:hover {
  background: var(--primary-hover);
  transform: translateY(-1px);
}

.error-message {
  position: fixed;
  top: 2rem;
  right: 2rem;
  background: var(--error-color);
  color: var(--white);
  padding: 1rem 1.5rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
  z-index: 1000;
  animation: slideIn 0.3s ease;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

/* Responsive */
@media (max-width: 1024px) {
  .payment-content {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
  
  .payment-section {
    position: static;
  }
}

@media (max-width: 768px) {
  .payment-container {
    padding: 1rem;
  }
  
  .payment-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 2rem;
  }
  
  .payment-header h1 {
    font-size: 2rem;
  }
  
  .premium-benefits,
  .pricing-card {
    padding: 1.5rem;
  }
  
  .benefits-grid {
    grid-template-columns: 1fr;
  }
  
  .benefits-header h2 {
    font-size: 1.5rem;
  }
  
  .amount {
    font-size: 2.5rem;
  }
}

@media (max-width: 480px) {
  .payment-header h1 {
    font-size: 1.5rem;
  }
  
  .premium-benefits,
  .pricing-card {
    padding: 1rem;
  }
  
  .benefit-item {
    padding: 1rem;
  }
  
  .amount {
    font-size: 2rem;
  }
}
</style>
