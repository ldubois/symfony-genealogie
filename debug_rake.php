<?php

// Test de débogage pour identifier le problème avec la courbe qui commence au-dessus du râteau
echo "Débogage des coordonnées du râteau\n";
echo "==================================\n\n";

// Simulation des positions des parents (comme dans le code réel)
$parentPositions = [
    ['centerX' => 100, 'y' => 50, 'height' => 120],  // Parent gauche
    ['centerX' => 300, 'y' => 50, 'height' => 120]   // Parent droit
];

$childPositions = [
    ['centerX' => 200, 'y' => 300, 'height' => 120]  // Un enfant
];

echo "1. Calcul des positions de base :\n";
$parentCenterX = array_sum(array_map(fn($p) => $p['centerX'], $parentPositions)) / count($parentPositions);
$parentBottomY = max(array_map(fn($p) => $p['y'] + $p['height'], $parentPositions)) + 15;

echo "- parentCenterX = {$parentCenterX}\n";
echo "- parentBottomY = {$parentBottomY}\n\n";

echo "2. Calcul du râteau des parents :\n";
$rakeY = null;
$rakeX = null;

if (count($parentPositions) > 1) {
    $leftParent = min(array_map(fn($p) => $p['centerX'], $parentPositions));
    $rightParent = max(array_map(fn($p) => $p['centerX'], $parentPositions));
    $rakeY = $parentBottomY + 25;
    $rakeX = ($leftParent + $rightParent) / 2;
    
    echo "- leftParent = {$leftParent}\n";
    echo "- rightParent = {$rightParent}\n";
    echo "- rakeY = {$rakeY}\n";
    echo "- rakeX = {$rakeX}\n";
    
    // Mise à jour de parentBottomY
    $parentBottomY = $rakeY;
    echo "- parentBottomY mis à jour = {$parentBottomY}\n";
} else {
    echo "- Pas de râteau (un seul parent)\n";
}

echo "\n3. Calcul de la courbe vers l'enfant :\n";
$child = $childPositions[0];

// Logique actuelle du code
$startY = ($rakeY !== null) ? $rakeY : $parentBottomY + 20;
$curveStartX = ($rakeX !== null) ? $rakeX : $parentCenterX;

echo "- startY = {$startY} (doit être égal à rakeY = {$rakeY})\n";
echo "- curveStartX = {$curveStartX} (doit être égal à rakeX = {$rakeX})\n";

echo "\n4. Vérification :\n";
echo "- startY === rakeY ? " . ($startY === $rakeY ? "OUI ✓" : "NON ✗") . "\n";
echo "- curveStartX === rakeX ? " . ($curveStartX === $rakeX ? "OUI ✓" : "NON ✗") . "\n";

if ($startY !== $rakeY) {
    echo "\n❌ PROBLÈME IDENTIFIÉ : startY ({$startY}) ≠ rakeY ({$rakeY})\n";
    echo "La courbe commence au mauvais niveau Y !\n";
} else {
    echo "\n✅ startY est correctement égal à rakeY\n";
}

echo "\n5. Coordonnées finales de la courbe :\n";
echo "- Point de départ : ({$curveStartX}, {$startY})\n";
echo "- Point d'arrivée : ({$child['centerX']}, " . ($child['y'] + 30) . ")\n";
