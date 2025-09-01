<?php

namespace App\Service;

use App\Entity\Person;

class FamilyTreeService
{
    public function organizeByGenerations(array $people): array
    {
        $personLevels = [];
        $generations = [];
        
        // Étape 1 : Calculer le niveau de chaque personne (distance depuis les ancêtres)
        foreach ($people as $person) {
            $level = $this->calculatePersonLevel($person, []);
            $personLevels[$person->getId()] = $level;
        }
        
        // Étape 2 : Ajuster les niveaux des conjoints (même niveau que leur partenaire)
        $this->adjustSpouseLevels($people, $personLevels);
        
        // Étape 3 : Trouver le niveau maximum (ancêtres les plus anciens)
        $maxLevel = max($personLevels);
        
        // Étape 4 : Inverser les niveaux (les plus anciens deviennent niveau 0)
        foreach ($personLevels as $personId => $level) {
            $personLevels[$personId] = $maxLevel - $level;
        }
        
        // Étape 5 : Organiser par générations
        foreach ($people as $person) {
            $level = $personLevels[$person->getId()];
            
            if (!isset($generations[$level])) {
                $generations[$level] = [];
            }
            $generations[$level][] = $person;
        }
        
        // Trier les générations par niveau
        ksort($generations);
        
        return $generations;
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
        
        return [
            'positionedPeople' => $positionedPeople,
            'svgPaths' => $svgPaths
        ];
    }
    


    /**
     * Récupère tous les parents d'une personne (anciens champs + nouveaux liens)
     */
    private function getAllParents(Person $person): array
    {
        $parents = [];
        
        // Ajouter les parents via les anciens champs (pour compatibilité)
        if ($person->getFather()) {
            $parents[] = $person->getFather();
        }
        if ($person->getMother()) {
            $parents[] = $person->getMother();
        }
        
        // Ajouter les parents via les nouveaux liens
        foreach ($person->getTousLesLiens() as $lien) {
            $typeLien = $lien->getTypeLien();
            if ($typeLien->isEstParental() && $lien->isActifADate()) {
                $potentialParent = $lien->getAutrePersonne($person);
                
                // Vérifier que c'est bien un lien parent->enfant et non enfant->parent
                // Si la personne actuelle est personne2 dans le lien, alors l'autre personne est le parent
                if ($lien->getPersonne2() === $person && !in_array($potentialParent, $parents)) {
                    $parents[] = $potentialParent;
                }
            }
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
         rsort($sortedLevels); // Trier en ordre décroissant pour mettre les anciens en premier
         
         foreach ($sortedLevels as $level) {
             if ($level === 'isolated') continue; // Skip isolated pour l'instant
             
             $people = $generations[$level];
             
             // Trier les enfants selon les numéros d'ordre de leurs parents
             $arrangedPeople = $this->arrangeByParentOrderNumbers($people, $parentOrderNumbers);
             
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
         $parentBottomY = max(array_map(fn($p) => $p['y'] + $p['height'], $parentPositions)) + 15; // Ajouter 15px d'espacement
         
         // Couleur unique pour cette famille
         $familyColor = '#' . substr(md5(json_encode(array_keys($family['parents']))), 0, 6);
         
         // Calculer la position du râteau des parents si il y en a un
         $rakeY = null;
         
         // Ligne horizontale entre parents (si plusieurs)
         if (count($parentPositions) > 1) {
             $leftParent = min(array_map(fn($p) => $p['centerX'], $parentPositions));
             $rightParent = max(array_map(fn($p) => $p['centerX'], $parentPositions));
             $rakeY = $parentBottomY + 25; // Augmenter l'espacement du râteau
            
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
             
             // Si on a un râteau de parents, partir exactement du râteau, sinon 20px plus bas  
             $startY = ($rakeY !== null) ? $rakeY : $parentBottomY + 20;
             
             // Pour la courbe vers enfant unique, utiliser aussi la position exacte du râteau si il existe
             $curveStartX = (count($parentPositions) > 1) ? 
                 (min(array_map(fn($p) => $p['centerX'], $parentPositions)) + max(array_map(fn($p) => $p['centerX'], $parentPositions))) / 2 : 
                 $parentCenterX;
             
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
            // Si on a un râteau de parents, partir exactement du râteau, sinon 20px plus bas
            $startY = ($rakeY !== null) ? $rakeY : $parentBottomY + 20;
            
            // Pour la courbe, utiliser la position du centre du râteau des parents si il y en a un
            $curveStartX = (count($parentPositions) > 1) ? 
                (min(array_map(fn($p) => $p['centerX'], $parentPositions)) + max(array_map(fn($p) => $p['centerX'], $parentPositions))) / 2 : 
                $parentCenterX;
            
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
         // Calculer le numéro d'ordre pour chaque personne en tenant compte des couples
         $peopleWithOrder = [];
         $couplesProcessed = [];
         
         foreach ($people as $person) {
             if (in_array($person->getId(), $couplesProcessed)) {
                 continue; // Déjà traité comme partie d'un couple
             }
             
             // Calculer l'ordre basé sur les parents
             $baseOrder = $this->calculateBaseOrder($person, $parentOrderNumbers);
             
             // Chercher TOUS les partenaires de cette personne (conjoint, ex-conjoint, compagnon, etc.)
             $partners = $this->findAllPartnersInList($person, $people);
             
             if (!empty($partners)) {
                 // Calculer l'ordre moyen de tout le groupe (personne + tous ses partenaires)
                 $allOrders = [$baseOrder];
                 foreach ($partners as $partner) {
                     $allOrders[] = $this->calculateBaseOrder($partner, $parentOrderNumbers);
                 }
                 
                 $groupOrder = array_sum($allOrders) / count($allOrders);
                 
                 // Créer un groupe avec tous les membres (personne + partenaires)
                 $groupMembers = [$person];
                 foreach ($partners as $partner) {
                     $groupMembers[] = $partner;
                     $couplesProcessed[] = $partner->getId();
                 }
                 $couplesProcessed[] = $person->getId();
                 
                 // Trier les membres du groupe par ID pour un ordre cohérent
                 usort($groupMembers, function($a, $b) {
                     return $a->getId() <=> $b->getId();
                 });
                 
                 // Ajouter tous les membres du groupe avec le même ordre principal
                 $subOrderCounter = 0;
                 foreach ($groupMembers as $member) {
                     $peopleWithOrder[] = [
                         'person' => $member,
                         'order' => $groupOrder,
                         'subOrder' => $subOrderCounter
                     ];
                     $subOrderCounter++;
                 }
             } else {
                 // Personne seule
                 $peopleWithOrder[] = [
                     'person' => $person,
                     'order' => $baseOrder,
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
      * Calculer l'ordre de base d'une personne selon ses parents
      */
     private function calculateBaseOrder(Person $person, array $parentOrderNumbers): float
     {
         $parents = $this->getAllParents($person);
         
         if (!empty($parents) && !empty($parentOrderNumbers)) {
             // Calculer la moyenne des numéros d'ordre des parents
             $totalOrder = 0;
             $validParents = 0;
             
             foreach ($parents as $parent) {
                 if (isset($parentOrderNumbers[$parent->getId()])) {
                     $totalOrder += $parentOrderNumbers[$parent->getId()];
                     $validParents++;
                 }
             }
             
             return $validParents > 0 ? $totalOrder / $validParents : $person->getId();
         } else {
             // Pas de parents ou première génération : utiliser l'ID
             return (float) $person->getId();
         }
     }
     

     
     /**
      * Trouver TOUS les partenaires d'une personne dans une liste
      */
     private function findAllPartnersInList(Person $person, array $peopleList): array
     {
         $partners = [];
         
         // Chercher tous les conjoints/compagnons/ex
         foreach ($person->getTousLesLiens() as $lien) {
             if (in_array($lien->getTypeLien()->getNom(), ['Conjoint', 'Ex-conjoint', 'Compagnon', 'Séparé'])) {
                 $potentialPartner = $lien->getAutrePersonne($person);
                 
                 // Vérifier si ce partenaire est dans la liste
                 foreach ($peopleList as $personInList) {
                     if ($personInList->getId() === $potentialPartner->getId()) {
                         $partners[] = $potentialPartner;
                         break;
                     }
                 }
             }
         }
         
         // Chercher aussi les co-parents (personnes avec qui cette personne a eu des enfants)
         $coParent = $this->findParentPartner($person, $peopleList);
         if ($coParent && !in_array($coParent, $partners, true)) {
             $partners[] = $coParent;
         }
         
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
     * Récupère tous les enfants d'une personne (anciens champs + nouveaux liens)
     */
    private function getAllChildren(Person $person): array
    {
        $children = [];
        
        // Enfants via les anciens champs
        foreach ($person->getChildren() as $child) {
            $children[] = $child;
        }
        
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
}