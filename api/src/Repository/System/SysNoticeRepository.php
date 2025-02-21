<?php

namespace App\Repository\System;

use App\Entity\System\SysNotice;
use App\Repository\BaseRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<SysNotice>
 * @method SysNotice|null find($id, $lockMode = null, $lockVersion = null)
 * @method SysNotice|null findOneBy(array $criteria, array $orderBy = null)
 * @method SysNotice[]    findAll()
 * @method SysNotice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SysNoticeRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry);
    }

    protected static function getEntityClass(): string
    {
        return SysNotice::class;
    }
}
