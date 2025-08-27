# TaskForce - Système de Gestion Intelligente des Tâches

## 🚀 Vue d'ensemble

TaskForce est une application web moderne pour la gestion intelligente des tâches et la répartition automatique basée sur les compétences et la charge de travail. L'application sépare le front-end (Vue.js) et le back-end (Symfony) pour une architecture modulaire et évolutive.

## 🏗️ Architecture

```
Projet-Fil-Rouge/
├── frontend/          # Application Vue.js
├── backend/           # API Symfony
└── README.md
```

## 🛠️ Technologies utilisées

### Front-end
- **Vue.js 3** avec Composition API
- **TypeScript** pour le typage statique
- **Vue Router** pour la navigation
- **Pinia** pour la gestion d'état
- **ESLint & Prettier** pour la qualité du code

### Back-end
- **PHP 8.1+**
- **Symfony 6.4** (skeleton)
- **Doctrine ORM** pour la persistance des données
- **SQLite** comme base de données
- **Symfony Security Bundle** pour l'authentification
- **NelmioCorsBundle** pour la gestion CORS

## 👥 Acteurs et Rôles

### 1. Collaborateur
- **Rôle Symfony**: `ROLE_USER`
- **Fonction**: Utilisateur principal qui reçoit les tâches assignées
- **Permissions**: 
  - Voir ses tâches assignées
  - Mettre à jour le statut de ses tâches
  - Consulter ses compétences

### 2. Manager
- **Rôle Symfony**: `ROLE_USER`, `ROLE_MANAGER`
- **Fonction**: Superviseur qui suit la progression des tâches
- **Permissions**:
  - Toutes les permissions du Collaborateur
  - Créer et modifier des tâches
  - Gérer l'équipe
  - Voir les rapports de progression
  - Recevoir des alertes de surcharge

### 3. Responsable de Projet
- **Rôle Symfony**: `ROLE_USER`, `ROLE_MANAGER`, `ROLE_PROJECT_MANAGER`
- **Fonction**: Responsable de l'allocation initiale des tâches
- **Permissions**:
  - Toutes les permissions du Manager
  - Assigner des tâches automatiquement
  - Définir les compétences requises
  - Gérer les priorités des missions
  - Accéder aux analyses avancées

## 🔐 Système d'Authentification

### Fonctionnalités implémentées

#### ✅ Inscription
- Validation des données requises
- Vérification de l'unicité de l'email
- Hachage sécurisé des mots de passe
- Attribution automatique des rôles selon le type d'utilisateur
- Validation des entités avec contraintes

#### ✅ Connexion
- Vérification des identifiants
- Validation du mot de passe haché
- Mise à jour de la date de dernière connexion
- Retour des informations utilisateur avec rôles

#### ✅ Sécurité
- Protection contre les injections SQL
- Validation côté serveur
- Messages d'erreur sécurisés
- Gestion des sessions

### API Endpoints

#### Inscription
```http
POST /api/auth/register
Content-Type: application/json

{
  "firstName": "Jean",
  "lastName": "Dupont",
  "email": "jean.dupont@example.com",
  "password": "MotDePasse123!",
  "company": "TechCorp",
  "role": "collaborator"
}
```

**Réponse de succès (201):**
```json
{
  "message": "Compte créé avec succès",
  "user": {
    "id": 1,
    "email": "jean.dupont@example.com",
    "firstName": "Jean",
    "lastName": "Dupont",
    "fullName": "Jean Dupont",
    "company": "TechCorp",
    "role": "collaborator",
    "roles": ["ROLE_USER"],
    "createdAt": "2025-08-20 16:38:15"
  }
}
```

#### Connexion
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "jean.dupont@example.com",
  "password": "MotDePasse123!"
}
```

**Réponse de succès (200):**
```json
{
  "message": "Connexion réussie",
  "user": {
    "id": 1,
    "email": "jean.dupont@example.com",
    "firstName": "Jean",
    "lastName": "Dupont",
    "fullName": "Jean Dupont",
    "company": "TechCorp",
    "role": "collaborator",
    "roles": ["ROLE_USER"],
    "chargeTravail": 0,
    "createdAt": "2025-08-20 16:38:15",
    "updatedAt": "2025-08-20 16:38:37"
  }
}
```

## 🚀 Installation et Lancement

### Prérequis
- Node.js 18+
- PHP 8.1+
- Composer
- SQLite

### 1. Cloner le projet
```bash
git clone <repository-url>
cd Projet-Fil-Rouge
```

### 2. Configuration du Back-end
```bash
cd backend

# Installer les dépendances
composer install

# Créer la base de données
php bin/console doctrine:database:create

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Lancer le serveur Symfony
php -S 127.0.0.1:8000 -t public
```

### 3. Configuration du Front-end
```bash
cd frontend

# Installer les dépendances
npm install

# Lancer le serveur de développement
npm run dev
```

### 4. Accès à l'application
- **Front-end**: http://localhost:5173
- **Back-end API**: http://127.0.0.1:8000

## 🎯 Fonctionnalités Front-end

### Pages implémentées
1. **Page d'accueil** (`/`)
   - Design inspiré de Trello
   - Boutons d'inscription et de connexion
   - Interface moderne et responsive

2. **Page d'inscription** (`/register`)
   - Formulaire complet avec validation
   - Sélection du rôle (Collaborateur, Manager, Responsable de Projet)
   - Indicateur de force du mot de passe
   - Messages d'erreur et de succès

3. **Page de connexion** (`/login`)
   - Formulaire de connexion sécurisé
   - Validation des identifiants
   - Redirection automatique après connexion

4. **Dashboard** (`/dashboard`)
   - Interface adaptée selon le rôle de l'utilisateur
   - Affichage du prénom de l'utilisateur connecté
   - Actions spécifiques selon les permissions
   - Statistiques et vue d'ensemble

### Protection des routes
- **Routes publiques**: `/`, `/login`, `/register`
- **Routes protégées**: `/dashboard` (nécessite une authentification)
- **Redirection automatique** selon l'état d'authentification

## 🔧 Configuration CORS

Le back-end est configuré pour accepter les requêtes du front-end :

```yaml
# backend/config/packages/nelmio_cors.yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['*']
        allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization']
        expose_headers: ['Link']
        max_age: 3600
    paths:
        '^/api/':
            allow_origin: ['*']
            allow_headers: ['*']
            allow_methods: ['POST', 'PUT', 'GET', 'DELETE', 'OPTIONS']
```

## 📊 Base de Données

### Entités principales
- **User**: Utilisateurs avec rôles et compétences
- **Task**: Tâches avec assignation et suivi
- **Competence**: Compétences des utilisateurs
- **Notification**: Système de notifications

### Relations
- Un utilisateur peut avoir plusieurs compétences
- Un utilisateur peut être assigné à plusieurs tâches
- Un utilisateur peut créer plusieurs tâches
- Les notifications sont liées aux utilisateurs

## 🧪 Tests

### Test de l'API d'authentification

#### 1. Inscription d'un collaborateur
```bash
curl -X POST http://127.0.0.1:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "firstName": "Jean",
    "lastName": "Dupont",
    "email": "jean.dupont@example.com",
    "password": "MotDePasse123!",
    "company": "TechCorp",
    "role": "collaborator"
  }'
```

#### 2. Connexion
```bash
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "jean.dupont@example.com",
    "password": "MotDePasse123!"
  }'
```

#### 3. Test avec mauvais mot de passe
```bash
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "jean.dupont@example.com",
    "password": "MauvaisMotDePasse"
  }'
```

## 🔮 Prochaines étapes

### Fonctionnalités à implémenter
1. **JWT Authentication** pour une sécurité renforcée
2. **Gestion des tâches** (CRUD complet)
3. **Système de notifications** en temps réel
4. **Algorithme d'assignation automatique** basé sur les compétences
5. **Rapports et analyses** avancées
6. **Interface de gestion des compétences**
7. **Système de projets** avec hiérarchie

### Améliorations techniques
1. **Tests unitaires** et d'intégration
2. **Documentation API** avec OpenAPI/Swagger
3. **Optimisation des performances**
4. **Monitoring et logging**
5. **Déploiement en production**

## 📝 Notes de développement

### Points importants
- L'authentification utilise actuellement un système simple sans JWT
- Les sessions sont gérées côté client avec localStorage
- La validation est effectuée côté serveur et client
- L'interface s'adapte automatiquement selon les rôles

### Sécurité
- Les mots de passe sont hachés avec Symfony Security
- Validation stricte des données d'entrée
- Protection contre les injections SQL via Doctrine
- Messages d'erreur génériques pour éviter l'exposition d'informations

---

**TaskForce** - Gestion intelligente des tâches pour une productivité optimale 🚀

