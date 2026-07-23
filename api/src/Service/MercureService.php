<?php

namespace App\Service;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class MercureService
{
  private HubInterface $hub;

  public function __construct(HubInterface $mercureHub)
  {
    $this->hub = $mercureHub;
  }

  /**
   * 推送消息到 Mercure Hub
   *
   * @param string|string[] $topics 订阅主题，如 "dict.change" 或 ["dict.change", "system.onlineCount"]
   * @param mixed           $data   要推送的数据（自动 json_encode）
   * @param bool            $private 是否为私有消息
   * @param string|null     $id     消息唯一 ID
   * @param int|null        $retry  重连建议间隔（毫秒）
   */
  public function publish(
    array|string $topics,
    mixed $data,
    bool $private = false,
    ?string $id = null,
    ?int $retry = null,
  ): string {
    $jsonData = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE);

    // type 与第一个 topic 一致，使 Mercure Hub 发送对应的 event: 字段
    $topicArray = (array) $topics;
    $type = $topicArray[0] ?? null;

    $update = new Update(
      topics: $topics,
      data: $jsonData,
      private: $private,
      id: $id,
      type: $type,
      retry: $retry,
    );

    return $this->hub->publish($update);
  }

  /**
   * 推送字典变更通知
   */
  public function dictChange(string $dictCode): string
  {
    return $this->publish(
      topics: "dict.change",
      data: [
        "timestamp" => time(),
        "dictCode" => $dictCode,
      ]
    );
  }

  /**
   * 推送在线人数更新
   */
  public function onlineCount(int $count): string
  {
    return $this->publish(
      topics: "system.onlineCount",
      data: ["count" => $count],
    );
  }

  /**
   * 推送系统通知
   */
  public function systemNotification(string $title, string $message, string $type = "info"): string
  {
    return $this->publish(
      topics: "system.notification",
      data: [
        "title" => $title,
        "message" => $message,
        "type" => $type,
        "timestamp" => time(),
      ],
    );
  }

  /**
   * 推送用户通知
   *
   * @param int|string $userId 用户 ID
   */
  public function userNotice(int|string $userId, array $noticeData): string
  {
    return $this->publish(
      topics: "user.{$userId}.notices",
      data: $noticeData,
    );
  }

  /**
   * 推送通知撤回
   *
   * @param int|string $userId 用户 ID
   * @param int        $noticeId 要撤回的通知 ID
   */
  public function noticeRevoke(int|string $userId, int $noticeId): string
  {
    return $this->publish(
      topics: "user.{$userId}.noticeRevoke",
      data: ["id" => $noticeId],
    );
  }
}
