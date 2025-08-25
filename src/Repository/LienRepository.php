<?php

namespace App\Repository;

use App\Entity\Lien;
use App\Entity\Person;
use App\Entity\TypeLien;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Lien>
 *
 * @method Lien|null find($id, $lockMode = null, $lockVersion = null)
 * @method Lien|null findOneBy(array $criteria, array $orderBy = null)
 * @method Lien[]    findAll()
 * @method Lien[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LienRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lien::class);
    }

    /**
     * Trouve tous les liens d'une personne
     */
    public function findByPersonne(Person $personne): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.typeLien', 't')
            ->addSelect('t')
            ->leftJoin('l.personne1', 'p1')
            ->addSelect('p1')
            ->leftJoin('l.personne2', 'p2')
            ->addSelect('p2')
            ->andWhere('l.personne1 = :personne OR l.personne2 = :personne')
            ->setParameter('personne', $personne)
            ->orderBy('t.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les liens d'un type spécifique pour une personne
     */
    public function findByPersonneAndType(Person $personne, TypeLien $typeLien): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.personne1', 'p1')
            ->addSelect('p1')
            ->leftJoin('l.personne2', 'p2')
            ->addSelect('p2')
            ->andWhere('(l.personne1 = :personne OR l.personne2 = :personne)')
            ->andWhere('l.typeLien = :typeLien')
            ->setParameter('personne', $personne)
            ->setParameter('typeLien', $typeLien)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les liens actifs à une date donnée
     */
    public function findActifsADate(?\DateTimeInterface $date = null): array
    {
        if ($date === null) {
            $date = new \DateTime();
        }

        return $this->createQueryBuilder('l')
            ->leftJoin('l.typeLien', 't')
            ->addSelect('t')
            ->leftJoin('l.personne1', 'p1')
            ->addSelect('p1')
            ->leftJoin('l.personne2', 'p2')
            ->addSelect('p2')
            ->andWhere('(l.dateDebut IS NULL OR l.dateDebut <= :date)')
            ->andWhere('(l.dateFin IS NULL OR l.dateFin >= :date)')
            ->setParameter('date', $date)
            ->orderBy('t.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les parents d'une personne
     */
    public function findParents(Person $personne): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.typeLien', 't')
            ->addSelect('t')
            ->leftJoin('l.personne1', 'p1')
            ->addSelect('p1')
            ->andWhere('l.personne2 = :personne')
            ->andWhere('t.estParental = :parental')
            ->setParameter('personne', $personne)
            ->setParameter('parental', true)
            ->orderBy('t.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les enfants d'une personne
     */
    public function findEnfants(Person $personne): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.typeLien', 't')
            ->addSelect('t')
            ->leftJoin('l.personne2', 'p2')
            ->addSelect('p2')
            ->andWhere('l.personne1 = :personne')
            ->andWhere('t.estParental = :parental')
            ->setParameter('personne', $personne)
            ->setParameter('parental', true)
            ->orderBy('t.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}