<?php

require_once 'vendor/autoload.php';

// Test avec vos vraies clés Stripe
$secretKey = 'sk_test_51S5oNDRyNP7K69mNIagsNjPlXg9Ndd1101C7pRjH3mIjQIfa2UnswStmLaBlRZhzlBOJEydjQcWym6p8aVpH4kxf00mcwmHhCC';

echo "Test de connexion à Stripe avec vos vraies clés...\n";
echo "Clé secrète: " . substr($secretKey, 0, 20) . "...\n\n";

try {
    \Stripe\Stripe::setApiKey($secretKey);
    
    // Test simple - récupérer les comptes
    $account = \Stripe\Account::retrieve();
    
    echo "✅ Connexion réussie !\n";
    echo "Compte Stripe: " . $account->display_name . "\n";
    echo "ID du compte: " . $account->id . "\n";
    echo "Pays: " . $account->country . "\n";
    echo "Devise par défaut: " . $account->default_currency . "\n";
    
    // Test de création d'un produit
    echo "\nTest de création d'un produit...\n";
    
    $product = \Stripe\Product::create([
        'name' => 'TaskForce Premium',
        'description' => 'Abonnement Premium TaskForce avec fonctionnalités avancées et mode observateur',
        'type' => 'service',
    ]);
    
    echo "✅ Produit créé avec l'ID: " . $product->id . "\n";
    
    // Test de création d'un prix
    echo "\nTest de création d'un prix...\n";
    
    $price = \Stripe\Price::create([
        'product' => $product->id,
        'unit_amount' => 2999, // 29.99€
        'currency' => 'eur',
        'recurring' => [
            'interval' => 'month',
        ],
    ]);
    
    echo "✅ Prix créé avec l'ID: " . $price->id . "\n";
    echo "Prix: " . ($price->unit_amount / 100) . "€ " . strtoupper($price->currency) . " par " . $price->recurring->interval . "\n";
    
    echo "\n🎉 Tous les tests sont passés ! Vos clés Stripe fonctionnent parfaitement.\n";
    echo "Vous pouvez maintenant utiliser le système de paiement réel.\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Type d'erreur: " . get_class($e) . "\n";
    
    if ($e instanceof \Stripe\Exception\InvalidRequestException) {
        echo "Détails: " . $e->getJsonBody()['error']['message'] . "\n";
    }
}
