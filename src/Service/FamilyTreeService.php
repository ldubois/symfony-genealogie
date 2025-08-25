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
        
        // Calculer le niveau basé sur les parents
        if ($person->getFather()) {
            $fatherLevel = $this->calculatePersonLevel($person->getFather(), $visited);
            $maxParentLevel = max($maxParentLevel, $fatherLevel);
        }
        
        if ($person->getMother()) {
            $motherLevel = $this->calculatePersonLevel($person->getMother(), $visited);
            $maxParentLevel = max($maxParentLevel, $motherLevel);
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
        $connections = [];
        
        foreach ($generations as $level => $people) {
            if (is_numeric($level) && $level > 0) {
                foreach ($people as $child) {
                    // Connexions parent-enfant
                    if ($child->getFather()) {
                        $connections[] = [
                            'type' => 'parent-child',
                            'from' => $child->getFather()->getId(),
                            'to' => $child->getId()
                        ];
                    }
                    
                    if ($child->getMother()) {
                        $connections[] = [
                            'type' => 'parent-child',
                            'from' => $child->getMother()->getId(),
                            'to' => $child->getId()
                        ];
                    }
                }
            }
        }
        
        // Connexions conjugales
        foreach ($generations as $level => $people) {
            foreach ($people as $person) {
                foreach ($person->getTousLesLiens() as $lien) {
                    if (in_array($lien->getTypeLien()->getNom(), ['Conjoint', 'Ex-conjoint', 'Compagnon', 'Séparé'])) {
                        $spouse = $lien->getAutrePersonne($person);
                        $connections[] = [
                            'type' => 'conjugal',
                            'from' => $person->getId(),
                            'to' => $spouse->getId(),
                            'linkType' => $lien->getTypeLien()->getNom()
                        ];
                    }
                }
            }
        }
        
        return $connections;
    }
}