## 🚀 Démarrage rapide

### Cloner le projet 

git clone https://github.com/Moh-yassine/Projet_Taskforce.git

cd Projet_Taskforce

### Commande
```bash
docker-compose up -d
```

C'est tout ! Le projet se lance automatiquement avec :
- ✅ Base de données MySQL avec toutes les tables et données
- ✅ Backend Symfony prêt à l'emploi
- ✅ Frontend Vue.js compilé
- ✅ phpMyAdmin pour gérer la base de données

## 📋 Services disponibles

| Service | Port | Description |
|---------|------|-------------|
| **Frontend** | 5173 | Application Vue.js |
| **Backend API** | 8000 | API Symfony |
| **Base de données** | 3306 | MySQL 8.0 |
| **phpMyAdmin** | 8080 | Interface web pour MySQL |

## 🔧 Configuration

### Variables d'environnement
Le fichier `docker.env` contient toutes les variables de configuration :

```env
# Base de données
MYSQL_ROOT_PASSWORD=root
MYSQL_DATABASE=taskforce
MYSQL_USER=taskforce
MYSQL_PASSWORD=taskforce

# Backend Symfony
APP_ENV=dev
DATABASE_URL=mysql://taskforce:taskforce@database:3306/taskforce

# Frontend
NODE_ENV=production
```

### Accès aux services

- **Frontend**: http://localhost:5173
- **Backend API**: http://127.0.0.1:8000
- **Base de données MySQL**: 
  - Host: localhost
  - Port: 3306
  - Database: taskforce_db
  - User: root
  - Password: root
- **phpMyAdmin**: http://localhost:8080
  - User: root
  - Password: root

## 🛠️ Commandes utiles

```bash
# Voir le statut des services
docker-compose ps

# Voir les logs de tous les services
docker-compose logs -f

# Voir les logs d'un service spécifique
docker-compose logs -f backend
docker-compose logs -f frontend
docker-compose logs -f database

# Arrêter tous les services
docker-compose down

# Redémarrer un service
docker-compose restart backend

# Reconstruire et redémarrer
docker-compose up -d --build