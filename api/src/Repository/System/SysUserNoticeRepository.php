<?php

namespace App\Repository\System;

use App\Entity\System\SysUserNotice;
use App\Repository\BaseRepository;

use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<SysUserNotice>
 * @method SysUserNotice|null find($id, $lockMode = null, $lockVersion = null)
 * @method SysUserNotice|null findOneBy(array $criteria, array $orderBy = null)
 * @method SysUserNotice[]    findAll()
 * @method SysUserNotice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SysUserNoticeRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry);
    }

    protected static function getEntityClass(): string
    {
        return SysUserNotice::class;
    }

    public function readAll($userId)
    {
        return  $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', 1)
            ->where('n.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }
}
