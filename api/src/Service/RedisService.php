<?php

namespace App\Service;

use Predis\Client;

class RedisService
{
  private static ?Client $instance = null;

  // 从环境变量 REDIS_URL 中解析 Redis 配置信息
  // REDIS_URL 格式: tcp://[password@]host:port[/db-index]
  public static function getConfig(?array $config = null): array
  {
    $url = $_ENV['REDIS_URL'] ?? 'tcp://127.0.0.1:6379/0';
    $parsed = (array) parse_url($url);
    $envConfig = [
      "scheme" => $parsed['scheme'] ?? 'tcp',
      "host" => $parsed['host'] ?? '127.0.0.1',
      "port" => $parsed['port'] ?? 6379,
      "password" => $parsed['pass'] ?? null,
      "database" => isset($parsed['path']) ? (int) ltrim($parsed['path'], '/') : 0,
    ];
    return $config ? $envConfig + $config : $envConfig;
  }

  public static function getInstance(?array $config = null)
  {
    if (!self::$instance) {
      self::$instance = new Client(self::getConfig($config));
    }
    return self::$instance;
  }

  public static function createClient(?array $config = null)
  {
    return new Client(self::getConfig($config));
  }
}
