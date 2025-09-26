<?php

require_once 'vendor/autoload.php';

// Configuration Stripe - utiliser les variables d'environnement
$stripeSecretKey = $_ENV['STRIPE_SECRET_KEY'] ?? getenv('STRIPE_SECRET_KEY');

if (!$stripeSecretKey) {
    echo "❌ Erreur: La variable d'environnement STRIPE_SECRET_KEY n'est pas définie.\n";
    echo "Veuillez définir cette variable avec votre clé secrète Stripe.\n";
    exit(1);
}

\Stripe\Stripe::setApiKey($stripeSecretKey);

try {
    echo "Création du produit TaskForce Premium...\n";
    
    // Créer le produit
    $product = \Stripe\Product::create([
        'name' => 'TaskForce Premium',
        'description' => 'Abonnement Premium TaskForce avec fonctionnalités avancées et mode observateur',
        'type' => 'service',
    ]);
    
    echo "Produit créé avec l'ID: " . $product->id . "\n";
    
    // Créer le prix
    echo "Création du prix (29.99€/mois)...\n";
    
    $price = \Stripe\Price::create([
        'product' => $product->id,
        'unit_amount' => 2999, // 29.99€ en centimes
        'currency' => 'eur',
        'recurring' => [
            'interval' => 'month',
        ],
    ]);
    
    echo "Prix créé avec l'ID: " . $price->id . "\n";
    echo "Prix: " . ($price->unit_amount / 100) . "€ " . strtoupper($price->currency) . " par " . $price->recurring->interval . "\n";
    
    echo "\n✅ Produit et prix Stripe créés avec succès !\n";
    echo "Vous pouvez maintenant utiliser le système de paiement.\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
