<template>
  <div v-if="isOpen" class="modal-overlay" @click="closeModal">
    <div class="modal-container" @click.stop>
      <div class="modal-header">
        <div class="modal-title-section">
          <div class="premium-badge">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
            <span>PREMIUM</span>
          </div>
          <h2>Activez votre abonnement Premium</h2>
          <p>Débloquez toutes les fonctionnalités avancées pour seulement 29.99€/mois</p>
        </div>
        <button @click="closeModal" class="close-btn">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
            <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
          </svg>
        </button>
      </div>

      <div class="modal-content">
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
                    <p>Activez votre abonnement premium pour accéder au mode observateur</p>
                  </div>
          
          <div v-if="paymentError" class="payment-error">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
            </svg>
            <span>{{ paymentError }}</span>
          </div>
          
                  <div class="payment-form">
                    <div class="payment-info">
                      <p>Votre abonnement premium sera activé immédiatement après confirmation. Vous aurez accès à toutes les fonctionnalités avancées.</p>
                      <div class="security-badges">
                        <div class="security-badge">
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                          </svg>
                          <span>Activation sécurisée</span>
                        </div>
                        <div class="security-badge">
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                          </svg>
                          <span>Accès immédiat</span>
                        </div>
                      </div>
                    </div>
            
                    <div class="payment-actions">
                      <button type="button" @click="showPaymentForm = false" class="cancel-btn">
                        Annuler
                      </button>
            <button @click="handlePayment" :disabled="isProcessing" class="submit-btn">
              <span v-if="isProcessing" class="spinner-small"></span>
              <span v-else>Payer avec Stripe - 29.99€/mois</span>
            </button>
                    </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { paymentService } from '@/services/paymentService'
import { authService } from '@/services/authService'

// Props
interface Props {
  isOpen: boolean
}

const props = defineProps<Props>()

// Emits
const emit = defineEmits<{
  close: []
}>()

// État du composant
const showPaymentForm = ref(false)
const paymentError = ref('')
const isProcessing = ref(false)

const closeModal = () => {
  emit('close')
  // Reset form state when closing
  showPaymentForm.value = false
  paymentError.value = ''
  isProcessing.value = false
}


const handlePayment = async () => {
  isProcessing.value = true
  paymentError.value = ''

  try {
    // Vérifier l'authentification avant de procéder
    if (!authService.isAuthenticated()) {
      paymentError.value = 'Session expirée. Redirection vers la page de connexion...'
      setTimeout(() => {
        window.location.href = '/login'
      }, 1500)
      isProcessing.value = false
      return
    }

    // Vérifier l'authentification côté serveur d'abord
    try {
      const config = await paymentService.getConfig()
      console.log('✅ Authentification serveur validée:', config.user.email)
    } catch (authError) {
      console.error('❌ Erreur d\'authentification serveur:', authError)
      paymentError.value = 'Session expirée. Reconnexion automatique...'
      authService.forceReconnect()
      isProcessing.value = false
      return
    }

    // Utiliser le vrai flux Stripe Checkout
    const result = await paymentService.createSubscription()
    
    if (result.success && result.checkout_url) {
      // Rediriger vers Stripe Checkout pour le paiement
      window.location.href = result.checkout_url
    } else {
      throw new Error(result.error || 'Erreur lors de la création de la session de paiement')
    }
  } catch (error) {
    console.error('Erreur lors du paiement:', error)
    
    // Gérer les erreurs d'authentification automatiquement
    if (error instanceof Error && (error.message.includes('Non authentifié') || error.message.includes('401'))) {
      paymentError.value = 'Session expirée. Reconnexion automatique...'
      
      // Forcer une reconnexion complète
      authService.forceReconnect()
    } else {
      paymentError.value = error instanceof Error ? error.message : 'Erreur lors du paiement'
    }
    
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

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  animation: fadeIn 0.3s ease;
  padding: 1rem;
}

.modal-container {
  background: var(--white);
  border-radius: 16px;
  max-width: 700px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: slideIn 0.3s ease;
}

.modal-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 2rem 2rem 1rem 2rem;
  border-bottom: 1px solid var(--deep-pale);
  text-align: center;
  position: relative;
}

.modal-title-section {
  flex: 1;
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

.modal-title-section h2 {
  color: var(--text-primary);
  font-size: 1.8rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
}

.modal-title-section p {
  color: var(--text-secondary);
  font-size: 1rem;
  margin: 0;
  line-height: 1.5;
}

.close-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 8px;
  color: var(--deep-light);
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  position: absolute;
  top: 1rem;
  right: 1rem;
}

.close-btn:hover {
  background: var(--deep-pale);
  color: var(--text-primary);
}

.modal-content {
  padding: 1rem 2rem 2rem 2rem;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.payment-features {
  margin-bottom: 2rem;
  width: 100%;
  max-width: 500px;
  display: flex;
  justify-content: center;
}

.feature-item {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 1rem;
  background: var(--deep-pale);
  border-radius: 8px;
  border: 1px solid var(--deep-light);
  font-weight: 500;
  color: var(--text-primary);
  text-align: center;
}

.feature-item svg {
  color: var(--success-color);
  flex-shrink: 0;
}

.payment-action {
  text-align: center;
  width: 100%;
  max-width: 500px;
  display: flex;
  justify-content: center;
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
  justify-content: center;
  gap: 0.75rem;
  margin: 0 auto;
  text-align: center;
}

.payment-btn:hover {
  background: linear-gradient(135deg, var(--primary-hover), var(--deep-navy));
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(65, 90, 119, 0.4);
}

.payment-btn svg {
  color: #ffd700;
}

.stripe-payment-form {
  margin-top: 2rem;
  padding: 2rem;
  background: var(--deep-pale);
  border-radius: 12px;
  border: 2px solid var(--deep-light);
  width: 100%;
  max-width: 500px;
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
  margin-bottom: 1.5rem;
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
  flex-wrap: wrap;
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

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .modal-overlay {
    padding: 0.5rem;
  }
  
  .modal-container {
    max-height: 95vh;
  }
  
  .modal-header {
    padding: 1.5rem 1.5rem 1rem 1.5rem;
  }
  
  .modal-content {
    padding: 1rem 1.5rem 1.5rem 1.5rem;
  }
  
  .modal-title-section h2 {
    font-size: 1.5rem;
  }
  
  .payment-btn {
    padding: 1rem 2rem;
    font-size: 1rem;
  }
  
  .payment-actions {
    flex-direction: column;
  }
  
  .cancel-btn,
  .submit-btn {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .modal-header {
    padding: 1rem 1rem 0.5rem 1rem;
  }
  
  .modal-content {
    padding: 0.5rem 1rem 1rem 1rem;
  }
  
  .modal-title-section h2 {
    font-size: 1.3rem;
  }
  
  .premium-badge {
    padding: 0.5rem 1rem;
    font-size: 0.8rem;
  }
}
</style>
