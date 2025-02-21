<?php

namespace App\Repository\System;

use App\Entity\System\SysDict;
use App\Repository\BaseRepository;
use App\Repository\System\SysDictDataRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<SysDict>
 *
 * @method SysDict|null find($id, $lockMode = null, $lockVersion = null)
 * @method SysDict|null findOneBy(array $criteria, array $orderBy = null)
 * @method SysDict[]    findAll()
 * @method SysDict[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SysDictRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry);
    }

    protected static function getEntityClass(): string
    {
        return SysDict::class;
    }
}
