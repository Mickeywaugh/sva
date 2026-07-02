<?php

namespace App\Service;

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\DbService as DB;
class BaseService
{

    public static function getInstance(): static
    {
        return new static();
    }

    public static function errorResponse(string $msg, int $code = 1, array $data = []): JsonResponse
    {
        Logger::error($msg, $data);
        return new JsonResponse([
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ]);
    }

    public static function successResponse(string $msg, array $data = []): JsonResponse
    {
        return new JsonResponse([
            'code' => 0,
            'msg' => $msg,
            'data' => $data
        ]);
    }

    public static function criticalResponse(string $msg, array $data = []): JsonResponse
    {
        Logger::critical($msg, $data);
        return new JsonResponse([
            'code' => 502,
            'msg' => $msg,
            'data' => $data
        ]);
    }

    public static function uniqidReal(int $length = 16)
    {
        // uniqid gives 13 chars, but you could adjust it to your needs.
        if (function_exists("openssl_random_pseudo_bytes")) {
            $bytes = openssl_random_pseudo_bytes(ceil($length / 2));
        } else {
            $bytes = random_bytes(ceil($length / 2));
        }
        return strtoupper(substr(bin2hex($bytes), 0, $length));
    }

    public static function getProjectDir()
    {
        return (new Kernel('dev', true))->getProjectDir();
    }

    public static function getProjectPath(string $fileName)
    {
        return self::getProjectDir() . "/" . $fileName;
    }

    public static function setProjectPath(string $fileName)
    {
        $projectPath = self::getProjectPath($fileName);
        try {
            self::mkdirP($fileName);
            return $projectPath;
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
            return false;
        }
    }

    /**
     * 数据文件夹
     * @param string $subPath order.roll
     * @return string|null
     */
    public static function getDataFolder(string $subPath)
    {
        //如果右边没有/则添加/
        $path = sprintf("data/%s", $subPath);
        self::mkdirP($path);
        return $path;
    }

    // 创建文件，路径相对于public目录,(index.php所在的目录)
    public static function touchFile(string $fileName)
    {
        $fileName = realpath($fileName);
        try {
            Logger::log($fileName);
            touch($fileName);
            return $fileName;
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
            return false;
        }
    }

    /**
     * 创建文件夹
     * @param string $fileName, 相对于项目目录
     * @param int $mode
     * @return string|bool
     */
    public static function mkdirP(string $fileName, $mode = 0775)
    {
        $fileName = self::getProjectPath($fileName);
        $pathName = dirname($fileName);
        try {
            if (!file_exists($pathName)) {
                mkdir($pathName, $mode, true);
            }
            return $fileName;
        } catch (\Exception $e) {
            Logger::error("create $fileName failed, msg=>" . $e->getMessage());
        }
    }
    public static function convertToSnakeCase(string $input): string
    {
        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $input));
    }

    public static function filterParams(array $params, array $filter = []): array
    {
        $result = [];
        $keywordKeys = ["keywords", "keyword"];
        if (empty($filter) || empty($params)) {
            return $params;
        }
        foreach ($filter as $key) {
            if (isset($params[$key])) {
                $result[self::convertToSnakeCase($key)] = in_array(strtolower($key), $keywordKeys) ? '%' . $params[$key] :  $params[$key];
            }
        }
        return $result;
    }

    public static function getRequest()
    {
        return new Request();
    }

    // 获取协议和域名
    public static function getSchemeHost()
    {
        $request = self::getInstance()->getRequest();
        $hostName = $request->getSchemeAndHttpHost();
        return $hostName;
    }
    public static function getIps()
    {
        $request = self::getInstance()->getRequest();
        $ip = $request->getClientIps();
        return $ip;
    }

    public static function getUserAgent()
    {
        $request = self::getInstance()->getRequest();
        $userAgent = $request->headers->get('User-Agent');
        return $userAgent;
    }

    // 驼峰转换下划线,首字小写不转换 configName => config_name
    public static function toSnakeCase(string $input): string
    {
        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $input));
    }

    // snakeCase 转换为小驼峰格式, config_name => configName
    public static function toCamelCase(string $input): string
    {
        // // 如果输入已经是小驼峰或单个单词，直接返回
        if (ctype_lower($input[0]) && !preg_match('/[A-Z]/', $input)) {
            return $input;
        }

        // 分割字符串（支持下划线、短横线分隔的单词）
        $words = preg_split('/[-_]+/', $input);

        // 将每个单词首字母转换为大写（除了第一个单词）
        array_walk($words, function (&$word, $index) {
            if ($index !== 0) {
                $word = ucfirst(strtolower($word));
            }
        });

        // 合并单词，形成小驼峰格式
        return implode('', $words);
    }

    // 转换为大驼峰格式, configName => ConfigName
    public static function toPascalCase(string $input): string
    {
        $camelCase = self::toCamelCase($input);
        // 将小驼峰的第一个字母转为大写，得到大驼峰形式
        return ucfirst($camelCase);
    }

    // 字典助手函数,根据字典组code 获取字典值 [value=>label]
    public static function getDictMap(string $code): array
    {
        $maps = Db::table('sys_dict')
            ->select('d.label', 'd.value')
            ->join('t', 'sys_dict_data', 'd', 't.id = d.dict_id')
            ->wheres(["t.dict_code" => $code])
            ->orderBy('sort', 'ASC')
            ->getResult();
        $types = [];
        foreach ($maps as $item) {
            $types[$item['value']] = $item['label'];
        }
        return $types;
    }

    /**
     * 保存base64图片到路径，并加入异常处理
     *
     * @param string $base64 图片的base64编码
     * @param string $filePath 保存图片的路径
     * @return string 返回图片保存的路径，或在异常时返回错误信息
     */
    public static function saveBase64Image(string $base64, string $filePath): string
    {
        try {
            if ($base64) {
                // mkdir(dirname($filePath), 0755, true);
                $base64 = str_replace('data:image/png;base64,', '', $base64);
                file_put_contents($filePath,  base64_decode($base64));
                return $filePath; // 成功保存后返回文件路径
            } else {
                Logger::log("Base64字符串为空");
                return false;
            }
        } catch (\Exception $e) {
            // 记录日志或者处理异常
            Logger::error("保存图片时发生错误：" . $e->getMessage());
            return false;
        }
    }

    /**
     * 输出文件
     * @param string $filePath 相对于项目目录的文件路径
     * @param bool $inline 是否内联显示文件
     * @return Response
     */
    public static function responseFile(string $filePath, bool $inline = true): Response
    {
        try {
            ob_start();
            $fileName = pathinfo($filePath, PATHINFO_BASENAME);
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $headerContentType = SELF::getContentType($extension);
            $fileContent = file_get_contents($filePath);
            $response = new Response($fileContent);
            // 输出文件头信息
            $response->headers->set("Content-Type", $headerContentType);
            $response->headers->set("Content-Disposition", ($inline ? "inline" : "attachment") . "; filename=" . $fileName);
            ob_end_flush();
            return $response;
        } catch (\Exception $e) {
            Logger::error("文件读取异常：" . $e->getMessage());
            SELF::errorResponse("File Read Error", 500);
            return new Response();
        }
    }

    public static function getContentType(string $extension)
    {
        $extension = strtolower($extension);
        return match ($extension) {
            'txt' => 'text/plain',
            'zip' => 'application/zip',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => 'application/octet-stream',
        };
    }

    public static function getApiUrl(): string
    {
        return sprintf("%s/api/v1", self::getHostName(":8000"));
    }

    public static function getPageUrl(): string
    {
        return sprintf("%s/api/v1", self::getHostName());
    }
    public static function getHostName(string $port = ":3000"): string
    {
        return $_ENV["APP_ENV"] == "dev" ? sprintf("http://localhost%s", $port) : "http://example.com";
    }

     public static function getRedisVersion(): string
    {
        try {
            $redis = \App\Service\RedisService::createClient();
            $info = $redis->info('server');
            return $info['redis_version'] ?? $info['Server']['redis_version'] ?? 'Unknown';
        } catch (\Throwable) {
            return 'Unknown';
        }
    }

    public static function getMysqlVersion(): string
    {
        try {
            // 利用 Doctrine Dbal 原生 SQL 查询获取 MySQL 版本
            $conn = DbService::getConnection();
            return $conn->fetchOne('SELECT VERSION()') ?: 'Unknown';
        } catch (\Throwable) {
            return 'Unknown';
        }
    }

    public static function getOsInfo(): string {
        try {
            return php_uname();
        } catch (\Throwable) {
            return 'Unknown';
        }
    }
}
