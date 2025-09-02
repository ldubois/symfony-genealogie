<?php

namespace App\Service;

use App\Entity\Person;

/**
 * Service pour gérer les générations et les niveaux des personnes
 */
class GenerationService
{
    private CoupleService $coupleService;
    
    public function __construct(CoupleService $coupleService)
    {
        $this->coupleService = $coupleService;
    }
    
    /**
     * Calculer le niveau d'une personne en tenant compte des couples
     */
    public function calculatePersonLevelWithCouples(Person $person, array $visited, array $couples): int
    {
        if (in_array($person->getId(), $visited)) {
            return 0; // Éviter les cycles
        }
        
        $visited[] = $person->getId();
        
        // Vérifier si cette personne a des parents
        $parents = $this->getAllParents($person);
        
        if (empty($parents)) {
            return 0; // Personne sans parents = génération 0
        }
        
        // Calculer le niveau maximum des parents + 1
        $maxParentLevel = 0;
        foreach ($parents as $parent) {
            $parentLevel = $this->calculatePersonLevelWithCouples($parent, $visited, $couples);
            $maxParentLevel = max($maxParentLevel, $parentLevel);
        }
        
        return $maxParentLevel + 1;
    }
    
    /**
     * Calculer le niveau d'une personne (méthode de base)
     */
    public function calculatePersonLevel(Person $person, array $visited): int
    {
        if (in_array($person->getId(), $visited)) {
            return 0;
        }
        
        $visited[] = $person->getId();
        
        $parents = $this->getAllParents($person);
        
        if (empty($parents)) {
            return 0;
        }
        
        $maxParentLevel = 0;
        foreach ($parents as $parent) {
            $parentLevel = $this->calculatePersonLevel($parent, $visited);
            $maxParentLevel = max($maxParentLevel, $parentLevel);
        }
        
        return $maxParentLevel + 1;
    }
    
    /**
     * Ajuster les niveaux des conjoints (même niveau que leur partenaire)
     */
    public function adjustSpouseLevels(array $people, array &$personLevels): void
    {
        foreach ($people as $person) {
            foreach ($person->getTousLesLiens() as $lien) {
                $typeLien = $lien->getTypeLien();
                
                if (in_array($typeLien->getNom(), ['Conjoint', 'Ex-conjoint', 'Compagnon', 'Séparé'])) {
                    $spouse = $lien->getAutrePersonne($person);
                    
                    if (isset($personLevels[$person->getId()]) && isset($personLevels[$spouse->getId()])) {
                        // Les conjoints doivent avoir le même niveau
                        $maxLevel = max($personLevels[$person->getId()], $personLevels[$spouse->getId()]);
                        $personLevels[$person->getId()] = $maxLevel;
                        $personLevels[$spouse->getId()] = $maxLevel;
                    }
                }
            }
        }
    }
    
    /**
     * Organiser les personnes par générations
     */
    public function organizeByGenerations(array $people): array
    {
        $personLevels = [];
        $generations = [];
        
        // Étape 1 : Identifier les couples
        $couples = $this->coupleService->identifyCouples($people);
        
        // Étape 2 : Calculer le niveau de chaque personne
        foreach ($people as $person) {
            $level = $this->calculatePersonLevelWithCouples($person, [], $couples);
            $personLevels[$person->getId()] = $level;
        }
        
        // Étape 3 : Ajuster les niveaux des conjoints
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
        
        return $generations;
    }
    
    /**
     * Obtenir le nom d'une génération
     */
    public function getGenerationName(int $level): string
    {
        return match($level) {
            0 => 'Génération 0 - Ancêtres',
            1 => 'Génération 1 - Parents',
            2 => 'Génération 2 - Enfants',
            3 => 'Génération 3 - Petits-enfants',
            4 => 'Génération 4 - Arrière-petits-enfants',
            default => "Génération $level"
        };
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
