<?php

namespace App\Repository;

use App\Entity\Forum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ForumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Forum::class);
    }

    // Méthode pour trouver tous les résumés de réunion pour un client donné
    public function findByClient($clientId)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.client = :clientId')
            ->setParameter('clientId', $clientId)
            ->orderBy('f.date', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
