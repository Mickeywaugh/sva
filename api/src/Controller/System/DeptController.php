<?php

namespace App\Controller\System;

use App\Controller\BaseController;
use App\Repository\System\SysDeptRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('dept')]
class DeptController extends BaseController
{
    private $deptRepo;
    public function __construct(SysDeptRepository $_deptRepo)
    {
        $this->deptRepo = $_deptRepo;
    }

    #[Route('', name: 'app_dept', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $deptList = [];
        try {
            $deptList = $this->deptRepo->getList();
            return $this->success($deptList);
        } catch (\Exception $e) {
            return $this->error("部门数据获取失败:" . $e->getMessage());
        }
    }

    #[Route('/options', name: 'dept.options', methods: ['GET'])]
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

    #[Route('', name: 'dept.create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $request->toArray();
        if (empty($data)) {
            return $this->error("参数错误");
        }

        // 关联父级部门
        if (isset($data["parentId"])) {
            $pid = $data["parentId"];
            unset($data["parentId"]);
            if ($pid == 0) {
                $parentDept = $this->deptRepo->getEntity();
            } else {
                $parentDept = $this->deptRepo->find($pid);
                if ($parentDept) {
                    $data["parent"] = $parentDept;
                }
            }
        }

        $dept = $this->deptRepo->create($data);
        if ($dept) {
            return $this->success($dept->toArray());
        } else {
            return $this->error("部门创建失败");
        }
    }

    #[Route('/{id}/form', name: 'dept.get', methods: ['GET'])]
    public function get($id): JsonResponse
    {
        $dept = $this->deptRepo->find($id);
        if ($dept) {
            return $this->success($dept->toArray());
        } else {
            return $this->error("获取部门数据失败");
        }
    }

    #[Route('/{id}/status', name: 'dept.setStatus', methods: ['PUT'])]
    public function setStatus(Request $request, $id): JsonResponse
    {
        $data = $request->toArray();
        if (empty($data)) {
            return $this->error("参数错误");
        }
        $dept = $this->deptRepo->update($id, $data);
        if ($dept) {
            return $this->success($dept->toArray());
        } else {
            return $this->error("更新失败");
        }
    }

    #[Route('/{id}', name: 'dept.update', methods: ['PUT'])]
    public function update(Request $request, $id): JsonResponse
    {
        $data = $request->toArray();
        if (empty($data)) {
            return $this->error("参数错误1");
        }

        $dept = $this->deptRepo->update($id, $data);
        if ($dept) {
            return $this->success($dept->toArray());
        } else {
            return $this->error("更新失败");
        }
    }

    #[Route('/{ids}', name: 'dept.delete', methods: ['DELETE'])]
    public function delete($ids): JsonResponse
    {
        $result = $this->deptRepo->delete(explode(",", $ids));
        if ($result) {
            return $this->success(["ids" => $result]);
        } else {
            return $this->error("删除失败");
        }
    }
}
