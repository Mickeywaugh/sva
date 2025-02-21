<?php

namespace App\Repository\System;

use App\Entity\System\SysLog;
use App\Repository\BaseRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BaseRepository<SysLog>
 * @method SysLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method SysLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method SysLog[]    findAll()
 * @method SysLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SysLogRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry);
    }
    protected static function getEntityClass(): string
    {
        return SysLog::class;
    }

    public function visitStats(): array
    {

        $retArray = [
            "type" => "pv",
            "title" => "Title",
            "todayCount" => 0,
            "totalCount" => 0,
            "growthRate" => 0,
            "granularityLabel" => "daily"
        ];

        return $retArray;
    }

    public function visitTrend(): array
    {
        $retArray = [
            "dates" => [],
            "pvList" => 0,
            "uvList" => 0,
            "ipList" => 0,
        ];

        return $retArray;
    }
}
