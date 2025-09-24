<?php

require_once 'vendor/autoload.php';

// Test avec des clés Stripe de test publiques
$secretKey = 'sk_test_4eC39HqLyjWDarjtT1zdp7dc'; // Clé de test publique Stripe

echo "Test avec des clés Stripe de test publiques...\n";
echo "Clé secrète: " . substr($secretKey, 0, 20) . "...\n\n";

try {
    \Stripe\Stripe::setApiKey($secretKey);
    
    // Test simple - récupérer les comptes
    $account = \Stripe\Account::retrieve();
    
    echo "✅ Connexion réussie avec les clés de test !\n";
    echo "Compte Stripe: " . $account->display_name . "\n";
    echo "ID du compte: " . $account->id . "\n";
    
    echo "\n🎉 Le SDK Stripe fonctionne correctement !\n";
    echo "Le problème vient probablement de la clé API que vous avez fournie.\n";
    echo "Vérifiez que vous avez bien copié la clé secrète complète depuis votre dashboard Stripe.\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Type d'erreur: " . get_class($e) . "\n";
}
