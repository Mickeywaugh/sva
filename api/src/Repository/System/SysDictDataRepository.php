<?php

namespace App\Repository\System;

use App\Entity\System\SysDictData;
use App\Repository\BaseRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<SysDictData>
 *
 * @method SysDictData|null find($id, $lockMode = null, $lockVersion = null)
 * @method SysDictData|null findOneBy(array $criteria, array $orderBy = null)
 * @method SysDictData[]    findAll()
 * @method SysDictData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SysDictDataRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry);
    }

    protected static function getEntityClass(): string
    {
        return SysDictData::class;
    }


    public function options()
    {
        $data = $this->findAll();
        $list = [];
        foreach ($data as $item) {
            $list[] = [
                'value' => $item->getDictCode(),
                'label' => $item->getDictCode(),
            ];
        }
        return $list;
    }
}
