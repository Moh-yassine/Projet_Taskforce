<template>
  <div class="premium-features-container">
    <!-- Header -->
    <div class="premium-header">
      <button @click="goBack" class="back-btn">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
          <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        Retour au dashboard
      </button>
      <div class="header-content">
        <div class="premium-badge">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
          </svg>
          <span>PREMIUM</span>
        </div>
        <h1>Fonctionnalités Premium</h1>
        <p>Profitez de toutes les fonctionnalités avancées de TaskForce</p>
      </div>
    </div>

    <!-- Contenu principal -->
    <div class="premium-content">
      <!-- Section Paiement pour les utilisateurs non-Premium -->
      <div class="payment-section">
        <div class="payment-card">
          <div class="payment-header">
            <h2>Activez votre abonnement Premium</h2>
            <p>Débloquez toutes les fonctionnalités avancées pour seulement 29.99€/mois</p>
          </div>
          <div class="payment-features">
            <div class="feature-item">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
              </svg>
              <span>Mode Observateur - Limitez les actions de certains utilisateurs sur vos tableaux</span>
            </div>
          </div>
          
          <!-- Formulaire de paiement intégré -->
          <div v-if="!showPaymentForm" class="payment-action">
            <button @click="showPaymentForm = true" class="payment-btn">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
              </svg>
              Commencer l'abonnement Premium - 29.99€/mois
            </button>
          </div>

          <!-- Formulaire de paiement Stripe -->
          <div v-if="showPaymentForm" class="stripe-payment-form">
            <div class="payment-form-header">
              <h3>Finaliser votre abonnement Premium</h3>
              <p>Entrez vos informations de carte pour activer le mode observateur</p>
            </div>
            
            
            <div v-if="paymentError" class="payment-error">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
              </svg>
              <span>{{ paymentError }}</span>
            </div>
            
            <div class="payment-form">
              <div class="payment-info">
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
              
              <div class="payment-actions">
                <button type="button" @click="showPaymentForm = false" class="cancel-btn">
                  Annuler
                </button>
                <button @click="handlePayment" :disabled="isProcessing" class="submit-btn">
                  <span v-if="isProcessing" class="spinner-small"></span>
                  <span v-else>Payer 29.99€/mois</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Messages de succès/erreur -->
    <div v-if="successMessage" class="success-message">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
      </svg>
      <span>{{ successMessage }}</span>
    </div>

    <div v-if="errorMessage" class="error-message">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
      </svg>
      <span>{{ errorMessage }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// État du composant
const showPaymentForm = ref(false)
const paymentError = ref('')
const isProcessing = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

const goBack = () => {
  router.push('/dashboard')
}

const handlePayment = async () => {
  isProcessing.value = true
  paymentError.value = ''

  try {
    // Pour le test, créer directement une session Stripe Checkout
    const response = await fetch('https://api.stripe.com/v1/checkout/sessions', {
      method: 'POST',
      headers: {
        'Authorization': 'Bearer sk_test_51S5oNDRyNP7K69mNIagsNjPlXg9Ndd1101C7pRjH3mIjQIfa2UnswStmLaBlRZhzlBOJEydjQcWym6p8aVpH4kxf00mcwmHhCC',
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        'payment_method_types[]': 'card',
        'line_items[0][price_data][currency]': 'eur',
        'line_items[0][price_data][product_data][name]': 'TaskForce Premium',
        'line_items[0][price_data][product_data][description]': 'Abonnement Premium TaskForce avec mode observateur',
        'line_items[0][price_data][unit_amount]': '2999',
        'line_items[0][price_data][recurring][interval]': 'month',
        'line_items[0][quantity]': '1',
        'mode': 'subscription',
        'success_url': `${window.location.origin}/dashboard?premium=success`,
        'cancel_url': `${window.location.origin}/premium`,
      }),
    })

    if (!response.ok) {
      throw new Error('Erreur lors de la création de la session de paiement')
    }

    const session = await response.json()
    
    if (session.url) {
      // Rediriger vers Stripe Checkout
      window.location.href = session.url
    } else {
      throw new Error('URL de paiement non disponible')
    }
  } catch (error) {
    console.error('Erreur lors du paiement:', error)
    paymentError.value = error instanceof Error ? error.message : 'Erreur lors du paiement'
    isProcessing.value = false
  }
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

.premium-features-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  padding: 2rem;
}

.premium-header {
  max-width: 1200px;
  margin: 0 auto 3rem auto;
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
  margin-bottom: 2rem;
}

.back-btn:hover {
  background: var(--deep-light);
  color: var(--white);
  transform: translateY(-1px);
}

.header-content {
  text-align: center;
}

.premium-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: linear-gradient(135deg, #ffd700, #ffed4e);
  color: #333;
  padding: 0.75rem 1.5rem;
  border-radius: 25px;
  font-weight: 800;
  font-size: 0.9rem;
  letter-spacing: 1px;
  margin-bottom: 1.5rem;
  box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
}

.premium-badge svg {
  color: #333;
}

.header-content h1 {
  color: var(--text-primary);
  font-size: 2.5rem;
  font-weight: 800;
  margin: 0 0 1rem 0;
}

.header-content p {
  color: var(--text-secondary);
  font-size: 1.2rem;
  margin: 0;
}

.premium-content {
  max-width: 1200px;
  margin: 0 auto;
}

/* Section de paiement */
.payment-section {
  margin-bottom: 3rem;
}

.payment-card {
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  border-radius: 16px;
  padding: 2.5rem;
  text-align: center;
  border: 2px solid var(--deep-light);
  box-shadow: 0 10px 30px rgba(65, 90, 119, 0.1);
}

.payment-header h2 {
  color: var(--text-primary);
  font-size: 2rem;
  font-weight: 700;
  margin: 0 0 1rem 0;
}

.payment-header p {
  color: var(--text-secondary);
  font-size: 1.1rem;
  margin: 0 0 2rem 0;
}

.payment-features {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  background: var(--white);
  border-radius: 8px;
  border: 1px solid var(--deep-light);
  font-weight: 500;
  color: var(--text-primary);
}

.feature-item svg {
  color: var(--success-color);
  flex-shrink: 0;
}

.payment-btn {
  background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
  color: var(--white);
  border: none;
  border-radius: 12px;
  padding: 1.25rem 2.5rem;
  font-size: 1.1rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 6px 20px rgba(65, 90, 119, 0.3);
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0 auto;
}

.payment-btn:hover {
  background: linear-gradient(135deg, var(--primary-hover), var(--deep-navy));
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(65, 90, 119, 0.4);
}

.payment-btn svg {
  color: #ffd700;
}

/* Formulaire de paiement intégré */
.payment-action {
  margin-top: 2rem;
}

.stripe-payment-form {
  margin-top: 2rem;
  padding: 2rem;
  background: var(--white);
  border-radius: 12px;
  border: 2px solid var(--deep-light);
}

.payment-form-header {
  text-align: center;
  margin-bottom: 2rem;
}

.payment-form-header h3 {
  color: var(--text-primary);
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
}

.payment-form-header p {
  color: var(--text-secondary);
  margin: 0;
}


.payment-error {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  color: #dc2626;
  font-weight: 500;
}

.payment-error svg {
  flex-shrink: 0;
}

.payment-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.payment-info {
  text-align: center;
  margin-bottom: 1.5rem;
}

.payment-info p {
  color: var(--text-secondary);
  margin: 0 0 1rem 0;
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


.payment-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
}

.cancel-btn {
  background: var(--deep-light);
  color: var(--white);
  border: none;
  border-radius: 8px;
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s ease;
}

.cancel-btn:hover {
  background: #6b7280;
}

.submit-btn {
  background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
  color: var(--white);
  border: none;
  border-radius: 8px;
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.submit-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, var(--primary-hover), var(--deep-navy));
  transform: translateY(-1px);
}

.submit-btn:disabled {
  background: #9ca3af;
  cursor: not-allowed;
  transform: none;
}

.spinner-small {
  width: 16px;
  height: 16px;
  border: 2px solid transparent;
  border-top: 2px solid currentColor;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}


.success-message,
.error-message {
  position: fixed;
  top: 2rem;
  right: 2rem;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
  z-index: 1000;
  animation: slideIn 0.3s ease;
}

.success-message {
  background: var(--success-color);
  color: var(--white);
}

.error-message {
  background: var(--error-color);
  color: var(--white);
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
@media (max-width: 768px) {
  .premium-features-container {
    padding: 1rem;
  }
  
  .premium-header {
    margin-bottom: 2rem;
  }
  
  .header-content h1 {
    font-size: 2rem;
  }
  
  .payment-card {
    padding: 1.5rem;
  }
}

@media (max-width: 480px) {
  .header-content h1 {
    font-size: 1.5rem;
  }
  
  .payment-card {
    padding: 1rem;
  }
}
</style>
