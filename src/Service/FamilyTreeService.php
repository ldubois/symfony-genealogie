<?php

namespace App\Service;

use App\Entity\Person;

class FamilyTreeService
{
    public function organizeByGenerations(array $people): array
    {
        $personLevels = [];
        $generations = [];
        
        // Étape 1 : Identifier les couples et les traiter comme des unités
        $couples = $this->identifyCouples($people);
        
        // Étape 2 : Calculer le niveau de chaque personne en tenant compte des couples
        foreach ($people as $person) {
            $level = $this->calculatePersonLevelWithCouples($person, [], $couples);
            $personLevels[$person->getId()] = $level;
        }
        
        // Étape 3 : Ajuster les niveaux des conjoints (même niveau que leur partenaire)
        $this->adjustSpouseLevels($people, $personLevels);
        
        // Étape 4 : Organiser par générations
        foreach ($people as $person) {
            $level = $personLevels[$person->getId()];
            
            if (!isset($generations[$level])) {
                $generations[$level] = [];
            }
            $generations[$level][] = $person;
        }
        
        // Trier les générations par niveau
        ksort($generations);
        
        // Étape 5 : Trier les personnes dans chaque génération en regroupant les couples
        foreach ($generations as $level => &$peopleInGen) {
            // Appliquer le tri des couples AVANT le tri alphabétique
            $this->sortGenerationWithCouples($peopleInGen, $couples);
        }
        
        // Étape 6 : Réorganiser les enfants selon l'ordre de leurs parents
        if (isset($generations[2])) { // Génération des enfants
            $this->sortChildrenByParentOrder($generations[2], $generations[1], $couples);
        }
        
        return $generations;
    }
    
    /**
     * Trie les enfants selon l'ordre de leurs parents dans la génération précédente
     */
    private function sortChildrenByParentOrder(array &$children, array $parents, array $couples): void
    {
        // Créer un mapping des couples pour identifier les parents ensemble
        $coupleMap = [];
        foreach ($couples as $couple) {
            $coupleMap[$couple['person1']->getId()] = $couple['person2'];
            $coupleMap[$couple['person2']->getId()] = $couple['person1'];
        }
        
        // Créer un mapping des enfants par parent
        $childrenByParent = [];
        foreach ($children as $child) {
            $childParents = $this->getAllParents($child);
            foreach ($childParents as $parent) {
                if (!isset($childrenByParent[$parent->getId()])) {
                    $childrenByParent[$parent->getId()] = [];
                }
                $childrenByParent[$parent->getId()][] = $child;
            }
        }
        
        // Réorganiser les enfants selon l'ordre des parents
        $reorderedChildren = [];
        $processedChildren = [];
        
        // Parcourir les parents dans l'ordre exact de la génération
        foreach ($parents as $parent) {
            $parentId = $parent->getId();
            
            // Ajouter les enfants de ce parent
            if (isset($childrenByParent[$parentId])) {
                foreach ($childrenByParent[$parentId] as $child) {
                    if (!in_array($child->getId(), $processedChildren)) {
                        $reorderedChildren[] = $child;
                        $processedChildren[] = $child->getId();
                    }
                }
            }
            
            // Si ce parent a un conjoint, ajouter aussi ses enfants
            if (isset($coupleMap[$parentId])) {
                $spouseId = $coupleMap[$parentId]->getId();
                if (isset($childrenByParent[$spouseId])) {
                    foreach ($childrenByParent[$spouseId] as $child) {
                        if (!in_array($child->getId(), $processedChildren)) {
                            $reorderedChildren[] = $child;
                            $processedChildren[] = $child->getId();
                        }
                    }
                }
            }
        }
        
        // Ajouter les enfants orphelins (sans parents identifiés)
        foreach ($children as $child) {
            if (!in_array($child->getId(), $processedChildren)) {
                $reorderedChildren[] = $child;
            }
        }
        
        // Remplacer l'ordre original des enfants
        $children = $reorderedChildren;
        
        // Debug de l'ordre final des enfants
        $finalNames = array_map(fn($p) => $p->getFirstName(), $children);
        error_log("=== DEBUG TRI ENFANTS ===");
        error_log("Ordre final des enfants: " . implode(', ', $finalNames));
        error_log("=== FIN DEBUG TRI ENFANTS ===");
    }

    private function sortGenerationWithCouples(array &$peopleInGen, array $couples): void
    {
        // Debug pour voir ce qui se passe
        error_log("=== DEBUG SORT COUPLES ===");
        error_log("Génération avec " . count($peopleInGen) . " personnes");
        error_log("Couples identifiés: " . count($couples));
        
        // Créer un mapping des couples pour un accès rapide
        $coupleMap = [];
        foreach ($couples as $couple) {
            $coupleMap[$couple['person1']->getId()] = $couple['person2'];
            $coupleMap[$couple['person2']->getId()] = $couple['person1'];
            error_log("Couple: " . $couple['person1']->getFirstName() . " <-> " . $couple['person2']->getFirstName());
        }
        
        // Identifier les parents d'enfants (ceux qui ont des enfants)
        $parentsWithChildren = $this->identifyParentsWithChildren($peopleInGen);
        error_log("Parents avec enfants: " . implode(', ', array_map(fn($p) => $p->getFirstName(), $parentsWithChildren)));
        
        // NOUVELLE LOGIQUE COMPLÈTE : Respecter l'ordre des parents ET des couples
        $reordered = [];
        $processed = [];
        
        // Étape 1 : Identifier tous les couples ET les personnes liées (conjoints, ex-conjoints, etc.)
        $allLinkedPeople = [];
        foreach ($couples as $couple) {
            $person1 = $couple['person1'];
            $person2 = $couple['person2'];
            
            // Vérifier que les deux personnes sont dans cette génération
            if (in_array($person1, $peopleInGen) && in_array($person2, $peopleInGen)) {
                $allLinkedPeople[] = [
                    'people' => [$person1, $person2],
                    'parentOrder' => $this->getParentOrder($person1),
                    'type' => $couple['type']
                ];
            }
        }
        
        // Trier les groupes de personnes liées selon l'ordre de leurs parents
        usort($allLinkedPeople, function($a, $b) {
            return $a['parentOrder'] <=> $b['parentOrder'];
        });
        
        // Ajouter les groupes de personnes liées dans l'ordre
        foreach ($allLinkedPeople as $group) {
            foreach ($group['people'] as $person) {
                if (!in_array($person->getId(), $processed)) {
                    $reordered[] = $person;
                    $processed[] = $person->getId();
                    error_log("Ajouté personne liée: " . $person->getFirstName() . " (type: " . $group['type'] . ")");
                }
            }
        }
        
        // Étape 2 : Ajouter les parents d'enfants restants selon l'ordre de leurs parents
        $remainingParents = array_filter($peopleInGen, function($person) use ($processed) {
            return !in_array($person->getId(), $processed) && $this->hasChildren($person);
        });
        
        // Trier les parents restants selon l'ordre de leurs parents
        usort($remainingParents, function($a, $b) {
            return $this->getParentOrder($a) <=> $this->getParentOrder($b);
        });
        
        foreach ($remainingParents as $person) {
            if (!in_array($person->getId(), $processed)) {
                $reordered[] = $person;
                $processed[] = $person->getId();
                error_log("Ajouté parent d'enfant trié: " . $person->getFirstName());
            }
        }
        
        // Étape 3 : Ajouter le reste (personnes sans enfants) selon l'ordre de leurs parents
        $remainingOthers = array_filter($peopleInGen, function($person) use ($processed) {
            return !in_array($person->getId(), $processed);
        });
        
        usort($remainingOthers, function($a, $b) {
            return $this->getParentOrder($a) <=> $this->getParentOrder($b);
        });
        
        foreach ($remainingOthers as $person) {
            if (!in_array($person->getId(), $processed)) {
                $reordered[] = $person;
                $processed[] = $person->getId();
                error_log("Ajouté autre trié: " . $person->getFirstName());
            }
        }
        
        error_log("Ordre final: " . implode(', ', array_map(fn($p) => $p->getFirstName(), $reordered)));
        error_log("=== FIN DEBUG SORT COUPLES ===");
        
        $peopleInGen = $reordered;
    }
    
    private function calculatePersonLevelWithCouples(Person $person, array $visited, array $couples): int
    {
        // Éviter les boucles infinies
        if (in_array($person->getId(), $visited)) {
            return 0;
        }
        
        $visited[] = $person->getId();
        
        $maxParentLevel = -1;
        
        // Calculer le niveau basé sur les parents (anciens champs ET nouveaux liens)
        $parents = $this->getAllParents($person);
        foreach ($parents as $parent) {
            $parentLevel = $this->calculatePersonLevelWithCouples($parent, $visited, $couples);
            $maxParentLevel = max($maxParentLevel, $parentLevel);
        }
        
        // Si pas de parents, niveau 0, sinon niveau parent + 1
        return $maxParentLevel + 1;
    }

    private function calculatePersonLevel(Person $person, array $visited): int
    {
        // Éviter les boucles infinies
        if (in_array($person->getId(), $visited)) {
            return 0;
        }
        
        $visited[] = $person->getId();
        
        $maxParentLevel = -1;
        
        // Calculer le niveau basé sur les parents (anciens champs ET nouveaux liens)
        $parents = $this->getAllParents($person);
        foreach ($parents as $parent) {
            $parentLevel = $this->calculatePersonLevel($parent, $visited);
            $maxParentLevel = max($maxParentLevel, $parentLevel);
        }
        
        // Si pas de parents, niveau 0, sinon niveau parent + 1
        return $maxParentLevel + 1;
    }
    
    private function adjustSpouseLevels(array $people, array &$personLevels): void
    {
        // Plusieurs passes pour s'assurer que tous les conjoints sont au même niveau
        for ($pass = 0; $pass < 3; $pass++) {
            foreach ($people as $person) {
                $personLevel = $personLevels[$person->getId()];
                
                // Ajuster le niveau des conjoints
                foreach ($person->getTousLesLiens() as $lien) {
                    if (in_array($lien->getTypeLien()->getNom(), ['Conjoint', 'Ex-conjoint', 'Compagnon', 'Séparé'])) {
                        $spouse = $lien->getAutrePersonne($person);
                        
                        // Vérifier que le conjoint a un niveau calculé
                        if (!isset($personLevels[$spouse->getId()])) {
                            continue; // Passer au lien suivant si le conjoint n'a pas de niveau
                        }
                        
                        $spouseLevel = $personLevels[$spouse->getId()];
                        
                        // Prendre le niveau le plus élevé (plus proche des ancêtres)
                        $targetLevel = max($personLevel, $spouseLevel);
                        $personLevels[$person->getId()] = $targetLevel;
                        $personLevels[$spouse->getId()] = $targetLevel;
                    }
                }
            }
        }
    }
    

    
    public function getConnectionData(array $generations): array
    {
        // Calculer les positions de tous les éléments
        $positionedPeople = $this->calculatePositions($generations);
        
        // Générer les chemins SVG pour toutes les connexions
        $svgPaths = $this->generateSVGConnections($positionedPeople, $generations);
        
        // IMPORTANT : Réorganiser positionedPeople pour respecter l'ordre des générations
        $orderedPositionedPeople = $this->reorderPositionedPeople($positionedPeople, $generations);
        
        // Préparer les informations sur les générations pour l'affichage
        $generationInfo = $this->prepareGenerationInfo($generations, $positionedPeople);
        
        // DEBUG : Afficher l'ordre final des personnes
        error_log("=== DEBUG SERVICE - ORDRE FINAL ===");
        foreach ($orderedPositionedPeople as $personId => $personData) {
            error_log("ID $personId: {$personData['person']['name']} (niveau {$personData['level']})");
        }
        error_log("=== FIN DEBUG SERVICE ===");
        
        return [
            'positionedPeople' => $orderedPositionedPeople,
            'svgPaths' => $svgPaths,
            'generationInfo' => $generationInfo
        ];
    }
    


    /**
     * Préparer les informations sur les générations pour l'affichage
     */
    private function prepareGenerationInfo(array $generations, array $positionedPeople): array
    {
        $generationInfo = [];
        
        // Trier les générations par niveau (des plus anciens aux plus récents)
        $sortedLevels = array_keys($generations);
        sort($sortedLevels);
        
        foreach ($sortedLevels as $level) {
            if ($level === 'isolated') continue;
            
            $people = $generations[$level];
            $count = count($people);
            
            // Déterminer le nom de la génération
            $generationName = $this->getGenerationName($level);
            
            // Calculer la position Y de la génération (basée sur la première personne)
            $firstPerson = reset($people);
            $generationY = 0; // Sera calculé plus tard
            
            // Trouver la position Y de cette génération dans les données positionnées
            if ($firstPerson && isset($positionedPeople[$firstPerson->getId()])) {
                $generationY = $positionedPeople[$firstPerson->getId()]['y'];
            }
            
            $generationInfo[$level] = [
                'level' => $level,
                'name' => $generationName,
                'count' => $count,
                'people' => $people,
                'y' => $generationY
            ];
        }
        
        return $generationInfo;
    }
    
    /**
     * Obtenir le nom de la génération
     */
    private function getGenerationName(int $level): string
    {
        switch ($level) {
            case 0:
                return 'Ancêtres (Génération 0)';
            case 1:
                return 'Parents (Génération 1)';
            case 2:
                return 'Enfants (Génération 2)';
            case 3:
                return 'Petits-enfants (Génération 3)';
            case 4:
                return 'Arrière-petits-enfants (Génération 4)';
            default:
                return "Génération $level";
        }
    }

    /**
     * Récupère tous les parents d'une personne (anciens champs + nouveaux liens)
     */
    private function identifyCouples(array $people): array
    {
        $couples = [];
        $processed = [];
        
        // Créer d'abord une liste de tous les liens conjugaux
        $allConjugalLinks = [];
        foreach ($people as $person) {
            foreach ($person->getTousLesLiens() as $lien) {
                $typeLien = $lien->getTypeLien();
                if (in_array($typeLien->getNom(), ['Conjoint', 'Ex-conjoint', 'Compagnon', 'Séparé'])) {
                    $spouse = $lien->getAutrePersonne($person);
                    
                    // Éviter les liens avec soi-même
                    if ($person->getId() !== $spouse->getId()) {
                        // Créer une clé unique pour ce couple
                        $coupleKey = min($person->getId(), $spouse->getId()) . '-' . max($person->getId(), $spouse->getId());
                        
                        if (!isset($allConjugalLinks[$coupleKey])) {
                            $allConjugalLinks[$coupleKey] = [
                                'person1' => $person,
                                'person2' => $spouse,
                                'type' => $typeLien->getNom()
                            ];
                        }
                    }
                }
            }
        }
        
        // Debug
        error_log("=== DEBUG IDENTIFY COUPLES ===");
        error_log("Liens conjugaux trouvés : " . count($allConjugalLinks));
        foreach ($allConjugalLinks as $coupleKey => $couple) {
            error_log("Couple {$coupleKey}: {$couple['person1']->getFirstName()} <-> {$couple['person2']->getFirstName()} ({$couple['type']})");
        }
        
        // Convertir en tableau et éviter les doublons
        // IMPORTANT : Trier les couples par priorité (Conjoint > Ex-conjoint > Compagnon > Séparé)
        $couplePriorities = [
            'Conjoint' => 1,
            'Ex-conjoint' => 2,
            'Compagnon' => 3,
            'Séparé' => 4
        ];
        
        // Trier les couples par priorité
        uasort($allConjugalLinks, function($a, $b) use ($couplePriorities) {
            $priorityA = $couplePriorities[$a['type']] ?? 999;
            $priorityB = $couplePriorities[$b['type']] ?? 999;
            return $priorityA <=> $priorityB;
        });
        
        foreach ($allConjugalLinks as $coupleKey => $couple) {
            if (!in_array($couple['person1']->getId(), $processed) && 
                !in_array($couple['person2']->getId(), $processed)) {
                $couples[] = $couple;
                $processed[] = $couple['person1']->getId();
                $processed[] = $couple['person2']->getId();
                error_log("Couple ajouté: {$couple['person1']->getFirstName()} <-> {$couple['person2']->getFirstName()} ({$couple['type']})");
            } else {
                error_log("Couple ignoré (déjà traité): {$couple['person1']->getFirstName()} <-> {$couple['person2']->getFirstName()} ({$couple['type']})");
            }
        }
        
        error_log("Couples finaux : " . count($couples));
        error_log("=== FIN DEBUG IDENTIFY COUPLES ===");
        
        return $couples;
    }

    private function identifyParentsWithChildren(array $people): array
    {
        $parentsWithChildren = [];
        
        foreach ($people as $person) {
            // Vérifier si cette personne a des enfants
            foreach ($person->getTousLesLiens() as $lien) {
                $typeLien = $lien->getTypeLien();
                if ($typeLien->isEstParental() && $lien->isActifADate()) {
                    // Si la personne est personne1 dans le lien, alors c'est un parent
                    if ($lien->getPersonne1() === $person) {
                        $parentsWithChildren[] = $person;
                        break; // Une fois qu'on sait que c'est un parent, pas besoin de continuer
                    }
                }
            }
        }
        
        return $parentsWithChildren;
    }

    private function getAllParents(Person $person): array
    {
        $parents = [];
        
        // Utiliser uniquement le nouveau système de liens
        foreach ($person->getTousLesLiens() as $lien) {
            $typeLien = $lien->getTypeLien();
            if ($typeLien->isEstParental() && $lien->isActifADate()) {
                $potentialParent = $lien->getAutrePersonne($person);
                
                // Vérifier que c'est bien un lien parent->enfant et non enfant->parent
                // Si la personne actuelle est personne2 dans le lien, alors l'autre personne est le parent
                // ET s'assurer que ce n'est pas la même personne (éviter les boucles)
                if ($lien->getPersonne2() === $person && 
                    $potentialParent !== $person && 
                    !in_array($potentialParent, $parents)) {
                    $parents[] = $potentialParent;
                }
            }
        }
        
        // Debug temporaire
        if (count($parents) > 0) {
            error_log("DEBUG: " . $person->getFirstName() . " a " . count($parents) . " parents: " . 
                     implode(', ', array_map(fn($p) => $p->getFirstName(), $parents)));
        }
        
        return $parents;
    }

         /**
      * Calculer les positions exactes de toutes les personnes
      */
     private function calculatePositions(array $generations): array
     {
         $positions = [];
         $nodeWidth = 200;  // Largeur d'une carte personne
         $nodeHeight = 120; // Hauteur d'une carte personne
         $levelSpacing = 150; // Espacement vertical entre générations
         $personSpacing = 40; // Espacement horizontal entre personnes
         
         $currentY = 50; // Position Y de départ
         $parentOrderNumbers = []; // Numéros d'ordre par génération
         
                 // Trier les générations (des plus anciens aux plus récents)
        $sortedLevels = array_keys($generations);
        sort($sortedLevels); // Trier en ordre croissant pour mettre les anciens (0) en premier
         
         foreach ($sortedLevels as $level) {
             if ($level === 'isolated') continue; // Skip isolated pour l'instant
             
             $people = $generations[$level];
             
             // IMPORTANT : Utiliser directement l'ordre des personnes dans la génération
             // plutôt que de passer par arrangeByParentOrderNumbers qui peut perturber l'ordre
             $arrangedPeople = $people;
             
             // Stocker la position Y de cette génération pour les étiquettes
             $generationY = $currentY;
             
             $totalWidth = count($arrangedPeople) * ($nodeWidth + $personSpacing) - $personSpacing;
             $startX = -$totalWidth / 2; // Centrer horizontalement
             
             $currentX = $startX;
             $orderNumber = 0;
             
             foreach ($arrangedPeople as $person) {
                 $positions[$person->getId()] = [
                     'x' => $currentX,
                     'y' => $currentY,
                     'width' => $nodeWidth,
                     'height' => $nodeHeight,
                     'centerX' => $currentX + $nodeWidth / 2,
                     'centerY' => $currentY + $nodeHeight / 2,
                     'level' => $level,
                     'person' => [
                         'id' => $person->getId(),
                         'name' => $person->getFullName(),
                         'gender' => $person->getGender() ? $person->getGender()->value : null,
                         'birthDate' => $person->getBirthDate()?->format('d/m/Y'),
                         'deathDate' => $person->getDeathDate()?->format('d/m/Y'),
                         'photo' => $person->getPhoto()
                     ]
                 ];
                 
                 // Attribuer un numéro d'ordre pour cette personne (position horizontale)
                 $parentOrderNumbers[$person->getId()] = $orderNumber;
                 
                 $currentX += $nodeWidth + $personSpacing;
                 $orderNumber++;
             }
             
             $currentY += $nodeHeight + $levelSpacing;
         }
        
        // Traiter les personnes isolées
        if (isset($generations['isolated']) && !empty($generations['isolated'])) {
            $isolatedPeople = $generations['isolated'];
            $totalWidth = count($isolatedPeople) * ($nodeWidth + $personSpacing) - $personSpacing;
            $startX = -$totalWidth / 2;
            $currentX = $startX;
            
            foreach ($isolatedPeople as $person) {
                $positions[$person->getId()] = [
                    'x' => $currentX,
                    'y' => $currentY,
                    'width' => $nodeWidth,
                    'height' => $nodeHeight,
                    'centerX' => $currentX + $nodeWidth / 2,
                    'centerY' => $currentY + $nodeHeight / 2,
                    'level' => 'isolated',
                    'person' => [
                        'id' => $person->getId(),
                        'name' => $person->getFullName(),
                        'gender' => $person->getGender() ? $person->getGender()->value : null,
                        'birthDate' => $person->getBirthDate()?->format('d/m/Y'),
                        'deathDate' => $person->getDeathDate()?->format('d/m/Y'),
                        'photo' => $person->getPhoto()
                    ]
                ];
                
                $currentX += $nodeWidth + $personSpacing;
            }
        }
        
        return $positions;
    }
    
    /**
     * Générer tous les chemins SVG pour les connexions
     */
    private function generateSVGConnections(array $positions, array $generations): array
    {
        $svgPaths = [];
        
        // 1. Connexions parent-enfant par famille
        $familyGroups = $this->createFamilyGroupsWithPositions($positions, $generations);
        foreach ($familyGroups as $familyKey => $family) {
            $svgPaths = array_merge($svgPaths, $this->generateFamilyConnections($family));
        }
        
        // 2. Connexions conjugales
        $conjugalConnections = $this->getConjugalConnectionsWithPositions($positions, $generations);
        $svgPaths = array_merge($svgPaths, $conjugalConnections);
        
        return $svgPaths;
    }
    
    /**
     * Créer les groupes de familles avec les positions calculées
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
                'x1' => $leftParent, // Position X du parent le plus à gauche
                'y1' => $rakeY, // Hauteur du râteau
                'x2' => $rightParent, // Position X du parent le plus à droite
                'y2' => $rakeY, // Même hauteur (ligne horizontale)
                'class' => 'parent-child-connection',
                'stroke' => $familyColor,
                'strokeWidth' => 3
            ];
            
                                                  // Lignes verticales des parents vers le râteau
             foreach ($parentPositions as $parent) {
                 $paths[] = [
                     'type' => 'line',
                     'x1' => $parent['centerX'], // Centre exact de la carte parent
                     'y1' => $parent['y'] + $parent['height'] + 15, // 15px après le bas de la carte
                     'x2' => $parent['centerX'], // Même centre X pour ligne parfaitement verticale
                     'y2' => $rakeY, // Jusqu'au râteau horizontal
                     'class' => 'parent-child-connection',
                     'stroke' => $familyColor,
                     'strokeWidth' => 2
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
                     $curveStartX, $startY, // Position exacte du centre du râteau ou du parent
                     ($curveStartX + $child['centerX']) / 2, ($startY + ($child['y'] + 30)) / 2 + 20,
                     $child['centerX'], $child['y'] + 30 // Descendre 30px dans la carte enfant
                 ),
                 'class' => 'parent-child-connection',
                 'stroke' => $familyColor,
                 'strokeWidth' => 3
             ];
        } else {
            // Plusieurs enfants : râteau
                         $leftChild = min(array_map(fn($c) => $c['centerX'], $childPositions));
             $rightChild = max(array_map(fn($c) => $c['centerX'], $childPositions));
             $childCenterX = ($leftChild + $rightChild) / 2;
             $minChildY = min(array_map(fn($c) => $c['y'], $childPositions));
             $childRakeY = $minChildY - 30; // Râteau 30px au-dessus du haut des cartes enfants
             

            
            // Ligne centrale courbée
            // Si on a un râteau de parents, partir 50px sous le râteau, sinon 20px plus bas
            $startY = ($rakeY !== null) ? $rakeY + 50 : $parentBottomY + 20;
            
            // Pour la courbe, utiliser la position exacte du râteau des parents si il y en a un
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
                'strokeWidth' => 3
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
                'strokeWidth' => 3
            ];
            
                         // Lignes vers chaque enfant
             foreach ($childPositions as $child) {
                 $paths[] = [
                     'type' => 'line',
                     'x1' => $child['centerX'],
                     'y1' => $childRakeY,
                     'x2' => $child['centerX'],
                     'y2' => $child['y'] + 30, // Descendre 30px dans la carte enfant
                     'class' => 'parent-child-connection',
                     'stroke' => $familyColor,
                     'strokeWidth' => 2
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
                                'strokeDasharray' => '5,5'
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
      * Arranger les personnes selon les numéros d'ordre en tenant compte des couples dès le départ
      */
         private function arrangeByParentOrderNumbers(array $people, array $parentOrderNumbers): array
    {
        // Calculer l'ordre pour chaque personne en tenant compte de la logique familiale
        $peopleWithOrder = [];
        $couplesProcessed = [];
        
        foreach ($people as $person) {
            if (in_array($person->getId(), $couplesProcessed)) {
                continue; // Déjà traité comme partie d'un couple
            }
            
            // Chercher TOUS les partenaires de cette personne (conjoint, ex-conjoint, compagnon, etc.)
            $partners = $this->findAllPartnersInList($person, $people);
            
            if (!empty($partners)) {
                // Créer un groupe avec tous les membres (personne + partenaires)
                $groupMembers = [$person];
                foreach ($partners as $partner) {
                    $groupMembers[] = $partner;
                    $couplesProcessed[] = $partner->getId();
                }
                $couplesProcessed[] = $person->getId();
                
                                 // Calculer l'ordre basé sur la logique familiale
                 $familyOrder = $this->calculateFamilyOrder($person, $partners, $parentOrderNumbers, $people);
                
                // Ajouter tous les membres du groupe avec le même ordre principal
                $subOrderCounter = 0;
                foreach ($groupMembers as $member) {
                    $peopleWithOrder[] = [
                        'person' => $member,
                        'order' => $familyOrder,
                        'subOrder' => $subOrderCounter
                    ];
                    $subOrderCounter++;
                }
            } else {
                                 // Personne seule
                 $familyOrder = $this->calculateFamilyOrder($person, [], $parentOrderNumbers, $people);
                $peopleWithOrder[] = [
                    'person' => $person,
                    'order' => $familyOrder,
                    'subOrder' => 0
                ];
            }
        }
        
        // Trier par ordre principal, puis sous-ordre
        usort($peopleWithOrder, function($a, $b) {
            if ($a['order'] == $b['order']) {
                return $a['subOrder'] <=> $b['subOrder'];
            }
            return $a['order'] <=> $b['order'];
        });
        
        // Extraire les personnes triées
        return array_map(fn($item) => $item['person'], $peopleWithOrder);
    }
    
               /**
       * Calculer l'ordre familial basé sur la logique métier spécifique
       */
      private function calculateFamilyOrder(Person $person, array $partners, array $parentOrderNumbers, array $peopleInGeneration): float
     {
         // Récupérer les parents de cette personne
         $parents = $this->getAllParents($person);
         
         if (!empty($parents)) {
             // Si la personne a des parents, utiliser leur ordre
             $totalOrder = 0;
             $validParents = 0;
             
             foreach ($parents as $parent) {
                 if (isset($parentOrderNumbers[$parent->getId()])) {
                     $totalOrder += $parentOrderNumbers[$parent->getId()];
                     $validParents++;
                 }
             }
             
             if ($validParents > 0) {
                 $baseOrder = $totalOrder / $validParents;
                 
                                   // LOGIQUE FAMILIALE BASÉE SUR LES RELATIONS
                  // Identifier la famille de cette personne par ses parents
                  $familyPriority = $this->calculateFamilyPriority($person, $parentOrderNumbers);
                  if ($familyPriority !== null) {
                      return $familyPriority;
                  }
                 
                 // DEBUG : Afficher l'ordre calculé
                 error_log("DEBUG calculateFamilyOrder: {$person->getFirstName()} a l'ordre {$baseOrder}");
                 
                                                                        // LOGIQUE POUR LA GÉNÉRATION 2 (ENFANTS) - BASÉE SUR LES RELATIONS
                   // Utiliser la priorité familiale calculée par les parents
                   // IMPORTANT : Passer le tableau des personnes de la génération pour utiliser la position, pas l'ID
                   $childFamilyPriority = $this->calculateChildFamilyPriority($person, $parentOrderNumbers, $peopleInGeneration);
                   if ($childFamilyPriority !== null) {
                       return $childFamilyPriority;
                   }
                 
                 // Ajuster l'ordre selon la logique familiale
                 // Les couples doivent être groupés ensemble
                 if (!empty($partners)) {
                     // Ajuster légèrement pour que les partenaires soient proches
                     $baseOrder += 0.1;
                 }
                 
                 return $baseOrder;
             }
         }
         
         // Pas de parents ou première génération : utiliser l'ID
         return (float) $person->getId();
     }
     
     
     

     
     /**
      * Trouver TOUS les partenaires d'une personne dans une liste
      */
         private function findAllPartnersInList(Person $person, array $peopleList): array
    {
        $partners = [];
        
        // DEBUG : Afficher les liens de cette personne
        error_log("DEBUG findAllPartnersInList: {$person->getFirstName()} a " . count($person->getTousLesLiens()) . " liens");
        
        // Chercher tous les conjoints/compagnons/ex
        foreach ($person->getTousLesLiens() as $lien) {
            $typeNom = $lien->getTypeLien()->getNom();
            error_log("DEBUG: Lien de type '{$typeNom}' trouvé pour {$person->getFirstName()}");
            
            if (in_array($typeNom, ['Conjoint', 'Ex-conjoint', 'Compagnon', 'Séparé'])) {
                $potentialPartner = $lien->getAutrePersonne($person);
                error_log("DEBUG: Partenaire potentiel trouvé: {$potentialPartner->getFirstName()}");
                
                // Vérifier si ce partenaire est dans la liste
                foreach ($peopleList as $personInList) {
                    if ($personInList->getId() === $potentialPartner->getId()) {
                        $partners[] = $potentialPartner;
                        error_log("DEBUG: Partenaire ajouté: {$potentialPartner->getFirstName()}");
                        break;
                    }
                }
            }
        }
        
        // Chercher aussi les co-parents (personnes avec qui cette personne a eu des enfants)
        $coParent = $this->findParentPartner($person, $peopleList);
        if ($coParent && !in_array($coParent, $partners, true)) {
            $partners[] = $coParent;
            error_log("DEBUG: Co-parent ajouté: {$coParent->getFirstName()}");
        }
        
        error_log("DEBUG: {$person->getFirstName()} a " . count($partners) . " partenaires: " . implode(', ', array_map(fn($p) => $p->getFirstName(), $partners)));
        return $partners;
    }
     
     /**
      * Trouver le partenaire (conjoint/compagnon) d'une personne dans une liste - version simple
      */
     private function findPartnerInList(Person $person, array $peopleList): ?Person
     {
         $allPartners = $this->findAllPartnersInList($person, $peopleList);
         return !empty($allPartners) ? $allPartners[0] : null;
     }
    
    /**
     * Trouver un partenaire avec qui la personne a eu des enfants
     */
    private function findParentPartner(Person $person, array $sameLevelPeople): ?Person
    {
        // Récupérer tous les enfants de cette personne
        $children = $this->getAllChildren($person);
        
        foreach ($children as $child) {
            // Pour chaque enfant, trouver ses autres parents
            $childParents = $this->getAllParents($child);
            
            foreach ($childParents as $otherParent) {
                if ($otherParent->getId() !== $person->getId()) {
                    // Vérifier si cet autre parent est dans la même génération
                    foreach ($sameLevelPeople as $sameLevelPerson) {
                        if ($sameLevelPerson->getId() === $otherParent->getId()) {
                            return $otherParent;
                        }
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * Récupère tous les enfants d'une personne (via le système de liens)
     */
    private function getAllChildren(Person $person): array
    {
        $children = [];
        
        // Enfants via les nouveaux liens
        foreach ($person->getTousLesLiens() as $lien) {
            $typeLien = $lien->getTypeLien();
            if ($typeLien->isEstParental() && $lien->isActifADate()) {
                $potentialChild = $lien->getAutrePersonne($person);
                
                // Vérifier que c'est bien un lien parent->enfant et non enfant->parent
                if ($lien->getPersonne1() === $person && !in_array($potentialChild, $children)) {
                    $children[] = $potentialChild;
                }
            }
        }
        
        return $children;
    }
    
         /**
      * Créer des groupes familiaux ordonnés
      */
     private function createFamilyGroups(array $people, array $existingPositions = []): array
     {
         // Grouper les enfants par leurs parents
         $familyGroups = [];
         $singles = [];
         
         foreach ($people as $person) {
             $parents = $this->getAllParents($person);
             
             if (!empty($parents)) {
                 // Créer une clé unique pour cette combinaison de parents
                 $parentIds = array_map(fn($parent) => $parent->getId(), $parents);
                 sort($parentIds);
                 $familyKey = implode('-', $parentIds);
                 
                 if (!isset($familyGroups[$familyKey])) {
                     $familyGroups[$familyKey] = [
                         'parents' => $parents,
                         'children' => [],
                         'avgParentX' => 0
                     ];
                 }
                 
                 $familyGroups[$familyKey]['children'][] = $person;
             } else {
                 $singles[] = $person;
             }
         }
         
         // Calculer la position moyenne pour chaque groupe familial
         foreach ($familyGroups as $familyKey => &$group) {
             if (!empty($existingPositions)) {
                 $totalX = 0;
                 $validParents = 0;
                 
                 foreach ($group['parents'] as $parent) {
                     if (isset($existingPositions[$parent->getId()])) {
                         $totalX += $existingPositions[$parent->getId()]['centerX'];
                         $validParents++;
                     }
                 }
                 
                 $group['avgParentX'] = $validParents > 0 ? $totalX / $validParents : 0;
             } else {
                 // Fallback avec ID minimum
                 $parentIds = array_map(fn($parent) => $parent->getId(), $group['parents']);
                 $group['avgParentX'] = min($parentIds) * 100;
             }
             
             // Trier les enfants dans chaque groupe par ID pour cohérence
             usort($group['children'], fn($a, $b) => $a->getId() <=> $b->getId());
         }
         
         // Trier les groupes par position X moyenne des parents
         uasort($familyGroups, fn($a, $b) => $a['avgParentX'] <=> $b['avgParentX']);
         
         // Trier les singles par ID
         usort($singles, fn($a, $b) => $a->getId() <=> $b->getId());
         
         // Créer le résultat final avec la structure de groupe
         $result = [];
         foreach ($familyGroups as $groupKey => $group) {
             $result[] = [
                 'key' => $groupKey,
                 'members' => $group['children'],
                 'avgParentX' => $group['avgParentX']
             ];
         }
         
         // Ajouter un groupe pour les singles
         if (!empty($singles)) {
             $result[] = [
                 'key' => 'singles',
                 'members' => $singles,
                 'avgParentX' => 999999 // À la fin
             ];
         }
         
         return $result;
     }
     
     /**
      * Récupère les données complètes des parents d'une personne pour le template
      */
     public function getParentData(Person $person): array
    {
        $parentData = ['fathers' => [], 'mothers' => []];
        
        // Récupérer tous les parents
        $allParents = $this->getAllParents($person);
        
        foreach ($allParents as $parent) {
            if ($parent->getGender() && $parent->getGender()->value === 'homme') {
                $parentData['fathers'][] = $parent;
            } else {
                $parentData['mothers'][] = $parent;
            }
        }
        
        return $parentData;
    }
    
    /**
     * Réorganiser positionedPeople pour respecter l'ordre des générations
     */
    private function reorderPositionedPeople(array $positionedPeople, array $generations): array
    {
        $orderedPeople = [];
        
        // IMPORTANT : Respecter l'ordre des personnes dans chaque génération
        // tel qu'il a été défini par organizeByGenerations() avec notre logique de tri
        
        // Parcourir les générations dans l'ordre
        foreach ($generations as $level => $people) {
            // Pour chaque personne dans cette génération, ajouter ses données de position
            // L'ordre des personnes dans $people respecte déjà notre logique de tri
            foreach ($people as $person) {
                $personId = $person->getId();
                if (isset($positionedPeople[$personId])) {
                    $orderedPeople[$personId] = $positionedPeople[$personId];
                }
            }
        }
        
        // Debug pour vérifier l'ordre final
        error_log("=== DEBUG REORDER - ORDRE FINAL ===");
        foreach ($orderedPeople as $personId => $personData) {
            error_log("ID $personId: {$personData['person']['name']} (niveau {$personData['level']})");
        }
        error_log("=== FIN DEBUG REORDER ===");
        
        return $orderedPeople;
    }
     
     /**
      * Calculer la priorité familiale basée sur les relations parent-enfant
      * plutôt que sur les prénoms en dur
      */
     private function calculateFamilyPriority(Person $person, array $parentOrderNumbers): ?float
     {
         $parents = $this->getAllParents($person);
         
         if (empty($parents)) {
             return null;
         }
         
         // Identifier la famille par ses parents
         $parentIds = array_map(fn($parent) => $parent->getId(), $parents);
         sort($parentIds); // Ordre stable pour la clé de famille
         
         // Priorité basée sur l'ordre des parents dans leur génération
         $totalParentOrder = 0;
         $validParents = 0;
         
         foreach ($parents as $parent) {
             if (isset($parentOrderNumbers[$parent->getId()])) {
                 $totalParentOrder += $parentOrderNumbers[$parent->getId()];
                 $validParents++;
             }
         }
         
         if ($validParents > 0) {
             // Plus l'ordre des parents est petit, plus la priorité est élevée
             $basePriority = $totalParentOrder / $validParents;
             
             // Ajuster selon la logique familiale
             // Les enfants de la première famille (parents avec ordre 0) ont la priorité la plus élevée
             return $basePriority;
         }
         
         return null;
     }
     
           /**
       * Calculer la priorité des enfants basée sur leurs parents
       */
      private function calculateChildFamilyPriority(Person $person, array $parentOrderNumbers, array $peopleInGeneration): ?float
      {
          // LOGIQUE GÉNÉRIQUE : Priorité basée uniquement sur l'ordre des parents
          $parents = $this->getAllParents($person);
          
          if (empty($parents)) {
              return null;
          }
          
          // Identifier la famille par ses parents
          $parentIds = array_map(fn($parent) => $parent->getId(), $parents);
          sort($parentIds);
          
          // Calculer la priorité de base basée sur l'ordre des parents
          $totalParentOrder = 0;
          $validParents = 0;
          
          foreach ($parents as $parent) {
              if (isset($parentOrderNumbers[$parent->getId()])) {
                  $totalParentOrder += $parentOrderNumbers[$parent->getId()];
                  $validParents++;
              }
          }
          
          if ($validParents > 0) {
              // Plus l'ordre des parents est petit, plus la priorité est élevée
              $basePriority = $totalParentOrder / $validParents;
              
              // IMPORTANT : Logique familiale basée sur les parents, pas la position !
              // Les enfants d'Isabelle/Pierre ont la priorité 0.0
              // Les enfants de Pierre/Christine ont la priorité 1.0
              // Les autres enfants ont des priorités croissantes
              
              // Identifier la famille par les parents
              $familyKey = implode('-', array_map(fn($p) => $p->getId(), $parents));
              
                             // LOGIQUE GÉNÉRIQUE : Priorité basée uniquement sur l'ordre des parents
               // Plus l'ordre des parents est petit, plus la priorité est élevée
               // Cela garantit que les enfants des premiers parents (ordre 0) ont la priorité la plus élevée
               error_log("DEBUG calculateChildFamilyPriority: {$person->getFirstName()} a les parents IDs: " . implode(', ', $parentIds));
               
               // Priorité basée uniquement sur l'ordre des parents dans leur génération
               // Pas de référence aux prénoms ou IDs spécifiques
               $finalPriority = $basePriority;
               error_log("DEBUG: {$person->getFirstName()} a la priorité {$finalPriority} basée sur l'ordre de ses parents");
               return $finalPriority;
              
              return $basePriority;
          }
          
          return null;
      }
     
     /**
      * Vérifier si une personne appartient à une famille spécifique
      */
     private function isPersonInFamily(Person $person, array $familyParentOrders, array $parentOrderNumbers): bool
     {
         $parents = $this->getAllParents($person);
         
         if (empty($parents)) {
             return false;
         }
         
         // Vérifier si tous les parents de la famille sont parents de cette personne
         $personParentOrders = [];
         foreach ($parents as $parent) {
             if (isset($parentOrderNumbers[$parent->getId()])) {
                 $personParentOrders[] = $parentOrderNumbers[$parent->getId()];
             }
         }
         
         // Trier pour la comparaison
         sort($personParentOrders);
         sort($familyParentOrders);
         
         return $personParentOrders == $familyParentOrders;
     }
     
     /**
      * Obtenir l'ordre d'un parent pour le tri
      */
     private function getParentOrder(Person $person): int
     {
         $parents = $this->getAllParents($person);
         
         if (empty($parents)) {
             return 999999; // Personnes sans parents à la fin
         }
         
         // Prendre le parent avec l'ID le plus petit (plus ancien dans la génération)
         return min(array_map(fn($p) => $p->getId(), $parents));
     }
     
     /**
      * Vérifier si une personne a des enfants
      */
     private function hasChildren(Person $person): bool
     {
         foreach ($person->getTousLesLiens() as $lien) {
             $typeLien = $lien->getTypeLien();
             if ($typeLien->isEstParental() && $lien->isActifADate()) {
                 if ($lien->getPersonne1() === $person) {
                     return true;
                 }
             }
         }
         return false;
     }
 }