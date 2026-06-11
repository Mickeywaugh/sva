<?php

namespace App\Repository\System;

use App\Entity\System\SysRole;
use App\Repository\BaseRepository;
use App\Service\Logger;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

/**
 * @extends BaseRepository<SysRole>
 *
 * @method SysRole|null find($id, $lockMode = null, $lockVersion = null)
 * @method SysRole|null findOneBy(array $criteria, array $orderBy = null)
 * @method SysRole[]    findAll()
 * @method SysRole[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SysRoleRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry);
    }

    protected static function getEntityClass(): string
    {
        return SysRole::class;
    }

    public function getTree()
    {
        $roleTree = [];
        $roleList = $this->findBy(["status" => 1, "deleteTime" => null]);

        foreach ($roleList as $role) {
            $data = [
                "value" => $role->getId(),
                "label" => $role->getName()
            ];
            $roleTree[] = $data;
        }
        return $roleTree;
    }

    // 删除ROLE实体
    public function delete($ids, bool $softDelete = true): bool
    {
        if (!$ids) return false;
        try {
            $em = $this->getEm();
            foreach ($ids as $id) {
                $entity = $this->find($id);
                if ($entity) {
                    if (count($entity->getUsers()) > 0) {
                        throw new Exception("该角色下存在用户，不能删除");
                    }
                    if ($softDelete) {
                        $entity->setDeleteTime();
                        $entity->setUpdateTime();
                        $em->persist($entity);
                    } else {
                        $em->remove($entity);
                    }
                };
            }
            $em->flush();
            return true;
        } catch (\Exception $e) {
            Logger::log($e->getMessage());
            return false;
        }
    }
}
