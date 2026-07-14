<?php

namespace App\Repository\System;

use App\Entity\System\SysApi;
use App\Repository\BaseRepository;
use Doctrine\Persistence\ManagerRegistry;

class SysApiRepository extends BaseRepository
{

    /**
     * @extends BaseRepository<SysApi>
     * @method SysApi|null find($id, $lockMode = null, $lockVersion = null)
     * @method SysApi|null findOneBy(array $criteria, array $orderBy = null)
     * @method SysApi[]    findAll()
     * @method SysApi[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry);
    }

    protected static function getEntityClass(): string
    {
        return SysApi::class;
    }
}
