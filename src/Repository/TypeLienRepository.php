<?php

namespace App\Repository;

use App\Entity\TypeLien;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TypeLien>
 *
 * @method TypeLien|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypeLien|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypeLien[]    findAll()
 * @method TypeLien[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypeLienRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeLien::class);
    }

    /**
     * Trouve tous les types de liens parentaux
     */
    public function findParentaux(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.estParental = :parental')
            ->setParameter('parental', true)
            ->orderBy('t.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les types de liens biologiques
     */
    public function findBiologiques(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.estBiologique = :biologique')
            ->setParameter('biologique', true)
            ->orderBy('t.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un type de lien par son nom
     */
    public function findOneByNom(string $nom): ?TypeLien
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.nom = :nom')
            ->setParameter('nom', $nom)
            ->getQuery()
            ->getOneOrNullResult();
    }
}