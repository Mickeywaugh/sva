<?php

namespace App\Repository\System;

use App\Entity\System\SysConfig;
use App\Repository\BaseRepository;
use Doctrine\Persistence\ManagerRegistry;

class SysConfigRepository extends BaseRepository
{

    /**
     * @extends BaseRepository<SysConfig>
     * @method SysConfig|null find($id, $lockMode = null, $lockVersion = null)
     * @method SysConfig|null findOneBy(array $criteria, array $orderBy = null)
     * @method SysConfig[]    findAll()
     * @method SysConfig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry);
    }

    protected static function getEntityClass(): string
    {
        return SysConfig::class;
    }
}
