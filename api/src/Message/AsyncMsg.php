<?php

namespace App\Message;

class AsyncMsg
{
  private int|string $id;
  private array $props;
  private string $transport;
  public function __construct(string $transport, int|string $id, array $props)
  {
    $this->transport = $transport;
    $this->id = $id;
    $this->props = $props;
  }

  public function getTransport(): string
  {
    return $this->transport;
  }

  public function getId(): string
  {
    return $this->id;
  }

  public function getProps(): array
  {
    return $this->props;
  }
}
