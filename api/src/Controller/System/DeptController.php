<?php

namespace App\Controller\System;

use App\Controller\BaseController;
use App\Repository\System\SysDeptRepository;
use App\Service\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('system/dept', name: "system.dept.")]
class DeptController extends BaseController
{
    private SysDeptRepository $deptRepo;
    public function __construct(SysDeptRepository $_deptRepo)
    {
        $this->deptRepo = $_deptRepo;
    }

    #[Route('/page', name: 'page', methods: ['POST'])]
    public function page(Request $request): JsonResponse
    {
        $params = $request->toArray();
        $keywords = $params['keywords'] ?? '';
        if (!empty($keywords)) {
            $params['name'] = ["LIKE" => $keywords];
        }
        unset($params['keywords']);
        try {
            $deptList = $this->deptRepo->init()->page($params);
            return $this->success($deptList);
        } catch (\Exception $e) {
            return $this->error("部门数据获取失败:" . $e->getMessage());
        }
    }

    #[Route('/options', name: 'options', methods: ['GET'])]
    public function options(): JsonResponse
    {
        $deptTree = [];
        try {
            $deptTree = $this->deptRepo->getTree();
            return $this->success($deptTree);
        } catch (\Exception $e) {
            return $this->error("部门树状获取失败:" . $e->getMessage());
        }
    }

    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(int $id): JsonResponse
    {
        $dept = $this->deptRepo->find($id);
        if ($dept) {
            return $this->success($dept->toArray());
        } else {
            return $this->error("获取部门数据失败");
        }
    }


    #[Route('/{id}', name: 'set', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function set(int $id, Request $request): JsonResponse
    {
        $data = $request->toArray();
        if (empty($data)) {
            return $this->error("参数错误");
        }

        // 关联父级部门
        if (isset($data["parentId"])) {
            $pid = $data["parentId"];
            $parentDept = null;
            if ($pid) {
                $parentDept = $this->deptRepo->find($pid);
            }
            $data["parent"] = $parentDept;
        }
        if ($id == 0) {
            unset($data["id"]);
            Logger::log("create dept: " . json_encode($data));
            $dept = $this->deptRepo->create($data);
        } else {
            $dept = $this->deptRepo->update($id, $data);
        }
        if ($dept) {
            return $this->success($dept->toArray());
        } else {
            return $this->error("操作失败");
        }
    }

    #[Route('/{ids}', name: 'delete', methods: ['DELETE'])]
    public function delete(string $ids): JsonResponse
    {
        if (empty($ids)) {
            return $this->error("参数错误");
        }
        $ids = explode(",", $ids);
        $result = $this->deptRepo->delete($ids);
        if ($result) {
            return $this->success(["ids" => $ids]);
        } else {
            return $this->error("删除失败");
        }
    }
}
