<?php 

// src/Repository/NotificationRepository.php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\Webtask;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findVisibleNotifications(): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.visible = :visible')
            ->setParameter('visible', true) // true est équivalent à 1
            ->orderBy('n.dateCreation', 'DESC') // Par exemple, trier par date de création
            ->getQuery()
            ->getResult();
    }
    
}