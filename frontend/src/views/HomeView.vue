<template>
  <div class="home-container">
    <header class="header">
      <div class="header-content">
        <div class="logo">
          <h1>TaskForce</h1>
        </div>
        <nav class="nav-buttons">
          <button class="btn btn-secondary" @click="handleLogin">Se connecter</button>
          <button class="btn btn-primary" @click="handleSignup">S'inscrire</button>
        </nav>
      </div>
    </header>

    <main class="hero-section">
      <div class="hero-content">
        <div class="hero-text">
          <h1 class="hero-title">
            Organisez vos projets avec
            <span class="highlight">TaskForce</span>
          </h1>
          <p class="hero-description">
            Une plateforme collaborative pour gérer vos tâches, organiser vos projets et travailler
            efficacement en équipe.
          </p>
          <div class="hero-buttons">
            <button class="btn btn-primary btn-large" @click="handleSignup">
              Commencer gratuitement
            </button>
          </div>
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
import { useRouter } from 'vue-router'

const router = useRouter()

const handleLogin = () => {
  router.push('/login')
}

const handleSignup = () => {
  router.push('/signup')
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
  --shadow-hover: 0 8px 15px rgba(0, 0, 0, 0.15);
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

.home-container {
  min-height: 100vh;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  color: var(--text-primary);
  background: var(--white);
  display: flex;
  flex-direction: column;
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

.logo h1 {
  color: var(--text-primary);
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

.btn-primary:hover {
  background: var(--primary-hover);
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(0, 121, 191, 0.4);
}

.btn-secondary {
  background: transparent;
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

.btn-large {
  padding: 1.25rem 3rem;
  font-size: 1.1rem;
  font-weight: 800;
  border-radius: 12px;
}

.hero-section {
  padding: 8rem 2rem 6rem;
  background: var(--white);
  color: var(--text-primary);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  flex: 1;
}

.hero-content {
  max-width: 800px;
  margin: 0 auto;
}

.hero-title {
  font-size: 4rem;
  font-weight: 900;
  line-height: 1.1;
  margin-bottom: 2rem;
  letter-spacing: -1px;
  color: var(--text-primary);
}

.highlight {
  color: var(--primary-color);
}

.hero-description {
  font-size: 1.4rem;
  line-height: 1.6;
  margin-bottom: 3rem;
  color: var(--text-secondary);
  font-weight: 400;
}

.hero-buttons {
  display: flex;
  justify-content: center;
  gap: 2rem;
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
  .hero-title {
    font-size: 2.5rem;
  }

  .hero-description {
    font-size: 1.2rem;
  }

  .hero-buttons {
    flex-direction: column;
    align-items: center;
  }

  .btn-large {
    width: 100%;
    max-width: 300px;
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
  .hero-section {
    padding: 6rem 1rem 4rem;
  }

  .hero-title {
    font-size: 2rem;
  }

  .hero-description {
    font-size: 1rem;
  }

  .btn {
    padding: 0.7rem 1.2rem;
    font-size: 0.8rem;
  }

  .footer-section h3 {
    font-size: 1.8rem;
  }
}
</style>
