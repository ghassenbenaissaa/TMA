<?php

namespace App\Repository;

use App\Entity\Aventure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Aventure>
 *
 * @method Aventure|null find($id, $lockMode = null, $lockVersion = null)
 * @method Aventure|null findOneBy(array $criteria, array $orderBy = null)
 * @method Aventure[]    findAll()
 * @method Aventure[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AventureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Aventure::class);
    }

//    /**
//     * @return Aventure[] Returns an array of Aventure objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Aventure
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
