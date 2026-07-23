<?php

namespace App\Service;

use App\Service\Logger;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Connection;

class DbService
{
  private Connection $conn;
  private mixed $table;
  public ?QueryBuilder $qb;
  private int $whereCounter = 0;
  private string $whereCond = "AND";
  private AbstractSchemaManager $schemaManager;
  private bool $debug = false;
  protected bool $isLogSql = true;

  // 支持的查询方法
  protected array $exprs = [
    '=',
    '<>',
    '>',
    '>=',
    '<',
    '<=',
    'BETWEEN',
    '!BETWEEN',
    'NULL',
    '!NULL',
    'LIKE',
    '!LIKE',
    'IN',
    '!IN',
    'LT_TIME',
    'GT_TIME',
    'LTE_TIME',
    'GTE_TIME',
    'FIND_IN',
    'OR'
  ];
  public function __construct(?string $dsn = null)
  {
    $this->conn = self::getConnection($dsn);
    $this->schemaManager = $this->conn->createSchemaManager();
    $this->qb = $this->conn->createQueryBuilder();
    $this->resetQueryBuilder();
  }

  public static function getConnection(?string $dsn = null)
  {
    //获取数据库配置信息
    if (!$dsn) $dsn = 'DATABASE_URL';
    $conf = (array) parse_url($_ENV[$dsn]);
    if (!$conf) {
      throw new \Exception("Invalid DSN configuration");
    }
    $attrs = [
      'driver' => "pdo_" . $conf['scheme'],
      'host' => $conf['host'],
      'dbname' => basename($conf['path']),
      'port' => $conf['port'],
      'user' => $conf['user'],
      'password' => $conf['pass']
    ];
    //将query参数转换为数组
    if (isset($conf['query'])) {
      parse_str($conf['query'], $queryParams);
      $attrs["driverOptions"] = $queryParams;
    }
    return DriverManager::getConnection($attrs);
  }

  public static function table(string $table, ?string $alias = 't', ?string $dsn = null)
  {
    $instance = new static($dsn); // 创建一个新的实例
    $instance->table = $table;
    $instance->qb->from($table, $alias); // 设置表名
    $instance->qb->select('*');
    $instance->resetQueryBuilder();
    return $instance;
  }

  public function resetQueryBuilder(): static
  {
    $this->qb->resetWhere();
    $this->qb->resetOrderBy();
    $this->qb->resetGroupBy();
    $this->qb->resetHaving();
    return $this;
  }

  public function getQueryBuilder(): ?QueryBuilder
  {
    return $this->qb;
  }

  public function setQeuryBuilder(?QueryBuilder $qb): static
  {
    $this->qb = $qb;
    return $this;
  }

  /**
   * 设置查询字段
   * 
   * @param string ...$args 要查询的字段
   * @return static
   */
  public function select(string ...$args)
  {
    $this->qb->select(...$args);
    return $this;
  }


  /**
   * 设置排序字段
   * 
   * @param mixed ...$orderBy 排序字段 sort ,order
   * @return static
   */
  public function orderBy(mixed ...$orderBy)
  {
    $this->qb->orderBy(...$orderBy);
    return $this;
  }

  /**
   * 设置分组字段
   * 
   * @param mixed ...$groupBy 分组字段 expression1,expression2
   * @return static
   */
  public function groupBy(mixed ...$groupBy)
  {
    $this->qb->groupBy(...$groupBy);
    return $this;
  }

  /**
   * @param mixed ...$args  $formAlias,$join,$alias,$condition
   * @return static
   */
  public function join(mixed ...$args): static
  {
    $this->qb->join(...$args);
    return $this;
  }

  /** 
   * @param mixed ...$args  $formAlias,$join,$alias,$condition
   * @return static
   */
  public function leftJoin(mixed ...$args)
  {
    $this->qb->leftJoin(...$args);
    return $this;
  }

  /**
   * @param mixed ...$args  $formAlias,$join,$alias,$condition
   * @return static
   */
  public function rightJoin(mixed ...$args)
  {
    $this->qb->rightJoin(...$args);
    return $this;
  }

  /**
   * @param mixed ...$args  $formAlias,$join,$alias,$condition
   * @return static
   */
  public function innerJoin(mixed ...$args)
  {
    $this->qb->innerJoin(...$args);
    return $this;
  }

  /**
   * 设置最大结果数
   * 
   * @param int $maxResults 最大结果数
   * @return static
   */
  public function setMaxResults(int $maxResults)
  {
    $this->qb->setMaxResults($maxResults);
    return $this;
  }

  public function resetWhere()
  {
    $this->qb->resetWhere();
    return $this;
  }

  /**
   * 执行sql并返回结果集对象
   * 
   * @param string $sql SQL语句
   * @param array $params 参数数组
   * @return \Doctrine\DBAL\Result 结果集对象
   */
  public static function execSql(string $sql, array $params = [], ?string $dsn = null): mixed
  {
    return self::getConnection($dsn)->executeQuery($sql, $params);
  }

  /**
   * 执行sql并返回多行关联数组
   */
  public static function fetchAll(string $sql, array $params = [], ?string $dsn = null): array
  {
    return self::execSql($sql, $params, $dsn)->fetchAllAssociative();
  }

  /**
   * 执行sql并返回单行关联数组
   */
  public static function fetchOne(string $sql, array $params = [], ?string $dsn = null): array|false
  {
    return self::execSql($sql, $params, $dsn)->fetchAssociative();
  }

  /**
   * 执行sql并返回单个值
   */
  public static function fetchScalar(string $sql, array $params = [], ?string $dsn = null): mixed
  {
    return self::execSql($sql, $params, $dsn)->fetchOne();
  }

  public function setWhere(string $where): static
  {
    if (in_array($where, ["AND", "OR"])) {
      $this->whereCond = $where;
    }
    return $this;
  }

  /**
   * 设置查询条件
   * @param array $where 查询条件 [field=>value]
   * @return static
   */
  public function wheres(array $where): static
  {
    if (empty($where)) return $this;
    // 处理模糊搜索条件
    foreach ($where as $field => $expr) {
      $paramName = sprintf("value%d", $this->whereCounter);
      $start = sprintf("start%d",  $this->whereCounter);
      $end = sprintf("end%d",  $this->whereCounter);
      //判断$where的数组的结构，只支持key=>value的形式, 如果value为数组，则认为是非精确查询条件，默认为数组第1个元素Key为操作符，value为匹配条件
      $rfield = self::toSnakeCase($field);
      if (is_array($expr)) {
        $op = strtoupper(key($expr));
        $condition = current($expr);
        if (!in_array($op, $this->exprs)) {
          //如果操作符不在$this->exprs数组中，则默认为精确查询
          $this->setQbWhere("$rfield = :$paramName")->setParameter($paramName, $condition);
        } else {
          switch ($op) {
            case "NULL":
              $this->setQbWhere($this->qb->expr()->isNull("$rfield"));
              break;
            case "!NULL":
              $this->setQbWhere($this->qb->expr()->isNotNull("$rfield"));
              break;
            case "LIKE":
              $rfields = explode("|", $rfield); //支持多个字段值like查询 "name|title"=>['like','value']
              $conditions = [];
              foreach ($rfields as $rfield) {
                $field = self::toSnakeCase($rfield);
                $conditions[] = $this->qb->expr()->like($rfield, ":$paramName");
              }

              // 正确的方式：使用 expr()->or() 组合多个条件
              if (count($conditions) > 0) {
                $orExpr = $conditions[0];
                for ($i = 1; $i < count($conditions); $i++) {
                  $orExpr = $this->qb->expr()->or($orExpr, $conditions[$i]);
                }
                $this->setQbWhere($orExpr)->setParameter($paramName, "%" . $condition . "%");
              }
              break;
            case "!LIKE":
              $this->setQbWhere($this->qb->expr()->notLike("$rfield", ":$paramName"))
                ->setParameter($paramName, "%" . $condition . "%");
              break;
            case "IN":
              $this->setQbWhere(sprintf("%s IN (%s)", $rfield, implode(",", (array)$condition)));
              break;
            case "!IN":
              $this->setQbWhere(sprintf("%s NOT IN (%s)", $rfield, implode(",", (array)$condition)));
              break;
            case "FIND_IN":
              $this->setQbWhere("FIND_IN_SET(:$paramName, $rfield)")
                ->setParameter($paramName, $condition);
              break;
            case "BETWEEN":
              $betweenExpr = $this->qb->expr()->and(
                $this->qb->expr()->gte("$rfield", ":$start"),
                $this->qb->expr()->lte("$rfield", ":$end")
              );
              $this->setQbWhere($betweenExpr)->setParameter($start, $condition[0])->setParameter($end, $condition[1]);
              break;
            case "!BETWEEN":
              $notBetweenExpr = $this->qb->expr()->or(
                $this->qb->expr()->lt("$rfield", ":$start"),
                $this->qb->expr()->gt("$rfield", ":$end")
              );
              $this->setQbWhere($notBetweenExpr)->setParameter("$start", $condition[0])->setParameter($end, $condition[1]);
              break;
            case "OR":
              // $expr为数组,循环添加orX操作
              foreach ($condition as $orVal) {
                $paramName = sprintf("value%d", $this->whereCounter);
                $this->qb->orWhere("$rfield = :$paramName")->setParameter($paramName, $orVal);
                $this->whereCounter++;
              }
              break;
            default:
              $this->setQbWhere("$rfield $op :$paramName")->setParameter($paramName, $condition);
              break;
          }
        }
      } else {
        // 处理多个字段or查询 $where["t.title|t.content"]="value"
        if (str_contains($rfield, "|")) {
          //按|分隔字符得到数组并以or条件进行搜索
          $sfields = explode("|", $rfield);
          $conditions = [];
          foreach ($sfields as $f) {
            $f = self::toSnakeCase($f);
            $conditions[] = $this->qb->expr()->eq($f, ":$paramName");
          }
          // 使用 expr()->or() 组合多个字段的 OR 条件
          if (count($conditions) > 0) {
            $orExpr = $conditions[0];
            for ($i = 1; $i < count($conditions); $i++) {
              $orExpr = $this->qb->expr()->or($orExpr, $conditions[$i]);
            }
            $this->setQbWhere($orExpr)->setParameter($paramName, $expr);
          }
        } elseif ($expr === NULL || $expr === "NULL") {
          $this->setQbWhere($this->qb->expr()->isNull("$rfield"));
        } elseif (strtoupper($expr) == '!NULL') {
          $this->setQbWhere($this->qb->expr()->isNotNull("$rfield"));
        } else {
          $this->setQbWhere("$rfield = :$paramName")->setParameter($paramName, $expr);
        }
      }
      $this->whereCounter++;
    }
    return $this;
  }

  public function pagination(int $pageSize, int $pageNum)
  {
    $offset = ($pageNum - 1) * $pageSize;
    if ($pageSize !== null) {
      $this->qb->setMaxResults($pageSize);
    }
    if ($offset !== null) {
      $this->qb->setFirstResult($offset);
    }
    return $this;
  }

  private function setQbWhere(mixed $argv)
  {
    if ($this->whereCond === "AND") {
      $this->qb->andWhere($argv);
    }
    if ($this->whereCond === "OR") {
      $this->qb->orWhere($argv);
    }
    return $this->qb;
  }

  public function getResult(): array
  {
    return $this->logSql()->qb->fetchAllAssociative();
  }

  public function getValue(string $field)
  {
    $result = $this->getFirst();
    return $result[$field] ?? null;
  }

  public function getCount()
  {
    return $this->logSql()->qb->select("COUNT(*) AS count")->fetchOne();
  }

  public function getMax(string $field = 'id')
  {
    return $this->logSql()->qb->select("MAX($field) AS max")->fetchOne();
  }

  public function getMin(string $field = 'id')
  {
    return $this->logSql()->qb->select("MIN($field) AS min")->fetchOne();
  }

  public function getSum(string $field = 'id')
  {
    return $this->logSql()->qb->select("SUM($field) AS sum")->fetchOne();
  }

  public function getAvg(string $field = 'id')
  {
    return $this->logSql()->qb->select("AVG($field) AS avg")->fetchOne();
  }

  public function getFirst()
  {
    $result = $this->getResult();
    return $result ? $result[0] : null;
  }

  public function find(int $id, ?string $pk = "id")
  {
    return $this->qb
      ->where(sprintf('%s = :id', $pk))
      ->setParameter('id', $id)
      ->fetchAssociative() ?: null;
  }

  public function getColumn(string $field): array
  {
    $result = $this->getResult();
    return array_column($result, $field);
  }

  public function comparison(string $field, string $op, mixed $value): static
  {
    $this->setQbWhere("$field $op :$field")->setParameter($field, $value);
    return $this;
  }

  public function update(array $data): int
  {
    $this->qb->update($this->table);
    foreach ($data as $key => $value) {
      $key = $this->toSnakeCase($key);
      $this->qb->set($key, ":$key")->setParameter($key, $value);
    }
    return $this->qb->executeStatement();
  }

  // 插入数据
  public function insert(array $data): int
  {
    // 设置要插入的数据
    foreach ($data as $field => $value) {
      $field = self::toSnakeCase($field);
      $this->qb->setValue($field, ":$field")->setParameter($field, $value);
    }
    // 执行插入并返回受影响的行数
    return $this->qb->insert($this->table)->executeStatement();
  }

  // 删除数据
  public function delete(): int
  {
    return $this->qb->delete($this->table)->executeStatement();
  }

  public function getDbTables(string $prefix = ""): array
  {
    $tableObjs = $this->schemaManager->introspectTables();
    $tables = [];
    foreach ($tableObjs as $tableObj) {
      $tableName = $tableObj->getObjectName()->toString();
      // 根据表名前缀搜索
      if (!empty($prefix) && strpos($tableName, $prefix) === 0) {
        $tables[] = $tableName;
      } else {
        $tables[] = $tableName;
      }
    }
    return $tables;
  }

  // 驼峰转下划线
  private static function toSnakeCase(string $input): string
  {
    return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $input));
  }

  public function debug(): self
  {
    $this->debug = true;
    return $this;
  }

  private function logSql(): self
  {
    if ($this->debug) Logger::log($this->qb->getSQL());
    return $this;
  }

  public function __destruct()
  {
    $this->qb = null;
  }
}
