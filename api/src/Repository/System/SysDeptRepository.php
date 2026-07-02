<?php

namespace App\Repository\System;

use App\Entity\System\SysUser;
use App\Entity\System\SysDept;
use App\Service\Logger;
use App\Repository\BaseRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

/**
 * @extends BaseRepository<SysDept>
 *
 * @method SysDept|null find($id, $lockMode = null, $lockVersion = null)
 * @method SysDept|null findOneBy(array $criteria, array $orderBy = null)
 * @method SysDept[]    findAll()
 * @method SysDept[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SysDeptRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry);
    }

    protected static function getEntityClass(): string
    {
        return SysDept::class;
    }

    public function getList(int $pid = 0): array
    {
        $sysdeptTree = [];
        $sysdeptList = $this->findBy(["parentId" => $pid]);
        foreach ($sysdeptList as $sysdept) {
            $child = $this->getList($sysdept->getId());
            $data = $sysdept->toArray();
            if ($child) {
                $data["children"] = $child;
            }
            $sysdeptTree[] = $data;
        }
        return $sysdeptTree;
    }

    public function getTree(int $pid = 0): array
    {
        $sysdeptTree = [];
        $sysdeptList = $this->findBy(["parentId" => $pid]);

        foreach ($sysdeptList as $sysdept) {
            $child = $this->getTree($sysdept->getId());
            $data = [
                "value" => $sysdept->getId(),
                "label" => $sysdept->getName()
            ];
            if ($child) {
                $data["children"] = $child;
            }
            $sysdeptTree[] = $data;
        }
        return $sysdeptTree;
    }

    // 删除实体
    public function delete(array $ids): bool
    {
        if (!$ids) return false;
        try {
            $em = $this->getEm();
            foreach ($ids as $id) {
                $entity = $this->find($id);
                if ($entity) {
                    // is there user in sysdepts
                    $userList = $em->getRepository(SysUser::class)->findBy(["deptId" => $id]);
                    if (count($userList) > 0) {
                        Logger::log("该部门下存在用户，无法删除");
                        return false;
                    }
                    $em->remove($entity);
                };
            }
            $em->flush();
            return true;
        } catch (\Exception $e) {
            @Logger::log($e->getMessage());
            return false;
        }
    }
}
