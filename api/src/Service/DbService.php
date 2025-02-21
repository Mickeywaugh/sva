<?php

namespace App\Service;

use App\Service\BaseService as Util;
use Doctrine\DBAL\DriverManager;

class DbService
{
  private $conn;
  private $table;
  public $qb;
  private $whereCond = "AND";
  private $schemaManager;
  private $debug = false;
  // 支持的查询方法
  protected $expr = [
    '=',
    '<>',
    '>',
    '>=',
    '<',
    '<=',
    'BETWEEN',
    'NOT_BETWEEN',
    'LIKE',
    'NOTLIKE',
    'IN',
    'NOT_IN',
    'LT_TIME',
    'GT_TIME',
    'LTE_TIME',
    'GTE_TIME',
    'FIND_IN',
  ];
  public function __construct()
  {
    $this->getConnection();
    $this->schemaManager = $this->conn->createSchemaManager();
    $this->qb = $this->conn->createQueryBuilder();
    $this->debug = $_ENV['APP_DEBUG'];
  }

  public function getDbConfig(): array
  {
    $databaseUrl = $_ENV['DATABASE_URL'];
    return parse_url($databaseUrl);
  }
  public function getConnection()
  {
    $conf = $this->getDbConfig();
    $attrs = [
      'driver' => "pdo_" . $conf['scheme'],
      'host' => $conf['host'],
      'dbname' => basename($conf['path']),
      'port' => $conf['port'],
      'user' => $conf['user'],
      'password' => $conf['pass']
    ];

    $this->conn = DriverManager::getConnection($attrs);
  }

  public function getConn()
  {
    return $this->conn;
  }

  public static function table($table, ?string $alias = 't')
  {
    $table = self::convertToSnakeCase($table);
    $instance = new static; // 创建一个新的实例
    $instance->table = $table;
    $instance->qb->from($table, $alias); // 设置表名
    $instance->qb->select('*');
    return $instance;
  }

  public function select(...$args)
  {
    $this->qb->select(...$args);
    return $this;
  }

  public function orderBy(...$orderBy)
  {
    $this->qb->orderBy(...$orderBy);
    return $this;
  }

  public function groupBy(...$groupBy)
  {
    $this->qb->groupBy(...$groupBy);
    return $this;
  }

  public function join(...$args)
  {
    $this->qb->join(...$args);
    return $this;
  }

  public function setWhereOr()
  {
    $this->whereCond = "OR";
    return $this;
  }

  public function setWhereAnd()
  {
    $this->whereCond = "AND";
    return $this;
  }

  public function resetWhere()
  {
    $this->qb->resetWhere();
    return $this;
  }

  public function execSql($sql, $params = [])
  {
    return $this->getConn()->executeStatement($sql, $params);
  }

  public function wheres($where)
  {

    if (empty($where)) return $this;
    $cond = $this->whereCond;
    // 处理模糊搜索条件
    foreach ($where as $key => $expr) {
      //判断$where的数组的结构，如果是[[key=>val],["key","op"],["key","op","val"]]结构
      if (is_int($key) && is_array($expr)) {
        // 数组元素为数组时，为模糊搜索条件
        $argc = count($expr);
        // 数组元素个数为1时，忽略
        //[key=val]
        if ($argc == 1) {
          $field = key($expr);
          $value = current($expr);
          $field = self::convertToSnakeCase($field);
          $this->andOrWhere($cond, "$field = :$field")->setParameter($field, $value);
          continue;
        };
        // 数组元素个数为2时,使用精确查询
        //[key,val]
        if ($argc == 2) {
          list($field, $op) = $expr;
          $field = self::convertToSnakeCase($field);
          $op = strtoupper($op);
          if ($op === 'NULL' || $op == null) {
            $this->andOrWhere($cond, $this->qb->expr()->isNull("$field"));
          }
          if ($op === 'NOTNULL') {
            $this->andOrWhere($cond, $this->qb->expr()->isNotNull("$field"));
          }
          continue;
        }
        // 数组元素个数为3时，使用模糊查询
        if ($argc == 3) {
          list($field, $opName, $value) = $expr;
          $field = self::convertToSnakeCase($field);
          $opName = strtoupper($opName);
          if (!in_array($opName, $this->expr)) continue;

          //以下操作需要将value转换化数组形式
          if (in_array($opName, ["BETWEEN", "NOT_BETWEEN", "NOT_IN", "IN"])) {
            $value = is_array($value) ? $value : explode(',', $value);
          } else {
            //如果其它操作value为数组，则忽略
            if (is_array($value)) $value = $value[0];
          }

          if (in_array($opName, ["BETWEEN", "NOT_BETWEEN"])) {
            if (count($value) != 2) continue;
            if ($opName == "BETWEEN") {
              $subQb = $this->qb->expr()->comparison("$field", $opName, $this->qb->expr()->and(':start', ':end'));
              // $subQb = $this->qb->expr()->and($this->qb->expr()->gt("$field", ':start'), $this->qb->expr()->lt("$field", ':end'));
            } else {
              $subQb = $this->qb->expr()->orX($this->qb->expr()->lt("$field", ':start'), $this->qb->expr()->gt("$field", ':end'));
            }

            $this->andOrWhere($cond, $subQb)
              ->setParameter('start', $value[0])
              ->setParameter('end', $value[1]);
          } else {
            switch ($opName) {
              case "LIKE":
                $this->andOrWhere($cond, $this->qb->expr()->like("$field", ':value'))
                  ->setParameter('value', "%" . $value . "%");
                break;

              case "NOT_LIKE":
                $this->andOrWhere($cond, $this->qb->expr()->notLike("$field", ':value'))
                  ->setParameter('value', "%" . $value . "%");
                break;

              case "IN":
                $this->andOrWhere($cond, $this->qb->expr()->in("$field", ':range'))
                  ->setParameter('range', $value);
                break;

              case "NOT_IN":
                $this->andOrWhere($cond, $this->qb->expr()->notIn("$field", ':range'))
                  ->setParameter('range', $value);
                break;
              case "FIND_IN":
                $this->andOrWhere($cond, "FIND_IN_SET(:value, t.$field)")
                  ->setParameter('value', $value);
              default:
                $this->andOrWhere($cond, "$field $opName :$field")->setParameter($field, $value);
                break;
            }
          }
        }
      } else {
        // $where=["key"=>"value"] 结构
        // 数组元素不为数组时，默认使用精确查询
        // 字段不在实体属性中，忽略
        $key = $this->convertToSnakeCase($key);
        // if (!in_array($key, $fieldlist)) continue;
        // 如何搜索字符串中有%，则使用主动使用模糊搜索
        if (is_string($expr) && strpos($expr, '%') !== false) {
          $this->andOrWhere($cond, $this->qb->expr()->like("$key", ':keyword'))
            ->setParameter('keyword', $expr);
          continue;
        } else {
          $this->andOrWhere($cond, "$key = :$key")->setParameter($key, $expr);
        }
      }
      // Util::log($where);
    }
    return $this;
  }

  public function pagination($pageSize, $pageNum)
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

  private function andOrWhere($cond, $argv)
  {
    if ($cond === "AND") {
      $this->qb->andWhere($argv);
    }
    if ($cond === "OR") {
      $this->qb->orWhere($argv);
    }
    return $this->qb;
  }

  public function getResult(): array
  {
    if ($this->debug) Util::log($this->qb->getSQL());
    return $this->qb->fetchAllAssociative();
  }

  public function getCount()
  {
    return $this->qb->select("COUNT(*) AS count")->fetchOne();
  }

  public function getFirst()
  {
    $result = $this->getResult();
    return $result ? $result[0] : null;
  }

  public function whereEq($field, $value): static
  {
    $this->andOrWhere($this->whereCond, $this->qb->expr()->eq($this->convertToSnakeCase($field), $value));
    return $this;
  }

  public function whereNeq($field, $value): static
  {
    $this->andOrWhere($this->whereCond, $this->qb->expr()->neq($this->convertToSnakeCase($field), $value));
    return $this;
  }

  public function whereIn($field, $values): static
  {
    $this->andOrWhere($this->whereCond, $this->qb->expr()->in($this->convertToSnakeCase($field), $values));
    return $this;
  }

  public function whereLike($field, $value): static
  {
    $this->andOrWhere($this->whereCond, $this->qb->expr()->like($this->convertToSnakeCase($field), ':value'))
      ->setParameter('value', "%" . $value . "%");
    return $this;
  }

  public function whereNotLike($field, $value): static
  {
    $this->andOrWhere($this->whereCond, $this->qb->expr()->notLike($this->convertToSnakeCase($field), ':value'))
      ->setParameter('value', "%" . $value . "%");
    return $this;
  }

  public function whereNotIn($field, $values): static
  {
    $this->andOrWhere($this->whereCond, $this->qb->expr()->notIn($this->convertToSnakeCase($field), $values));
    return $this;
  }

  public function whereNull($field): static
  {
    $this->andOrWhere($this->whereCond, $this->qb->expr()->isNull($this->convertToSnakeCase($field)));
    return $this;
  }

  public function whereNotNull($field): static
  {
    $this->andOrWhere($this->whereCond, $this->qb->expr()->isNotNull($this->convertToSnakeCase($field)));
    return $this;
  }

  public function whereBetween($field, array $value): static
  {
    $this->andOrWhere($this->whereCond, $this->qb->expr()->comparison($this->convertToSnakeCase($field), "BETWEEN", $this->qb->expr()->and(':start', ':end')))
      ->setParameter('start', $value[0])
      ->setParameter('end', $value[1]);
    return $this;
  }

  public function comparison($field, $op, $value): static
  {
    $this->andOrWhere($this->whereCond, "$field $op :$field")->setParameter($field, $value);
    return $this;
  }

  public function update(array $data): int
  {
    $this->qb->update($this->table);
    foreach ($data as $key => $value) {
      $key = $this->convertToSnakeCase($key);
      $this->qb->set($key, ":$key")->setParameter($key, $value);
    }
    return $this->qb->executeStatement();
  }

  // 插入数据
  public function insert(array $data): int
  {
    // 设置要插入的数据
    foreach ($data as $field => $value) {
      $field = self::convertToSnakeCase($field);
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

  public function getDbTables($prefix = ""): array
  {
    $tableObjs = $this->schemaManager->listTables();
    $tables = [];
    foreach ($tableObjs as $tableObj) {
      // 根据表名前缀搜索
      if (!empty($prefix) && strpos($tableObj->getName(), $prefix) === 0) {
        $tables[] = $tableObj->getName();
      } else {
        $tables[] = $tableObj->getName();
      }
    }
    return $tables;
  }
  private function getTableFields($table = null): array
  {
    $table = $table ?: $this->table;
    $fieldsObjs = $this->schemaManager->listTableColumns($table);
    $fields = [];
    foreach ($fieldsObjs as $field) {
      $fields[] = $field->getName();
    }
    return $fields;
  }

  // 驼峰转下划线
  private static function convertToSnakeCase(string $input): string
  {
    return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $input));
  }
}
