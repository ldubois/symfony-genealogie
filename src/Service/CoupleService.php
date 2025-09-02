<?php

namespace App\Service;

use App\Entity\Person;
use App\Entity\Lien;

/**
 * Service pour identifier et gérer les couples dans l'arbre généalogique
 */
class CoupleService
{
    /**
     * Identifier tous les couples dans une liste de personnes
     */
    public function identifyCouples(array $people): array
    {
        $couples = [];
        $processedPairs = [];
        
        foreach ($people as $person) {
            foreach ($person->getTousLesLiens() as $lien) {
                $typeLien = $lien->getTypeLien();
                
                // Vérifier si c'est un lien conjugal
                if (in_array($typeLien->getNom(), ['Conjoint', 'Ex-conjoint', 'Compagnon', 'Séparé'])) {
                    $autrePersonne = $lien->getAutrePersonne($person);
                    
                    // Éviter les doublons
                    $pairKey = min($person->getId(), $autrePersonne->getId()) . '-' . max($person->getId(), $autrePersonne->getId());
                    if (in_array($pairKey, $processedPairs)) {
                        continue;
                    }
                    
                    // Ajouter le couple avec priorité
                    $couples[] = [
                        'person1' => $person,
                        'person2' => $autrePersonne,
                        'type' => $typeLien->getNom(),
                        'priority' => $this->getCouplePriority($typeLien->getNom())
                    ];
                    
                    $processedPairs[] = $pairKey;
                }
            }
        }
        
        // Trier par priorité (Conjoint > Ex-conjoint > Compagnon > Séparé)
        usort($couples, function($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });
        
        return $couples;
    }
    
    /**
     * Obtenir la priorité d'un type de couple
     */
    private function getCouplePriority(string $type): int
    {
        return match($type) {
            'Conjoint' => 4,
            'Ex-conjoint' => 3,
            'Compagnon' => 2,
            'Séparé' => 1,
            default => 0
        };
    }
    
    /**
     * Trouver tous les partenaires d'une personne dans une liste
     */
    public function findAllPartnersInList(Person $person, array $peopleList): array
    {
        $partners = [];
        
        foreach ($person->getTousLesLiens() as $lien) {
            $typeLien = $lien->getTypeLien();
            
            if (in_array($typeLien->getNom(), ['Conjoint', 'Ex-conjoint', 'Compagnon', 'Séparé'])) {
                $partner = $lien->getAutrePersonne($person);
                
                // Vérifier que le partenaire est dans la liste
                if (in_array($partner, $peopleList)) {
                    $partners[] = $partner;
                }
            }
        }
        
        return $partners;
    }
    
    /**
     * Trouver le partenaire principal d'une personne dans une liste
     */
    public function findPartnerInList(Person $person, array $peopleList): ?Person
    {
        $partners = $this->findAllPartnersInList($person, $peopleList);
        
        if (empty($partners)) {
            return null;
        }
        
        // Retourner le partenaire avec la priorité la plus élevée
        $highestPriority = 0;
        $bestPartner = null;
        
        foreach ($partners as $partner) {
            foreach ($person->getTousLesLiens() as $lien) {
                if ($lien->getAutrePersonne($person) === $partner) {
                    $priority = $this->getCouplePriority($lien->getTypeLien()->getNom());
                    if ($priority > $highestPriority) {
                        $highestPriority = $priority;
                        $bestPartner = $partner;
                    }
                    break;
                }
            }
        }
        
        return $bestPartner;
    }
    
    /**
     * Trouver le partenaire d'un parent dans le même niveau
     */
    public function findParentPartner(Person $person, array $sameLevelPeople): ?Person
    {
        return $this->findPartnerInList($person, $sameLevelPeople);
    }
    
    /**
     * Vérifier si deux personnes forment un couple
     */
    public function areCouple(Person $person1, Person $person2): bool
    {
        foreach ($person1->getTousLesLiens() as $lien) {
            $autrePersonne = $lien->getAutrePersonne($person1);
            
            if ($autrePersonne === $person2) {
                $typeLien = $lien->getTypeLien();
                return in_array($typeLien->getNom(), ['Conjoint', 'Ex-conjoint', 'Compagnon', 'Séparé']);
            }
        }
        
        return false;
    }
    
    /**
     * Obtenir le type de relation entre deux personnes
     */
    public function getRelationshipType(Person $person1, Person $person2): ?string
    {
        foreach ($person1->getTousLesLiens() as $lien) {
            $autrePersonne = $lien->getAutrePersonne($person1);
            
            if ($autrePersonne === $person2) {
                return $lien->getTypeLien()->getNom();
            }
        }
        
        return null;
    }
}
