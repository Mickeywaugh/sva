<?php

namespace App\Service;

use Predis\Client;
use Predis\Connection\ConnectionException;

class RedisService
{
  private static $pool = [];
  private static $maxConnections = 10; // 最大连接数
  private static $timeout = 5;         // 超时时间（秒）

  // 从环境变量中读取redis配置信息，每个项目.env中配置的DBINDEX不能相同
  public static function getCofing(): array
  {
    $config =  [
      "scheme" => "tcp",
      "host" => $_ENV['REDIS_HOST'],
      "port" => $_ENV['REDIS_PORT'],
      "password" => $_ENV['REDIS_PASSWORD'],
      "database" => $_ENV['REDIS_DBINDEX']
    ];
    return $config;
  }


  /**
   * 获取 Redis 连接
   */
  public static function getConnection(): Client
  {
    // 检查是否有空闲连接
    foreach (self::$pool as $key => $connection) {
      if ($connection['inUse'] === false) {
        self::$pool[$key]['inUse'] = true;
        return $connection['client'];
      }
    }

    // 如果没有空闲连接且未达到最大连接数，则创建新连接
    if (count(self::$pool) < self::$maxConnections) {
      $client = new Client(self::getCofing());
      try {
        $client->connect();
        self::$pool[] = [
          'client' => $client,
          'inUse' => true,
        ];
        return $client;
      } catch (ConnectionException $e) {
        throw new \RuntimeException("Failed to connect to Redis: " . $e->getMessage());
      }
    }

    // 如果连接池已满，等待或抛出异常
    throw new \RuntimeException("Redis connection pool is full.");
  }

  /**
   * 归还 Redis 连接到池中
   */
  public static function releaseConnection(Client $client): void
  {
    foreach (self::$pool as &$connection) {
      if ($connection['client'] === $client) {
        $connection['inUse'] = false;
        break;
      }
    }
  }

  /**
   * 关闭所有连接
   */
  public static function closeAll(): void
  {
    foreach (self::$pool as $connection) {
      $connection['client']->disconnect();
    }
    self::$pool = [];
  }
}
