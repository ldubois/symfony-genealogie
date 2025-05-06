<?php

namespace App\Repository;

use App\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PersonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Person::class);
    }

    public function findAllOrderedByName()
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.lastName', 'ASC')
            ->addOrderBy('p.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAncestors(Person $person, int $generations = 3)
    {
        $ancestors = [];
        $currentPerson = $person;
        
        for ($i = 0; $i < $generations; $i++) {
            if ($currentPerson->getFather()) {
                $ancestors[] = $currentPerson->getFather();
            }
            if ($currentPerson->getMother()) {
                $ancestors[] = $currentPerson->getMother();
            }
            if ($currentPerson->getFather()) {
                $currentPerson = $currentPerson->getFather();
            } elseif ($currentPerson->getMother()) {
                $currentPerson = $currentPerson->getMother();
            } else {
                break;
            }
        }
        
        return $ancestors;
    }

    public function findDescendants(Person $person, int $generations = 3)
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.father = :person OR p.mother = :person')
            ->setParameter('person', $person);
            
        return $qb->getQuery()->getResult();
    }
} 