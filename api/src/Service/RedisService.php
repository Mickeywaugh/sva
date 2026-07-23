<?php

namespace App\Service;

use Predis\Client;

/**
 * Redis 连接池服务
 *
 * 设计要点（适配 FrankenPHP worker 模式）：
 * - 维护一个内部连接池，borrow/release 模式
 * - borrow 时自动 PING 检测连接健康，不健康则重新创建
 * - 支持最小空闲连接数（预热）和最大连接数（防爆）
 * - getInstance() 仍返回 Client（向后兼容），内部走默认连接
 * - createClient() 改为从池中借用，需配对 releaseClient()
 */
class RedisService
{
  private static ?self $singleton = null;

  // ============ 连接池 ============

  /** @var Client[] 空闲连接栈 */
  private array $idlePool = [];

  /** @var array<int, true> 已借出的连接 (spl_object_id => true) */
  private array $inUse = [];

  private array $config;
  private int $maxConnections;
  private int $minIdle;
  private int $totalCreated = 0;

  /** 默认共享客户端（向后兼容 getInstance()->set/get/...） */
  private ?Client $defaultClient = null;

  // ============ 配置解析 ============

  /**
   * 从环境变量 REDIS_URL 中解析 Redis 配置信息
   * REDIS_URL 格式: tcp://[password@]host:port[/db-index]
   */
  public static function getConfig(?array $config = null): array
  {
    $url = $_ENV['REDIS_URL'] ?? 'tcp://127.0.0.1:6379/0';
    $parsed = (array) parse_url($url);
    $envConfig = [
      "scheme"   => $parsed['scheme'] ?? 'tcp',
      "host"     => $parsed['host'] ?? '127.0.0.1',
      "port"     => $parsed['port'] ?? 6379,
      "password" => $parsed['pass'] ?? null,
      "database" => isset($parsed['path']) ? (int) ltrim($parsed['path'], '/') : 0,
    ];
    return $config ? $envConfig + $config : $envConfig;
  }

  // ============ 构造 & 单例 ============

  private function __construct(?array $config = null)
  {
    $this->config = self::getConfig($config);
    $this->maxConnections = max(3, (int)($_ENV['REDIS_POOL_MAX'] ?? 10));
    $this->minIdle = min($this->maxConnections, max(1, (int)($_ENV['REDIS_POOL_MIN'] ?? 2)));
  }

  /**
   * 连接池管理器单例
   */
  public static function getPool(?array $config = null): self
  {
    if (!self::$singleton) {
      self::$singleton = new self($config);
    }
    return self::$singleton;
  }

  /**
   * 向后兼容：返回默认共享 Client
   * 用法不变：RedisService::getInstance()->set('key', 'val')
   */
  public static function getInstance(?array $config = null): Client
  {
    return self::getPool($config)->getDefaultClient();
  }

  // ============ 连接池核心方法 ============

  /**
   * 从连接池借用一个 Redis 客户端
   *
   * @throws \RuntimeException 当连接池耗尽时
   */
  public function borrow(): Client
  {
    // 1. 从空闲池取出健康连接
    while (!empty($this->idlePool)) {
      $client = array_pop($this->idlePool);
      if ($this->isHealthy($client)) {
        $this->markInUse($client);
        return $client;
      }
      // 不健康则销毁
      $this->disconnect($client);
    }

    // 2. 未达上限：创建新连接
    if ($this->totalCreated < $this->maxConnections) {
      $client = $this->createConnection();
      $this->markInUse($client);
      return $client;
    }

    // 3. 池满：退避等待空闲连接
    $retries = 0;
    while ($retries < 30) {
      usleep(100000); // 100ms
      if (!empty($this->idlePool)) {
        $client = array_pop($this->idlePool);
        if ($this->isHealthy($client)) {
          $this->markInUse($client);
          return $client;
        }
        $this->disconnect($client);
      }
      $retries++;
    }

    throw new \RuntimeException(sprintf(
      'Redis connection pool exhausted: %d/%d connections in use',
      count($this->inUse),
      $this->maxConnections
    ));
  }

  /**
   * 归还连接到空闲池
   */
  public function release(Client $client): void
  {
    $id = spl_object_id($client);
    if (isset($this->inUse[$id])) {
      unset($this->inUse[$id]);

      // 保持最小空闲连接数，多余的直接关闭
      if (count($this->idlePool) < $this->minIdle) {
        $this->idlePool[] = $client;
      } else {
        $this->disconnect($client);
      }
    }
  }

  /**
   * 快捷方法：自动 borrow/release
   *
   * @template T
   * @param callable(Client): T $callback
   * @return T
   */
  public function withClient(callable $callback): mixed
  {
    $client = $this->borrow();
    try {
      return $callback($client);
    } finally {
      $this->release($client);
    }
  }

  // ============ 向后兼容方法 ============

  /**
   * 获取默认共享客户端（带健康检查 + 自动重连）
   */
  public function getDefaultClient(): Client
  {
    if (!$this->defaultClient || !$this->isHealthy($this->defaultClient)) {
      if ($this->defaultClient) {
        $this->disconnect($this->defaultClient);
      }
      $this->defaultClient = $this->createConnection();
    }
    return $this->defaultClient;
  }

  /**
   * 创建客户端（从池借用，需调用 releaseClient() 归还）
   *
   * 更新：不再每次都 new，改为从连接池借用。
   * 调用完毕后请使用 RedisService::releaseClient($redis) 归还，
   * 或使用 RedisService::with(fn($r) => ...) 自动管理。
   */
  public static function createClient(?array $config = null): Client
  {
    return self::getPool($config)->borrow();
  }

  /**
   * 归还 createClient() 借出的客户端
   */
  public static function releaseClient(Client $client): void
  {
    self::getPool()->release($client);
  }

  /**
   * 快捷执行：自动 borrow/release
   *
   * @template T
   * @param callable(Client): T $callback
   * @return T
   */
  public static function with(callable $callback): mixed
  {
    return self::getPool()->withClient($callback);
  }

  // ============ 内部方法 ============

  private function createConnection(): Client
  {
    $this->totalCreated++;
    Logger::debug(sprintf(
      'Redis pool: new connection [total=%d, idle=%d, inUse=%d]',
      $this->totalCreated,
      count($this->idlePool),
      count($this->inUse)
    ));
    return new Client($this->config);
  }

  private function isHealthy(Client $client): bool
  {
    try {
      $response = $client->ping();
      return $response === 'PONG' || (is_object($response) && method_exists($response, '__toString') && (string)$response === 'PONG');
    } catch (\Throwable $e) {
      Logger::debug('Redis health check failed: ' . $e->getMessage());
      return false;
    }
  }

  private function disconnect(Client $client): void
  {
    try {
      $client->disconnect();
    } catch (\Throwable) {
      // 忽略断开连接时的异常
    }
    $this->totalCreated = max(0, $this->totalCreated - 1);
  }

  private function markInUse(Client $client): void
  {
    $this->inUse[spl_object_id($client)] = true;
  }

  /**
   * 获取池状态（调试用）
   */
  public function getStats(): array
  {
    return [
      'totalCreated' => $this->totalCreated,
      'idle'         => count($this->idlePool),
      'inUse'        => count($this->inUse),
      'max'          => $this->maxConnections,
      'minIdle'      => $this->minIdle,
    ];
  }

  /**
   * 关闭所有连接（worker shutdown 时调用）
   */
  public function shutdown(): void
  {
    // 关闭默认客户端
    if ($this->defaultClient) {
      $this->disconnect($this->defaultClient);
      $this->defaultClient = null;
    }

    // 关闭空闲池
    foreach ($this->idlePool as $client) {
      $this->disconnect($client);
    }
    $this->idlePool = [];

    // 使用中的连接由调用方持有引用，请求结束后 PHP 自动回收
    $this->inUse = [];

    Logger::debug('Redis pool: all connections closed');
  }
}
