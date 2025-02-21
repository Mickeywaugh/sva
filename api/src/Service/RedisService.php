<?php

namespace App\Service;

use Predis\Client;

class RedisService
{
  protected $client;
  protected $dbIndex;
  public function __construct(int $dbIndex = 1)
  {
    $this->dbIndex = $dbIndex;
    $this->client = new Client($this->getCofing());
  }

  public function getClient(): Client
  {
    return $this->client;
  }
  public function getCofing(): array
  {
    $config = [
      "host" => $_ENV['REDIS_HOST'],
      "port" => $_ENV['REDIS_PORT'],
      "password" => $_ENV['REDIS_PASSWORD'],
      "database" => $this->dbIndex
    ];
    return $config;
  }

  public function setDbIndex(int $dbIndex): static
  {
    $this->dbIndex = $dbIndex;
    $this->client->select($dbIndex);
    return $this;
  }

  public static function init(int $dbIndex = 1)
  {
    $instance = new static($dbIndex); // 创建一个新的实例
    $instance->setDbIndex($dbIndex);
    return $instance->getClient();
  }
}
