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
                :class="{ 'active': showPassword }"
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
                :class="{ 'error': !passwordsMatch && formData.confirmPassword }"
              />
              <button
                type="button"
                @click="toggleConfirmPassword"
                class="password-toggle"
                :class="{ 'active': showConfirmPassword }"
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
              J'accepte les <a href="#" class="link-primary"> conditions d'utilisation</a> 
            </label>
          </div>

          <button type="submit" class="btn btn-primary btn-full" :disabled="isLoading || !passwordsMatch">
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
  acceptTerms: false
})

const showPassword = ref(false)
const showConfirmPassword = ref(false)
const isLoading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

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

const handleSignup = async () => {
  if (!passwordsMatch.value) {
    errorMessage.value = 'Les mots de passe ne correspondent pas'
    return
  }
  
  if (!formData.acceptTerms) {
    errorMessage.value = 'Veuillez accepter les conditions d\'utilisation'
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
      password: formData.password
    })
    
    successMessage.value = response.message
    
    setTimeout(() => {
      router.push('/login')
    }, 2000)
    
  } catch (error) {
    if (error instanceof Error) {
      errorMessage.value = error.message
    } else {
      errorMessage.value = 'Erreur inconnue lors de l\'inscription'
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
:root {
  --primary-color: #0079bf;
  --primary-hover: #005a8b;
  --text-primary: #2c3e50;
  --text-secondary: #6c757d;
  --white: #ffffff;
  --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
}

.checkbox-container input[type="checkbox"] {
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
</style>
