<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use App\Repository\System\SysLogRepository;
use Jenssegers\Agent\Agent;

class RequestLoggerListener
{
  private SysLogRepository $loggerRepo;
  private Agent $agent;
  private array $logData = [];
  protected float $startTime;
  protected bool $save = false;
  public function __construct(SysLogRepository $_loggerRepo)
  {
    $this->loggerRepo = $_loggerRepo;
    $this->agent = new Agent();
    $this->logData = [
      "browser" => $this->agent->browser(),
      "browserVersion" => $this->agent->version($this->agent->browser()),
      "os" => sprintf("%s->%s:%s", $this->agent->device(), $this->agent->platform(), $this->agent->version($this->agent->platform()))
    ];
    $this->save = $_ENV['APP_SYSLOG'] ?? $this->save;
  }

  public function onKernelRequest(RequestEvent $reqEvent)
  {
    if (!$this->save) return;

    $request = $reqEvent->getRequest();
    if ($request->getMethod() === 'OPTIONS') {
      return;
    }
    if ($request->getPathInfo() === '/api/v1/auth/login') {
      return;
    }
    $this->startTime = microtime(true) * 1000;
    //添加请求信息
    $this->logData = $this->logData + [
      "module" => $request->getPathInfo(),
      "requestMethod" => $request->getMethod(),
      "requestParams" => $request->getQueryString(),
      'content' => $request->getContent(),
      "requestUri" => $request->getRequestUri(),
      "method" => $request->getMethod(),
      "ip" => implode(",", $request->getClientIps())
    ];
    $this->loggerRepo->create($this->logData);
  }

  public function onKernelResponse(ResponseEvent $resEvent)
  {
    if (!$this->save) return;

    $response = $resEvent->getResponse();

    if ($response->getStatusCode() >= 400) {
      return;
    }
    // 添加响应信息
    $responseTime = microtime(true) * 1000;
    $this->logData = $this->logData + [
      "requestMethod" => "Response",
      "module" => $resEvent->getRequest()->getPathInfo(),
      "responseContent" => $response->getStatusCode(),
      "executionTime" => $responseTime - $this->startTime,
      "createBy" => 1
    ];
    // 记录日志
    // BaseService::log($this->logData);
    $this->loggerRepo->create($this->logData);
  }
}
