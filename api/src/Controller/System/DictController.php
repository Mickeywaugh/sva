<?php

namespace App\Controller\System;

use App\Controller\BaseController;
use App\Repository\System\SysDictDataRepository;
use App\Repository\System\SysDictRepository;
use App\Service\BaseService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('dict')]

class DictController extends BaseController
{

    private $dictRepo;
    private $dictDataRepo;
    public function __construct(SysDictRepository $_dictRepo, SysDictDataRepository $_dictDataRepo)
    {
        $this->dictRepo = $_dictRepo;
        $this->dictDataRepo = $_dictDataRepo;
    }

    #[Route('/list', name: 'dict.list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $dictList = [];
        try {
            $dictList = $this->dictRepo->list($params, ["DictData"]);
            return $this->success($dictList);
        } catch (\Exception $e) {
            return $this->error("字典数据获取失败:" . $e->getMessage());
        }
    }

    #[Route('/page', name: 'dict.page', methods: ['GET'])]
    public function page(Request $request): JsonResponse
    {
        $params = $request->query->all();
        $dictList = [];
        try {
            $dictList = $this->dictRepo->page($params);
            return $this->success($dictList);
        } catch (\Exception $e) {
            return $this->error("字典数据获取失败:" . $e->getMessage());
        }
    }

    #[Route('/{id}/form', name: 'dict.add', methods: ['GET'])]
    public function get($id): JsonResponse
    {
        $dict = $this->dictRepo->find($id);
        if ($dict) {
            return $this->success($dict->toArray());
        } else {
            return $this->error("字典数据获取失败");
        }
    }

    #[Route('', name: 'dict.create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $request->toArray();
        if (empty($data)) {
            return $this->error("参数错误");
        }
        try {
            $dict = $this->dictRepo->create($data);
            if ($dict) {
                return $this->success($dict->toArray());
            } else {
                return $this->error("字典数据添加失败");
            }
        } catch (\Exception $e) {
            return $this->error("字典数据更新失败:" . $e->getMessage());
        }
    }

    #[Route('/{id}', name: 'dict.update', methods: ['PUT'])]
    public function update(Request $request, $id): JsonResponse
    {
        $data = $request->toArray();
        if (empty($data)) {
            return $this->error("参数错误");
        }
        try {
            $dict = $this->dictRepo->update($id, $data);
            if ($dict) {
                return $this->success($dict->toArray());
            } else {
                return $this->error("字典数据更新失败");
            }
        } catch (\Exception $e) {
            return $this->error("字典数据更新失败:" . $e->getMessage());
        }
    }

    #[Route('/{id}/status', name: 'dict.setStatus', methods: ['PUT'])]
    public function setStatus(Request $request, $id): JsonResponse
    {
        $data = $request->toArray();
        if (empty($data)) {
            return $this->error("参数错误");
        }
        $dept = $this->dictRepo->update($id, $data);
        if ($dept) {
            return $this->success($dept->toArray());
        } else {
            return $this->error("更新失败");
        }
    }

    #[Route('/{ids}', name: 'dict.delete', methods: ['DELETE'])]
    public function delete($ids): JsonResponse
    {
        if (empty($ids)) {
            return $this->error("参数错误");
        }
        $result = $this->dictRepo->delete(explode(',', $ids));
        if ($result) {
            return $this->success(["ids" => $result]);
        } else {
            return $this->error("删除失败");
        }
    }


    #[Route('/{code}/options', name: 'dict.options', methods: ['GET'])]
    public function options($code): JsonResponse
    {
        if ($code == "") {
            return $this->error("参数错误");
        }
        $dictList = $this->dictRepo->options($code);
        return $this->success($dictList);
    }

    #[Route('-data/page', name: 'dict.dataPage', methods: ['GET'])]
    public function dataPage(Request $request): JsonResponse
    {

        $params = $request->query->all();
        BaseService::log($params);
        $data = $this->dictDataRepo->page($params);
        return $this->success($data);
    }
    #[Route('-data/{id}/form', name: 'dict.dataForm', methods: ['GET'])]
    public function dataForm($id): JsonResponse
    {
        $data = $this->dictDataRepo->find($id);
        if ($data) {
            return $this->success($data->toArray());
        } else {
            return $this->error("字典数据获取失败");
        }
    }
}
