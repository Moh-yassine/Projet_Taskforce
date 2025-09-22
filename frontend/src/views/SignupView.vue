<template>
  <div class="signup-container">
    <header class="header">
      <div class="header-content">
        <div class="logo">
          <router-link to="/" class="logo-link">
            <h1>TaskForce</h1>
          </router-link>
        </div>
        <nav class="nav-buttons">
          <router-link to="/login" class="btn btn-secondary">Se connecter</router-link>
          <router-link to="/signup" class="btn btn-primary active">S'inscrire</router-link>
        </nav>
      </div>
    </header>

    <main class="signup-section">
      <div class="signup-card">
        <div class="signup-header">
          <h2>Inscription à TaskForce</h2>
          <p>Créez votre compte pour commencer à organiser vos projets</p>
        </div>

        <div v-if="errorMessage" class="alert alert-error">
          {{ errorMessage }}
        </div>
        <div v-if="successMessage" class="alert alert-success">
          {{ successMessage }}
        </div>

        <form @submit.prevent="handleSignup" class="signup-form">
          <div class="form-row">
            <div class="form-group">
              <label for="firstName">Prénom</label>
              <input
                type="text"
                id="firstName"
                v-model="formData.firstName"
                required
                placeholder="Votre prénom"
                class="form-input"
              />
            </div>
            <div class="form-group">
              <label for="lastName">Nom</label>
              <input
                type="text"
                id="lastName"
                v-model="formData.lastName"
                required
                placeholder="Votre nom"
                class="form-input"
              />
            </div>
          </div>

          <div class="form-group">
            <label for="email">Email</label>
            <input
              type="email"
              id="email"
              v-model="formData.email"
              required
              placeholder="votre@email.com"
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label for="company">Entreprise (optionnel)</label>
            <input
              type="text"
              id="company"
              v-model="formData.company"
              placeholder="Nom de votre entreprise"
              class="form-input"
            />
          </div>

          <div class="form-group">
            <label for="password">Mot de passe</label>
            <div class="password-input-container">
              <input
                :type="showPassword ? 'text' : 'password'"
                id="password"
                v-model="formData.password"
                required
                placeholder="Créez un mot de passe"
                class="form-input"
              />
              <button
                type="button"
                @click="togglePassword"
                class="password-toggle"
                :class="{ active: showPassword }"
              >
                <span v-if="showPassword">👁️</span>
                <span v-else>👁️‍🗨️</span>
              </button>
            </div>
            <div class="password-strength" v-if="passwordStrength">
              <div class="strength-bar">
                <div class="strength-fill" :class="passwordStrengthClass"></div>
              </div>
              <span class="strength-text">{{ passwordStrengthText }}</span>
            </div>
          </div>

          <div class="form-group">
            <label for="confirmPassword">Confirmer le mot de passe</label>
            <div class="password-input-container">
              <input
                :type="showConfirmPassword ? 'text' : 'password'"
                id="confirmPassword"
                v-model="formData.confirmPassword"
                required
                placeholder="Confirmez votre mot de passe"
                class="form-input"
                :class="{ error: !passwordsMatch && formData.confirmPassword }"
              />
              <button
                type="button"
                @click="toggleConfirmPassword"
                class="password-toggle"
                :class="{ active: showConfirmPassword }"
              >
                <span v-if="showConfirmPassword">👁️</span>
                <span v-else>👁️‍🗨️</span>
              </button>
            </div>
            <div v-if="!passwordsMatch && formData.confirmPassword" class="error-message">
              Les mots de passe ne correspondent pas
            </div>
          </div>

          <div class="form-options">
            <label class="checkbox-container">
              <input type="checkbox" v-model="formData.acceptTerms" required />
              <span class="checkmark"></span>
              J'accepte les <a href="#" @click.prevent="showTermsModal = true" class="link-primary">conditions d'utilisation</a>
            </label>
          </div>

          <button
            type="submit"
            class="btn btn-primary btn-full"
            :disabled="isLoading || !passwordsMatch"
          >
            <span v-if="isLoading" class="loading-spinner"></span>
            {{ isLoading ? 'Création du compte...' : 'Créer mon compte' }}
          </button>
        </form>

        <div class="signup-footer">
          <p>
            Déjà un compte ?
            <router-link to="/login" class="link-primary">Se connecter</router-link>
          </p>
        </div>
      </div>
    </main>

    <footer class="footer">
      <div class="container">
        <div class="footer-content">
          <div class="footer-section">
            <h3>TaskForce</h3>
            <p>Organisez vos projets efficacement</p>
          </div>
        </div>
        <div class="footer-bottom">
          <p>&copy; 2024 TaskForce. Tous droits réservés.</p>
        </div>
      </div>
    </footer>

    <!-- Modal des conditions d'utilisation -->
    <div v-if="showTermsModal" class="modal-overlay" @click="showTermsModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>Conditions d'utilisation</h3>
          <button @click="showTermsModal = false" class="modal-close">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
              <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
          </button>
        </div>
        <div class="modal-body">
          <div class="terms-content">
            <h4>1. Acceptation des conditions</h4>
            <p>En utilisant TaskForce, vous acceptez ces conditions d'utilisation. Si vous n'acceptez pas ces conditions, veuillez ne pas utiliser notre service.</p>

            <h4>2. Description du service</h4>
            <p>TaskForce est une plateforme de gestion de projets et d'assignation automatique de tâches qui permet aux équipes de collaborer efficacement.</p>

            <h4>3. Compte utilisateur</h4>
            <p>Vous êtes responsable de maintenir la confidentialité de votre compte et de votre mot de passe. Vous acceptez d'assumer la responsabilité de toutes les activités qui se produisent sous votre compte.</p>

            <h4>4. Utilisation acceptable</h4>
            <p>Vous vous engagez à utiliser TaskForce de manière légale et respectueuse. Il est interdit d'utiliser le service pour :</p>
            <ul>
              <li>Des activités illégales ou frauduleuses</li>
              <li>Harceler ou nuire à d'autres utilisateurs</li>
              <li>Transmettre des virus ou codes malveillants</li>
              <li>Violer les droits de propriété intellectuelle</li>
            </ul>

            <h4>5. Protection des données</h4>
            <p>Nous nous engageons à protéger vos données personnelles conformément à notre politique de confidentialité et au RGPD.</p>

            <h4>6. Propriété intellectuelle</h4>
            <p>TaskForce et son contenu sont protégés par les lois sur la propriété intellectuelle. Vous ne pouvez pas copier, modifier ou distribuer notre contenu sans autorisation.</p>

            <h4>7. Limitation de responsabilité</h4>
            <p>TaskForce est fourni "en l'état" sans garantie. Nous ne sommes pas responsables des dommages directs ou indirects résultant de l'utilisation du service.</p>

            <h4>8. Modification des conditions</h4>
            <p>Nous nous réservons le droit de modifier ces conditions à tout moment. Les modifications prendront effet dès leur publication sur le site.</p>

            <h4>9. Résiliation</h4>
            <p>Nous pouvons suspendre ou résilier votre compte à tout moment en cas de violation de ces conditions.</p>

            <h4>10. Contact</h4>
            <p>Pour toute question concernant ces conditions, contactez-nous à : support@taskforce.com</p>

            <p class="terms-date"><strong>Dernière mise à jour :</strong> 22 septembre 2025</p>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="showTermsModal = false" class="btn btn-secondary">Fermer</button>
          <button @click="acceptTerms" class="btn btn-primary">J'accepte</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'
import { authService } from '@/services/authService'

const router = useRouter()

const formData = reactive({
  firstName: '',
  lastName: '',
  email: '',
  company: '',
  password: '',
  confirmPassword: '',
  acceptTerms: false,
})

const showPassword = ref(false)
const showConfirmPassword = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const showTermsModal = ref(false)

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const toggleConfirmPassword = () => {
  showConfirmPassword.value = !showConfirmPassword.value
}

const passwordsMatch = computed(() => {
  return formData.password === formData.confirmPassword
})

const passwordStrength = computed(() => {
  if (!formData.password) return null

  let score = 0
  if (formData.password.length >= 8) score++
  if (/[a-z]/.test(formData.password)) score++
  if (/[A-Z]/.test(formData.password)) score++
  if (/[0-9]/.test(formData.password)) score++
  if (/[^A-Za-z0-9]/.test(formData.password)) score++

  return score
})

const passwordStrengthClass = computed(() => {
  if (!passwordStrength.value) return ''
  if (passwordStrength.value <= 2) return 'weak'
  if (passwordStrength.value <= 3) return 'medium'
  return 'strong'
})

const passwordStrengthText = computed(() => {
  if (!passwordStrength.value) return ''
  if (passwordStrength.value <= 2) return 'Faible'
  if (passwordStrength.value <= 3) return 'Moyen'
  return 'Fort'
})

const acceptTerms = () => {
  formData.acceptTerms = true
  showTermsModal.value = false
}

const handleSignup = async () => {
  if (!passwordsMatch.value) {
    errorMessage.value = 'Les mots de passe ne correspondent pas'
    return
  }

  if (!formData.acceptTerms) {
    errorMessage.value = "Veuillez accepter les conditions d'utilisation"
    return
  }

  isLoading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await authService.register({
      firstName: formData.firstName,
      lastName: formData.lastName,
      email: formData.email,
      company: formData.company,
      password: formData.password,
    })

    successMessage.value = response.message

    setTimeout(() => {
      router.push('/login')
    }, 2000)
  } catch (error) {
    if (error instanceof Error) {
      errorMessage.value = error.message
    } else {
      errorMessage.value = "Erreur inconnue lors de l'inscription"
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
:root {
  /* Palette Deep Sea */
  --deep-dark: #0d1b2a;
  --deep-navy: #1b263b;
  --deep-blue: #415a77;
  --deep-light: #778da9;
  --deep-pale: #e0e1dd;

  /* Couleurs principales */
  --primary-color: var(--deep-blue);
  --primary-hover: var(--deep-navy);
  --text-primary: var(--deep-dark);
  --text-secondary: var(--deep-blue);
  --white: #ffffff;
  --shadow: 0 4px 6px rgba(13, 27, 42, 0.1);
  --shadow-hover: 0 8px 15px rgba(0, 0, 0, 0.15);
  --error-color: #dc3545;
  --success-color: #28a745;
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.signup-container {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: var(--white);
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.header {
  background: var(--white);
  box-shadow: var(--shadow);
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
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

.logo-link {
  text-decoration: none;
  color: inherit;
}

.logo h1 {
  color: var(--primary-color);
  font-size: 2rem;
  font-weight: 800;
  letter-spacing: -0.5px;
}

.nav-buttons {
  display: flex;
  gap: 1.5rem;
}

.btn {
  padding: 1rem 2rem;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  text-decoration: none;
  display: inline-block;
  text-align: center;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  position: relative;
  overflow: hidden;
}

.btn::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.5s;
}

.btn:hover::before {
  left: 100%;
}

.btn-primary {
  background: var(--primary-color);
  color: var(--white);
  box-shadow: 0 4px 15px rgba(0, 121, 191, 0.3);
}

.btn-primary:hover:not(:disabled) {
  background: var(--primary-hover);
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(0, 121, 191, 0.4);
}

.btn-primary.active {
  background: var(--primary-hover);
}

.btn-secondary {
  background: var(--white);
  color: var(--primary-color);
  border: 3px solid var(--primary-color);
  box-shadow: 0 4px 15px rgba(0, 121, 191, 0.1);
}

.btn-secondary:hover {
  background: var(--primary-color);
  color: var(--white);
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(0, 121, 191, 0.3);
}

.btn-full {
  width: 100%;
  padding: 1rem;
  font-size: 1.1rem;
  margin-top: 1rem;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.signup-section {
  padding: 8rem 2rem 4rem;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 1;
}

.signup-card {
  background: var(--white);
  border-radius: 16px;
  padding: 3rem;
  box-shadow: var(--shadow-hover);
  width: 100%;
  max-width: 600px;
  animation: slideUp 0.6s ease-out;
  border: 1px solid #e1e5e9;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.signup-header {
  text-align: center;
  margin-bottom: 2rem;
}

.signup-header h2 {
  font-size: 2rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 0.5rem;
}

.signup-header p {
  color: var(--text-secondary);
  font-size: 1rem;
}

.alert {
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  font-weight: 500;
}

.alert-error {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.alert-success {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.signup-form {
  margin-bottom: 2rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.9rem;
}

.form-input {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 2px solid #e1e5e9;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s ease;
  background: var(--white);
  color: var(--text-primary);
}

.form-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(0, 121, 191, 0.1);
}

.form-input::placeholder {
  color: var(--text-secondary);
}

select.form-input {
  cursor: pointer;
  background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right 1rem center;
  background-size: 1rem;
  padding-right: 3rem;
  appearance: none;
}

.form-input.error {
  border-color: var(--error-color);
}

.form-help {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.8rem;
  color: var(--text-secondary);
  font-style: italic;
}

.password-input-container {
  position: relative;
}

.password-toggle {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.2rem;
  padding: 0.25rem;
  border-radius: 4px;
  transition: background-color 0.2s ease;
}

.password-toggle:hover {
  background: rgba(0, 0, 0, 0.05);
}

.password-toggle.active {
  background: rgba(0, 121, 191, 0.1);
}

.password-strength {
  margin-top: 0.5rem;
}

.strength-bar {
  width: 100%;
  height: 4px;
  background: #e1e5e9;
  border-radius: 2px;
  overflow: hidden;
  margin-bottom: 0.25rem;
}

.strength-fill {
  height: 100%;
  transition: all 0.3s ease;
}

.strength-fill.weak {
  width: 40%;
  background: var(--error-color);
}

.strength-fill.medium {
  width: 70%;
  background: #ffc107;
}

.strength-fill.strong {
  width: 100%;
  background: var(--success-color);
}

.strength-text {
  font-size: 0.8rem;
  color: var(--text-secondary);
}

.error-message {
  color: var(--error-color);
  font-size: 0.8rem;
  margin-top: 0.25rem;
}

.form-options {
  margin-bottom: 1.5rem;
}

.checkbox-container {
  display: flex;
  align-items: flex-start;
  cursor: pointer;
  font-size: 0.9rem;
  color: var(--text-secondary);
  line-height: 1.4;
  word-spacing: normal;
}

.checkbox-container .link-primary {
  margin-left: 0.25rem;
}

.checkbox-container input[type='checkbox'] {
  margin-right: 0.5rem;
  margin-top: 0.1rem;
  width: 16px;
  height: 16px;
  accent-color: var(--primary-color);
}

.link-primary {
  color: var(--primary-color);
  text-decoration: none;
  font-weight: 600;
}

.link-primary:hover {
  text-decoration: underline;
}

.signup-footer {
  text-align: center;
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.loading-spinner {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid transparent;
  border-top: 2px solid currentColor;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-right: 0.5rem;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.footer {
  background: var(--text-primary);
  color: var(--white);
  padding: 4rem 0 2rem;
  margin-top: auto;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
}

.footer-content {
  text-align: center;
  margin-bottom: 3rem;
}

.footer-section h3 {
  color: var(--white);
  font-size: 2.5rem;
  font-weight: 800;
  margin-bottom: 1rem;
}

.footer-section p {
  color: #bdc3c7;
  line-height: 1.6;
  font-size: 1.2rem;
}

.footer-bottom {
  border-top: 1px solid #34495e;
  padding-top: 2rem;
  text-align: center;
  color: #bdc3c7;
}

@media (max-width: 768px) {
  .signup-section {
    padding: 6rem 1rem 2rem;
  }

  .signup-card {
    padding: 2rem;
    margin: 0 1rem;
  }

  .signup-header h2 {
    font-size: 1.75rem;
  }

  .form-row {
    grid-template-columns: 1fr;
    gap: 0;
  }

  .header-content {
    padding: 0 1rem;
  }

  .nav-buttons {
    gap: 1rem;
  }

  .btn {
    padding: 0.8rem 1.5rem;
    font-size: 0.9rem;
  }

  .footer-section h3 {
    font-size: 2rem;
  }
}

@media (max-width: 480px) {
  .signup-card {
    padding: 1.5rem;
  }

  .signup-header h2 {
    font-size: 1.5rem;
  }

  .btn-full {
    padding: 0.875rem;
    font-size: 1rem;
  }

  .footer-section h3 {
    font-size: 1.8rem;
  }
}

/* Modal des conditions d'utilisation */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  padding: 2rem;
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

.modal-content {
  background: var(--white);
  border-radius: 16px;
  max-width: 700px;
  width: 100%;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: slideUp 0.4s ease-out;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem 2rem 1rem;
  border-bottom: 1px solid #e1e5e9;
}

.modal-header h3 {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0;
}

.modal-close {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 50%;
  color: var(--text-secondary);
  transition: all 0.2s ease;
}

.modal-close:hover {
  background: rgba(0, 0, 0, 0.05);
  color: var(--text-primary);
}

.modal-body {
  padding: 1rem 2rem;
  overflow-y: auto;
  flex: 1;
}

.terms-content h4 {
  color: var(--primary-color);
  font-size: 1.1rem;
  font-weight: 600;
  margin: 1.5rem 0 0.75rem 0;
  padding-top: 1rem;
  border-top: 1px solid #f0f0f0;
}

.terms-content h4:first-child {
  margin-top: 0;
  padding-top: 0;
  border-top: none;
}

.terms-content p {
  color: var(--text-secondary);
  line-height: 1.6;
  margin-bottom: 1rem;
}

.terms-content ul {
  color: var(--text-secondary);
  line-height: 1.6;
  margin: 0.5rem 0 1rem 1.5rem;
}

.terms-content li {
  margin-bottom: 0.5rem;
}

.terms-date {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
  margin-top: 2rem;
  font-size: 0.9rem;
  color: var(--text-secondary);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1rem 2rem 2rem;
  border-top: 1px solid #e1e5e9;
}

.modal-footer .btn {
  padding: 0.75rem 1.5rem;
  font-size: 0.9rem;
}

@media (max-width: 768px) {
  .modal-overlay {
    padding: 1rem;
  }

  .modal-content {
    max-height: 90vh;
  }

  .modal-header,
  .modal-body,
  .modal-footer {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
  }

  .modal-footer {
    flex-direction: column;
  }

  .modal-footer .btn {
    width: 100%;
  }
}
</style>
