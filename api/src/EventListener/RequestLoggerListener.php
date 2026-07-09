<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use App\Repository\System\SysLogRepository;
use App\Entity\System\SysLog;
use Jenssegers\Agent\Agent;

class RequestLoggerListener
{
  private SysLogRepository $loggerRepo;
  private Agent $agent;
  private float $startTime = 0;
  private bool $save = false;
  private ?SysLog $sysLog = null;

  public function __construct(SysLogRepository $_loggerRepo)
  {
    $this->loggerRepo = $_loggerRepo;
    $this->agent = new Agent();
    $this->save = (bool) ($_ENV['APP_SYSLOG'] ?? false);
  }

  public function onKernelRequest(RequestEvent $reqEvent): void
  {
    $this->startTime = microtime(true);

    if (!$this->save) {
      return;
    }

    $request = $reqEvent->getRequest();

    // 跳过 OPTIONS 预检请求
    if ($request->getMethod() === 'OPTIONS') {
      return;
    }

    // 跳过登录接口
    if (str_contains($request->getPathInfo(), '/auth/login') || str_contains($request->getPathInfo(), 'system/logs/page')) {
      return;
    }

    $logData = [
      'requestMethod'  => $request->getMethod(),
      'requestParams'  => $request->getContent() ?: $request->getQueryString(),
      'requestUri'     => $request->getRequestUri(),
      'ip'             => implode(',', $request->getClientIps()),
      'browser'        => $this->agent->browser(),
      'browserVersion' => $this->agent->version($this->agent->browser()),
      'os'             => $this->agent->platform() . ' ' . $this->agent->version($this->agent->platform()),
    ];

    // 创建日志记录，保存实体引用供响应阶段更新
    $this->sysLog = $this->loggerRepo->create($logData);
  }

  public function onKernelResponse(ResponseEvent $resEvent): void
  {
    if (!$this->save || !$this->sysLog) {
      return;
    }

    // 计算执行耗时（毫秒）
    $executionTime = (int) ((microtime(true) - $this->startTime) * 1000);

    $updateData = [
      'responseCode' => (string) $resEvent->getResponse()->getStatusCode(),
      'executionTime'   => $executionTime,
    ];

    $this->loggerRepo->updateEntity($this->sysLog, $updateData, true);
  }
}
