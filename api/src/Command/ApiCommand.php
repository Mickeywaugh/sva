<?php

namespace App\Command;

use App\Repository\System\SysApiRepository;
use App\Service\DbService;
use App\Service\SysApiService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Routing\RouterInterface;

#[AsCommand(name: 'app:api-sync', description: 'Sync all routes to sys_api table')]
class ApiCommand extends BaseCommand
{
    protected string $pidFile = 'var/run/apiSync.pid';
    protected string $channelName = 'api.sync';
    private RouterInterface $router;
    private SysApiRepository $sysApiRepo;

    public function __construct(RouterInterface $router, SysApiRepository $sysApiRepo)
    {
        parent::__construct();
        $this->router = $router;
        $this->sysApiRepo = $sysApiRepo;
    }

    /**
     * 同步所有控制器路由到 sys_api 表
     */
    public function process(array $options = []): void
    {
        $this->info('开始同步路由到 sys_api 表');

        $routeCollection = $this->router->getRouteCollection();
        $syncedCount = 0;
        $skippedCount = 0;
        $totalCount = $routeCollection->count();
        $this->info("共 {$totalCount} 条路由");
        $syncedIds = [];
        foreach ($routeCollection->all() as $name => $route) {
            $controller = $route->getDefault('_controller');

            // 跳过非控制器路由（如框架内置路由）
            if (!$controller || !is_string($controller)) {
                $skippedCount++;
                continue;
            }

            // 跳过不包含 App\Controller 的路由
            if (!str_contains($controller, 'App\Controller')) {
                $skippedCount++;
                continue;
            }

            $path = $route->getPath();
            $methods = $route->getMethods();

            // 解析控制器类名提取模块名
            [$controllerClass] = explode('::', $controller);
            $module = $this->extractModule($controllerClass);

            try {
                $props = [
                    'module' => $module,
                    'name'   => $name,
                    'path'   => $path,
                    'method' => $methods[0],
                    'result' => NULL,
                    'responseCode' => NULL,
                    'responseContext' => NULL
                ];
                // 查找是否已存在相同 path+method 的记录，存在则更新
                $api = $this->sysApiRepo->findOrCreate(['name' => $name], $props);
                if ($api) {
                    $syncedCount++;
                    $syncedIds[] = $api->id;
                    $this->info("已同步: [$module] $name → " . implode('|', $methods) . " $path");
                } else {
                    $skippedCount++;
                    $this->info("跳过: [$module] $name → " . implode('|', $methods) . " $path");
                }
            } catch (\Throwable $e) {
                $this->error("同步失败 [$path]: " . $e->getMessage());
            }
        }
        // DbService::table("sys_api")->wheres(["id" => ["NOT_IN" => $syncedIds]])->delete();
        $this->success("路由同步完成: 同步 {$syncedCount} 条, 跳过 {$skippedCount} 条");
    }

    /**
     * 从控制器类名提取模块名
     * 如 App\Controller\System\SysApiController → System
     */
    private function extractModule(string $controllerClass): string
    {
        // 移除 App\Controller 前缀
        $relative = str_replace('App\Controller\\', '', $controllerClass);
        // 取第一段作为模块名
        $parts = explode('\\', $relative);
        return $parts[0] ?? 'Common';
    }
}
