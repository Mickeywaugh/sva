<?php

namespace App\Service;

use Aws\AwsClient;
use Aws\Exception\AwsException;

class MinioService
{
  private $minioClient;
  private $bucket;

  public function __construct(string $bucket)
  {
    $this->minioClient = new AwsClient([
      'version' => 'latest',
      'endpoint' => $_ENV['MINIO_ENDPOINT'],
      'region' => 'shanghai-bt',
      'credentials' => [
        'key' => $_ENV['MINIO_ACCESS_KEY'],
        'secret' => $_ENV['MINIO_SECRET_KEY'],
      ],
    ]);
    $this->bucket = $bucket;
  }

  public function uploadFile(string $object, string $filePath): bool
  {
    try {
      $this->minioClient->putObject([
        'Bucket' => $this->bucket,
        'Key' => $object,
        'SourceFile' => $filePath,
      ]);
      return true;
    } catch (AwsException $e) {
      printf(__FUNCTION__ . ": FAILED\n");
      printf($e->getMessage() . "\n");
      return false;
    }
  }

  public function downloadFile(string $object, string $localFilePath): bool
  {
    try {
      $this->minioClient->getObject([
        'Bucket' => $this->bucket,
        'Key' => $object,
        'SaveAs' => $localFilePath,
      ]);
      return true;
    } catch (AwsException $e) {
      printf(__FUNCTION__ . ": FAILED\n");
      printf($e->getMessage() . "\n");
      return false;
    }
  }

  public function getObjectUrl(string $object): string
  {
    return $this->minioClient->presignedGetObject($this->bucket, $object, 3600);
  }
}
