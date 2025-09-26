# Dockerfile pour le frontend Vue.js
FROM node:20-alpine AS builder

# Installer les outils nécessaires
RUN apk add --no-cache python3 make g++

# Définir le répertoire de travail
WORKDIR /app

# Copier les fichiers de dépendances
COPY package*.json ./

# Installer toutes les dépendances
RUN npm ci

# Copier le code source
COPY . .

# Build de l'application pour la production (ignorer les erreurs TypeScript)
RUN npm run build-only

# Stage de production
FROM node:20-alpine AS production

# Définir le répertoire de travail
WORKDIR /app

# Copier les fichiers de dépendances
COPY package*.json ./

# Installer seulement les dépendances de production
RUN npm ci --only=production

# Copier les fichiers buildés depuis le stage builder
COPY --from=builder /app/dist ./dist

# Exposer le port
EXPOSE 5173

# Commande pour servir l'application
CMD ["npx", "vite", "preview", "--port", "5173", "--host"]
