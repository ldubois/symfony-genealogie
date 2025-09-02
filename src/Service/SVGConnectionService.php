<?php

namespace App\Service;

use App\Entity\Person;

/**
 * Service pour générer les connexions SVG de l'arbre généalogique
 */
class SVGConnectionService
{
    /**
     * Générer toutes les connexions SVG
     */
    public function generateSVGConnections(array $positions, array $generations): array
    {
        $svgPaths = [];
        
        // Créer des groupes familiaux avec positions
        $familyGroups = $this->createFamilyGroupsWithPositions($positions, $generations);
        
        // Générer les connexions familiales
        foreach ($familyGroups as $family) {
            $familyConnections = $this->generateFamilyConnections($family);
            $svgPaths = array_merge($svgPaths, $familyConnections);
        }
        
        // Générer les connexions conjugales
        $conjugalConnections = $this->getConjugalConnectionsWithPositions($positions, $generations);
        $svgPaths = array_merge($svgPaths, $conjugalConnections);
        
        return $svgPaths;
    }
    
    /**
     * Créer des groupes familiaux avec positions
     */
    private function createFamilyGroupsWithPositions(array $positions, array $generations): array
    {
        $familyGroups = [];
        
        foreach ($generations as $level => $people) {
            foreach ($people as $child) {
                $parents = $this->getAllParents($child);
                
                if (!empty($parents) && isset($positions[$child->getId()])) {
                    $parentIds = array_map(fn($parent) => $parent->getId(), $parents);
                    sort($parentIds);
                    $familyKey = 'family-' . implode('-', $parentIds) . '-gen' . $level;
                    
                    if (!isset($familyGroups[$familyKey])) {
                        $familyGroups[$familyKey] = [
                            'parents' => [],
                            'children' => []
                        ];
                    }
                    
                    // Ajouter les parents avec leurs positions
                    foreach ($parents as $parent) {
                        if (isset($positions[$parent->getId()])) {
                            $familyGroups[$familyKey]['parents'][$parent->getId()] = $positions[$parent->getId()];
                        }
                    }
                    
                    // Ajouter l'enfant avec sa position
                    $familyGroups[$familyKey]['children'][$child->getId()] = $positions[$child->getId()];
                }
            }
        }
        
        return $familyGroups;
    }
    
    /**
     * Générer les connexions SVG pour une famille
     */
    private function generateFamilyConnections(array $family): array
    {
        $paths = [];
        
        if (empty($family['parents']) || empty($family['children'])) {
            return $paths;
        }
        
        $parentPositions = array_values($family['parents']);
        $childPositions = array_values($family['children']);
        
        // Calculer le centre des parents
        $parentCenterX = array_sum(array_map(fn($p) => $p['centerX'], $parentPositions)) / count($parentPositions);
        // Utiliser le bas des parents + espacement pour le râteau
        $parentBottomY = max(array_map(fn($p) => $p['y'] + $p['height'], $parentPositions)) + 15;
        
        // Couleur unique pour cette famille
        $familyColor = '#' . substr(md5(json_encode(array_keys($family['parents']))), 0, 6);
        
        // Calculer la position du râteau des parents si il y en a un
        $rakeY = null;
        $rakeX = null; // Position X du râteau
        
        // Ligne horizontale entre parents (si plusieurs)
        if (count($parentPositions) > 1) {
            $leftParent = min(array_map(fn($p) => $p['centerX'], $parentPositions));
            $rightParent = max(array_map(fn($p) => $p['centerX'], $parentPositions));
            $rakeY = $parentBottomY + 25; // Râteau 25px sous le bas des parents
            $rakeX = ($leftParent + $rightParent) / 2; // Centre exact du râteau
            
            $paths[] = [
                'type' => 'line',
                'x1' => $leftParent,
                'y1' => $rakeY,
                'x2' => $rightParent,
                'y2' => $rakeY,
                'class' => 'parent-child-connection',
                'stroke' => $familyColor,
                'strokeWidth' => 3,
                'linkType' => 'parent-child'
            ];
            
            // Lignes verticales des parents vers le râteau
            foreach ($parentPositions as $parent) {
                $paths[] = [
                    'type' => 'line',
                    'x1' => $parent['centerX'],
                    'y1' => $parent['y'] + $parent['height'] + 15,
                    'x2' => $parent['centerX'],
                    'y2' => $rakeY,
                    'class' => 'parent-child-connection',
                    'stroke' => $familyColor,
                    'strokeWidth' => 2,
                    'linkType' => 'parent-child'
                ];
            }
            $parentBottomY = $rakeY;
        }
        
        // Connexions vers les enfants
        if (count($childPositions) === 1) {
            // Un seul enfant
            $child = $childPositions[0];
            
            // Si on a un râteau de parents, partir 50px sous le râteau, sinon 20px plus bas  
            $startY = ($rakeY !== null) ? $rakeY + 50 : $parentBottomY + 20;
            
            // Pour la courbe vers enfant unique, utiliser la position exacte du râteau si il existe
            $curveStartX = ($rakeX !== null) ? $rakeX : $parentCenterX;
            
            $paths[] = [
                'type' => 'path',
                'd' => sprintf('M %d %d Q %d %d %d %d', 
                    $curveStartX, $startY,
                    ($curveStartX + $child['centerX']) / 2, ($startY + ($child['y'] + 30)) / 2 + 20,
                    $child['centerX'], $child['y'] + 30
                ),
                'class' => 'parent-child-connection',
                'stroke' => $familyColor,
                'strokeWidth' => 3,
                'linkType' => 'parent-child'
            ];
        } else {
            // Plusieurs enfants : râteau
            $leftChild = min(array_map(fn($c) => $c['centerX'], $childPositions));
            $rightChild = max(array_map(fn($c) => $c['centerX'], $childPositions));
            $childCenterX = ($leftChild + $rightChild) / 2;
            $minChildY = min(array_map(fn($c) => $c['y'], $childPositions));
            $childRakeY = $minChildY - 30; // Râteau 30px au-dessus du haut des cartes enfants
            
            // Ligne centrale courbée
            $startY = ($rakeY !== null) ? $rakeY + 50 : $parentBottomY + 20;
            $curveStartX = ($rakeX !== null) ? $rakeX : $parentCenterX;
            
            $paths[] = [
                'type' => 'path',
                'd' => sprintf('M %d %d Q %d %d %d %d', 
                    $curveStartX, $startY,
                    ($curveStartX + $childCenterX) / 2, ($startY + $childRakeY) / 2,
                    $childCenterX, $childRakeY
                ),
                'class' => 'parent-child-connection',
                'stroke' => $familyColor,
                'strokeWidth' => 3,
                'linkType' => 'parent-child'
            ];
            
            // Râteau horizontal
            $paths[] = [
                'type' => 'line',
                'x1' => $leftChild,
                'y1' => $childRakeY,
                'x2' => $rightChild,
                'y2' => $childRakeY,
                'class' => 'parent-child-connection',
                'stroke' => $familyColor,
                'strokeWidth' => 3,
                'linkType' => 'parent-child'
            ];
            
            // Lignes vers chaque enfant
            foreach ($childPositions as $child) {
                $paths[] = [
                    'type' => 'line',
                    'x1' => $child['centerX'],
                    'y1' => $childRakeY,
                    'x2' => $child['centerX'],
                    'y2' => $child['y'] + 30,
                    'class' => 'parent-child-connection',
                    'stroke' => $familyColor,
                    'strokeWidth' => 2,
                    'linkType' => 'parent-child'
                ];
            }
        }
        
        return $paths;
    }
    
    /**
     * Obtenir les connexions conjugales avec positions
     */
    private function getConjugalConnectionsWithPositions(array $positions, array $generations): array
    {
        $connections = [];
        $processedPairs = [];
        
        foreach ($generations as $level => $people) {
            foreach ($people as $person) {
                if (!isset($positions[$person->getId()])) continue;
                
                foreach ($person->getTousLesLiens() as $lien) {
                    if (in_array($lien->getTypeLien()->getNom(), ['Conjoint', 'Ex-conjoint', 'Compagnon', 'Séparé'])) {
                        $spouse = $lien->getAutrePersonne($person);
                        
                        if (!isset($positions[$spouse->getId()])) continue;
                        
                        // Éviter les doublons
                        $pairKey = min($person->getId(), $spouse->getId()) . '-' . max($person->getId(), $spouse->getId());
                        if (in_array($pairKey, $processedPairs)) continue;
                        
                        $personPos = $positions[$person->getId()];
                        $spousePos = $positions[$spouse->getId()];
                        
                        // Vérifier qu'ils sont de même niveau
                        if ($personPos['level'] === $spousePos['level']) {
                            $midX = ($personPos['centerX'] + $spousePos['centerX']) / 2;
                            $midY = min($personPos['centerY'], $spousePos['centerY']) - 30;
                            
                            $connections[] = [
                                'type' => 'path',
                                'd' => sprintf('M %d %d Q %d %d %d %d', 
                                    $personPos['centerX'], $personPos['centerY'],
                                    $midX, $midY,
                                    $spousePos['centerX'], $spousePos['centerY']
                                ),
                                'class' => 'conjugal-connection',
                                'stroke' => '#e91e63',
                                'strokeWidth' => 2,
                                'strokeDasharray' => '5,5',
                                'linkType' => 'conjugal'
                            ];
                            
                            $processedPairs[] = $pairKey;
                        }
                    }
                }
            }
        }
        
        return $connections;
    }
    
    /**
     * Obtenir tous les parents d'une personne
     */
    private function getAllParents(Person $person): array
    {
        $parents = [];
        
        foreach ($person->getTousLesLiens() as $lien) {
            $typeLien = $lien->getTypeLien();
            
            if ($typeLien->isEstParental() && $lien->isActifADate()) {
                if ($lien->getPersonne1() === $person) {
                    // Cette personne est le parent
                    continue;
                } else {
                    // L'autre personne est le parent
                    $parents[] = $lien->getPersonne1();
                }
            }
        }
        
        return $parents;
    }
}
