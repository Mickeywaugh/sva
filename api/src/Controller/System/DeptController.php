<?php

namespace App\Controller\System;

use App\Controller\BaseController;
use App\Repository\System\SysDeptRepository;
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

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
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

        $dept = $this->deptRepo->create($data);
        if ($dept) {
            return $this->success($dept->toArray());
        } else {
            return $this->error("部门创建失败");
        }
    }

    #[Route('/{id}/form', name: 'get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $dept = $this->deptRepo->find($id);
        if ($dept) {
            return $this->success($dept->toArray());
        } else {
            return $this->error("获取部门数据失败");
        }
    }

    #[Route('/{id}/status', name: 'setStatus', methods: ['PUT'])]
    public function setStatus(Request $request,int $id): JsonResponse
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

    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Request $request,int $id): JsonResponse
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
