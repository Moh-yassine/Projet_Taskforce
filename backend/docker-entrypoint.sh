#!/bin/bash
set -e

echo "🚀 Démarrage du backend Symfony..."

# Attendre que la base de données soit prête
echo "⏳ Attente de la base de données..."
until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
    echo "Base de données non disponible, attente..."
    sleep 2
done

echo "✅ Base de données disponible !"

# Vérifier que la base de données et les tables existent
echo "🔍 Vérification de la base de données taskforce_db..."
php bin/console doctrine:query:sql "SHOW TABLES" > /dev/null 2>&1 && echo "✅ Tables trouvées dans taskforce_db" || echo "⚠️  Aucune table trouvée"

# Marquer toutes les migrations comme exécutées (les données sont déjà créées par le script SQL)
echo "📝 Marquage des migrations comme exécutées..."
php bin/console doctrine:migrations:version --add --all --no-interaction || echo "⚠️  Impossible de marquer les migrations"

echo "✅ Backend prêt ! Démarrage du serveur..."

# Exécuter la commande passée en paramètre
exec "$@"
