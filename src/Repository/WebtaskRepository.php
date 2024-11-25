<?php
// src/Repository/WebTaskRepository.php

namespace App\Repository;

use App\Entity\Webtask;
use App\Entity\User;
use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WebTask|null find($id, $lockMode = null, $lockVersion = null)
 * @method WebTask|null findOneBy(array $criteria, array $orderBy = null)
 * @method WebTask[]    findAll()
 * @method WebTask[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WebtaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebTask::class);
    }

    /**
     * @return WebTask[] Returns an array of WebTask objects
     */
    public function findByClient($idclient)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.idclient = :idclient')
            ->setParameter('idclient', $idclient)
            ->getQuery()
            ->getResult();
    }

    public function findTasksByUser(User $user)
    {
        return $this->createQueryBuilder('w')
            ->where('w.responsable = :user OR w.Piloteid = :user OR w.iddemandeur = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    // Mise à jour de cette méthode
    public function findIdByCodeWebtask(string $codeWebtask): ?int
    {
        $webtask = $this->createQueryBuilder('w')
            ->select('w.id') // Sélectionne uniquement l'ID
            ->where('w.code = :code') // Utiliser le nom correct du champ
            ->setParameter('code', $codeWebtask)
            ->getQuery()
            ->getOneOrNullResult();

        return $webtask ? $webtask['id'] : null; // Retourne l'ID ou null
    }
}