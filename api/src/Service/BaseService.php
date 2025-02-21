<?php

namespace App\Service;

use App\Kernel;
use Endroid\QrCode\Builder\Builder as QRCodeBuilder;
use Endroid\QrCode\Encoding\Encoding;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\DbService as DB;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Font\OpenSans;
use Endroid\QrCode\Label\LabelAlignment;

class BaseService
{
    public static $INITIALPASSWORD = "123456";
    public static $APISUFFIX = "/api/v1/";
    public static $uploadDir = "download";

    public static function getInstance(): static
    {
        return new static();
    }

    public static function errorResponse($msg, $code = 500, $data = null): JsonResponse
    {
        self::log($msg);
        return new JsonResponse([
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ]);
    }

    public static function successResponse($msg, $data = null): JsonResponse
    {
        return new JsonResponse([
            'code' => "00000",
            'msg' => $msg,
            'data' => $data
        ]);
    }
    public static function uniqidReal($lenght = 16)
    {
        // uniqid gives 13 chars, but you could adjust it to your needs.
        if (function_exists("openssl_random_pseudo_bytes")) {
            $bytes = openssl_random_pseudo_bytes(ceil($lenght / 2));
        } else {
            $bytes = random_bytes(ceil($lenght / 2));
        }
        return strtoupper(substr(bin2hex($bytes), 0, $lenght));
    }

    public static function getProjectDir()
    {
        return (new Kernel('dev', true))->getProjectDir();
    }

    public static function getProjectPath($fileName)
    {
        return self::getProjectDir() . "/" . $fileName;
    }

    public static function getParamater($param)
    {
        switch ($param) {
            case 'qrCodeUserUrl':
                return 'api_common/getEmployee/';
            case 'qrCodeOrderUrl':
                return 'form/order/';
            case 'qrCodeRawMatUrl':
                return 'api_common/getRawMat/';
            case 'qrCodeMachineUrl':
                return 'api_common/getMachine/';
        }
    }

    // 创建文件，路径相对于public目录,(index.php所在的目录)
    public static function touchFile(string $fileName)
    {
        $fileName = realpath($fileName);
        try {
            self::log($fileName, 'info');
            touch($fileName);
            return $fileName;
        } catch (\Exception $e) {
            self::log($e->getMessage(), 'error');
            return false;
        }
    }

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
            self::log("create $fileName failed, msg=>" . $e->getMessage(), 'error');
            return false;
        }
    }
    public static function convertToSnakeCase(string $input): string
    {
        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $input));
    }

    public static function filterParams($params, $filter = []): array
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
    /**
     * 日志记录
     * @param string $type
     * @param $msg
     * @return void
     */
    public static function log($msg, string $type = "log", string $logName = "log"): void
    {
        $logFile = sprintf("%s/var/log/%s.log", self::getProjectDir(), $logName);
        touch($logFile);
        // 检查文件大小是否超过100MB
        if (file_exists($logFile) && filesize($logFile) > 100 * 1024 * 1024) {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            // 只保留最后100条日志
            $lines = array_slice($lines, -100);
            file_put_contents($logFile, implode(PHP_EOL, $lines));
        }

        if (is_array($msg) || is_object($msg)) {
            $msg = json_encode($msg, JSON_UNESCAPED_SLASHES);
        }
        // 获取调用者信息
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $callerInfo = [];
        $traceCount = count($backtrace);
        foreach ($backtrace as $index => $frame) {
            // 跳过最后6层
            if ($index > $traceCount - 7) {
                break;
            } else {
                $callerInfo[] = sprintf(
                    "File %d: %s:%d %s()",
                    $index,
                    basename($frame['file']),
                    $frame['line'],
                    $frame['function'] ?? 'main'
                );
            }
        }

        $callerInfo = implode(" -> ", $callerInfo);
        $logText = sprintf("%s %s `%s`: %s", date("Y-m-d H:i:s"), $callerInfo, $type, $msg) . PHP_EOL;
        file_put_contents($logFile, $logText, FILE_APPEND);
    }
    // 字典助手函数,根据字典组code 获取字典值 [value=>name]
    public static function getDictMap($code): array
    {
        $maps = Db::table('sys_dict')
            ->select('name', 'value')
            ->wheres(["type_code" => $code])
            ->orderBy('sort', 'ASC')
            ->getResult();
        $types = [];
        foreach ($maps as $item) {
            $types[$item['value']] = $item['name'];
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
                self::log("Base64字符串为空", "error");
                return false;
            }
        } catch (\Exception $e) {
            // 记录日志或者处理异常
            self::log("保存图片时发生错误：" . $e->getMessage(), "error");
            return false;
        }
    }

    public static function generateQrcode(string $data = "", string $labelText = "", string $savePath = "", int $size = 300): bool|string
    {
        if ($data == '') {
            return false;
        }
        try {
            //实例化自身
            $instance = new static();
            $projectDir = $instance->getProjectDir();
            $projectPath = sprintf("public/%s", $savePath);
            self::mkdirP($projectPath);
            $fullSavePath = $projectDir ? sprintf("%s/%s", $projectDir, $projectPath) : $savePath;

            if ($labelText) {
                $QrCodeBuilder = new QRCodeBuilder(
                    data: $data,
                    encoding: new Encoding('UTF-8'),
                    errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                    size: $size,
                    margin: 10,
                    labelText: $labelText,
                    labelFont: new OpenSans(22),
                    labelAlignment: LabelAlignment::Center
                );
            } else {
                $QrCodeBuilder = new QRCodeBuilder(
                    data: $data,
                    encoding: new Encoding('UTF-8'),
                    errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                    size: $size,
                    margin: 10,
                );
            }

            $QrCodeBuilder->build()->saveToFile($fullSavePath);
            return $savePath;
        } catch (\Exception $e) {
            self::log("生成二维码时发生错误：" . $e->getMessage(), "error");
            return FALSE;
        }
    }

    /**
     * 输出文件
     * @param string $filePath 相对于项目目录的文件路径
     * @param string|null $fileName 返回至浏览器的文件名
     * @return void
     */
    public static function responseFile(string $filePath, bool $inline = true)
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $headerContentType = SELF::getContentType($extension);
        if (!$filePath) {
            SELF::errorResponse("Filepath empty", 500);
        }
        $projectFilePath = self::getProjectPath($filePath);
        try {
            $fileSize = filesize($projectFilePath);
            $fileName = basename($projectFilePath);
            $hander = fopen($projectFilePath, "r");
            ob_end_clean();
            ob_start();
            // 输出文件头信息
            header("Content-Type: " . $headerContentType);
            header("Content-Length: " . $fileSize);
            header("Content-Disposition: " . ($inline ? "inline" : "attachment") . "; filename=" . $fileName);
            fpassthru($hander);
            fclose($hander);
            ob_end_flush();
            exit;
        } catch (\Exception $e) {
            self::log("文件读取异常：" . $e->getMessage(), "error");
            SELF::errorResponse("File Read Error", 500);
        }
    }

    public static function getContentType(string $extension)
    {
        return match ($extension) {
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
}
