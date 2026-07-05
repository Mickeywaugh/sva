<?php

namespace App\Repository;

use App\Service\Logger;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use ReflectionClass;

/**
 * @extends ServiceEntityRepository<object>
 * @template T of object
 */
abstract class BaseRepository extends ServiceEntityRepository
{

    protected ?QueryBuilder $qb = null;
    protected int $whereCounter = 0;
    protected string $whereCond = "AND";
    /**
     * @var class-string<T>
     */
    protected ?string $entityClass = null;
    protected ?EntityManagerInterface $em = null;
    protected bool $debug = false;
    protected bool $isLogSql = true;
    protected bool $returnArray = false;
    // 支持的查询方法
    protected array $expr = [
        '=',
        '<>',
        '>',
        '>=',
        '<',
        '<=',
        'BETWEEN',
        'NOT_BETWEEN',
        'NULL',
        'NOT_NULL',
        'LIKE',
        'NOT_LIKE',
        'IN',
        'EMPTY',
        'NOT_IN',
        'LT_TIME',
        'GT_TIME',
        'LTE_TIME',
        'GTE_TIME',
        'FIND_IN',
        'OR'
    ];

    // =========================================================================
    // 构造方法 / 生命周期方法
    // =========================================================================

    public function __construct(ManagerRegistry $registry, ?string $entityClass = "")
    {
        $this->entityClass = $entityClass ?: static::getEntityClass();
        parent::__construct($registry, $this->entityClass);
        $this->em = $this->getEntityManager();
        $this->init();
    }

    public function __destruct()
    {
        $this->em->close();
        $this->em = null;
        $this->qb = null;
        $this->entityClass = null;
    }

    // =========================================================================
    // 静态方法
    // =========================================================================

    /**
     * 抽象方法，子类需实现并返回实体类名
     *
     * @return string
     */
    abstract protected static function getEntityClass(): string;

    /**
     * 获取 Repository 实例
     *
     * @param ManagerRegistry $registry
     * @return static
     */
    public static function newInstance(ManagerRegistry $registry, ?string $entityClass = ""): static
    {
        return new static($registry, $entityClass);
    }

    /**
     * 获取实体方法名称 parentId  => setParentId;
     * @param string $input
     * @return string
     */
    public static function toCamelCase(string $input): string
    {
        // 分割字符串（支持下划线、短横线分隔的单词）
        $words = preg_split('/[-_]+/', $input);

        // 将每个单词首字母转换为大写（除了第一个单词）
        array_walk($words, function (&$word, $index) {
            if ($index !== 0) {
                $word = ucfirst(strtolower($word));
            }
        });

        // 合并单词，形成小驼峰格式,首字小写
        return implode('', $words);
    }

    /**
     * 构建字段名称
     * @param string $input
     * @return string
     */
    public static function rebuildField(string $input): string
    {
        //如何$filed中有.字符，则直接返回，如果没有则返回t.$field
        $cmInput = self::toCamelCase($input);
        $retField = strpos($input, '.') !== false ? $cmInput : 't.' . $cmInput;
        // Logger::log("rebuildField: $input => $retField");
        return $retField;
    }

    // =========================================================================
    // 工具方法
    // =========================================================================

    public function getEm(): EntityManagerInterface
    {
        return $this->em;
    }

    public function getQueryBuiler(): ?QueryBuilder
    {
        return $this->qb;
    }

    /**
     * 获取实体类属性
     *
     * @param bool $withRelated 是否获取关联属性
     * @return array
     */
    public function getEntityProperties(bool $withRelated = true): array
    {
        $metaData = $this->getClassMetadata();
        $properties = [];
        // 字段属性
        foreach ($metaData->getFieldNames() as $fieldName) {
            array_push($properties, $fieldName);
        }

        // 添加关联属性（如 OneToOne, ManyToOne, OneToMany, ManyToMany）
        if ($withRelated) {
            foreach ($metaData->getAssociationMappings() as $mapping) {
                array_push($properties, $mapping['fieldName']);
            }
        }
        // Logger::log($properties);
        return $properties;
    }

    // =========================================================================
    // 返回 static 的方法 (Fluent Interface)
    // =========================================================================

    /**
     * 初始化查询构造器
     * @return static
     */
    public function init(): static
    {
        $this->qb = $this->createQueryBuilder('t')->resetDQLParts(["where", "set", "join"]);
        $this->whereCounter = 0;
        $this->whereCond = "AND";
        $this->returnArray = false;
        return $this;
    }

    /**
     * 设置锁模式
     * @param LockMode $mode
     * @return static
     */
    public function setLockMode(LockMode $mode): static
    {
        $this->qb->getQuery()->setLockMode($mode);
        return $this;
    }

    /**
     * 设置查询构造器
     * @param QueryBuilder $qb
     * @return static
     */
    public function setQueryBuiler(QueryBuilder $qb): static
    {
        $this->qb = $qb;
        return $this;
    }

    /**
     * @param mixed $args 参数组 $select,$alias,$indexBy
     * @return static
     */
    public function select(mixed ...$args): static
    {
        $this->qb->resetDQLPart('select')->select(...$args);
        return $this;
    }

    /**
     * @param mixed $args 参数组 $join,$alias,$conditionType,$condition,$indexBy
     * @return static
     */
    public function join(...$args): static
    {
        $this->qb->resetDQLPart('join')->join(...$args);
        return $this;
    }

    /**
     * @param mixed $args 参数组 $join,$alias,$conditionType,$condition,$indexBy
     * @return static
     */
    public function leftJoin(mixed ...$args): static
    {
        $this->qb->leftJoin(...$args);
        return $this;
    }

    /**
     * @param mixed $args 参数组 $from,$alias,$indexBy
     * @return static
     */
    public function from(mixed ...$args): static
    {
        $this->qb->from(...$args);
        return $this;
    }

    /**
     * 查询指定字段的不重复值
     * @param string $field 字段名
     * @param array $where 查询条件
     * @return array 不重复的值数组
     */
    public function distinct(string $field, array $where = []): array
    {
        $this->init();
        $rfield = self::rebuildField($field);
        $this->parseWhere($where);

        $result = $this->qb
            ->select("DISTINCT $rfield")
            ->getQuery()
            ->getScalarResult();

        return $result;
    }

    public function orderBy(?array $orderBy): static
    {
        $this->qb->resetDQLPart('orderBy');
        if ($orderBy) {
            foreach ($orderBy as $field => $order) {
                $field = self::rebuildField($field);
                $this->qb->addOrderBy($field, $order);
            }
        }
        return $this;
    }

    /**
     * @param mixed $args 参数组 $where
     * @return static
     */
    public function groupBy(...$args): static
    {
        $this->qb->resetDQLPart('groupBy')->groupBy(...$args);
        return $this;
    }

    public function resetDQLParts(?array $parts = null): static
    {
        $this->qb->resetDQLParts($parts);
        return $this;
    }

    /**
     * 分页
     * @param int|null $limit
     * @param int|null $offset
     * @return static
     */
    public function pagination(?int $limit, ?int $offset): static
    {
        //清空分页参数
        $this->qb->setFirstResult(0)->setMaxResults(null);
        if ($limit !== null) {
            $this->qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $this->qb->setFirstResult($offset);
        }
        return $this;
    }

    /**
     * @param mixed $args 添加select参数
     * @return static
     */
    public function addSelect(...$args): static
    {
        $this->qb->addSelect(...$args);
        return $this;
    }

    /**
     * @param ?int $limit
     * @return static
     */
    public function setMaxResult(?int $limit): static
    {
        $this->qb->setMaxResults($limit);
        return $this;
    }

    public function setWhere(string $where): static
    {
        $where = strtoupper($where);
        if (in_array($where, ["AND", "OR"])) {
            $this->whereCond = $where;
        }
        return $this;
    }

    /**
     * @param array $where ["field"=>"value"],[">="=>""],["NOT_NULL"=>true],["BETWEEN"=>["start","end"]],["OR"=>["A","B"]]
     * @return static
     */
    public function parseWhere(array $where): static
    {
        if (empty($where)) return $this;
        // 处理模糊搜索条件
        foreach ($where as $field => $expr) {
            $paramName = sprintf("value%d", $this->whereCounter);
            $start = sprintf("start%d",  $this->whereCounter);
            $end = sprintf("end%d",  $this->whereCounter);
            //判断$where的数组的结构，只支持key=>value的形式, 如果value为数组，则认为是非精确查询条件，默认为数组第1个元素为操作符，数组第2个元素为匹配条件
            $rfield = self::rebuildField($field);
            if (is_array($expr)) {
                // list($op, $condition) = $expr;
                $op = strtoupper(key($expr));
                $condition = current($expr);
                if (!in_array($op, $this->expr)) {
                    //如果操作符不在$this->expr数组中，则默认为精确查询
                    $this->setQbWhere("$rfield = :$paramName")->setParameter($paramName, $condition);
                } else {
                    switch ($op) {
                        case "EMPTY":
                            //$rfield 为0或为null
                            $this->setQbWhere($this->qb->expr()->orX($this->qb->expr()->eq("$rfield", 0), $this->qb->expr()->isNull("$rfield")));
                            break;
                        case "NULL":
                            $this->setQbWhere($this->qb->expr()->isNull("$rfield"));
                            break;
                        case "NOT_NULL":
                            $this->setQbWhere($this->qb->expr()->isNotNull("$rfield"));
                            break;
                        case "LIKE":
                            $rfields = explode("|", $rfield); //支持多个字段值like查询 "name|title"=>['like','value']
                            $orX = $this->qb->expr()->orX();
                            foreach ($rfields as $rfield) {
                                $rfield = self::rebuildField($rfield);
                                $orX->add($this->qb->expr()->like("$rfield", ":$paramName"));
                            }
                            $this->setQbWhere($orX)->setParameter($paramName, "%" . $condition . "%");
                            break;
                        case "NOT_LIKE":
                            $this->setQbWhere($this->qb->expr()->notLike("$rfield", ":$paramName"))
                                ->setParameter($paramName, "%" . $condition . "%");
                            break;
                        case "IN":
                            $this->setQbWhere($this->qb->expr()->in("$rfield", ":$paramName"))
                                ->setParameter($paramName, $condition);
                            break;
                        case "NOT_IN":
                            $this->setQbWhere($this->qb->expr()->notIn("$rfield", ":$paramName"))
                                ->setParameter($paramName, $condition);
                            break;
                        case "FIND_IN":
                            $this->setQbWhere("FIND_IN_SET(:$paramName, $rfield)")
                                ->setParameter($paramName, $condition);
                            break;
                        case "BETWEEN":
                            $subQb = $this->qb->expr()->between("$rfield", ":$start", ":$end");
                            $this->setQbWhere($subQb)->setParameter($start, $condition[0])->setParameter($end, $condition[1]);
                            break;
                        case "NOT_BETWEEN":
                            $subQb = $this->qb->expr()->orX($this->qb->expr()->lt("$rfield", ":$start"), $this->qb->expr()->gt("$rfield", ":$end"));
                            $this->setQbWhere($subQb)->setParameter("$start", $condition[0])->setParameter($end, $condition[1]);
                            break;
                        case "OR":
                            // $condition为数组,循环添加or操作
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
                    $orX = $this->qb->expr()->orX();
                    foreach ($sfields as $sfield) {
                        $sfield = self::rebuildField($sfield);
                        $orX->add($this->qb->expr()->eq("$sfield", ":$paramName"));
                    }
                    $this->setQbWhere($orX)->setParameter($paramName, $expr);
                } else {
                    $this->setQbWhere("$rfield = :$paramName")->setParameter($paramName, $expr);
                }
            }
            $this->whereCounter++;
        }
        return $this;
    }

    public function whereOr(array $where): static
    {
        $this->whereCond = "OR";
        return $this->parseWhere($where);
    }

    public function whereAnd(array $where): static
    {
        $this->whereCond = "AND";
        return $this->parseWhere($where);
    }

    public function debug(bool $sql = true): static
    {
        $this->debug = true;
        $this->isLogSql = $sql;
        return $this;
    }

    /**
     * 不对查询结果进行实例化，而是直接返回数组,查询效率至少提升5倍
     */
    public function retArray(): static
    {
        $this->returnArray = true;
        return $this;
    }

    private function logSql(?QueryBuilder $qb = null): static
    {
        if (!$qb) {
            $qb = $this->qb;
        }
        if ($this->debug) {
            if ($this->isLogSql) {
                Logger::log($this->qb->getQuery()->getSQL());
            } else {
                Logger::log($this->qb->getQuery()->getDQL());
            }
        }
        return $this;
    }

    private function setQbWhere(mixed $argv): ?QueryBuilder
    {
        if ($this->whereCond === "AND") {
            $this->qb->andWhere($argv);
        }
        if ($this->whereCond === "OR") {
            $this->qb->orWhere($argv);
        }
        return $this->qb;
    }

    // =========================================================================
    // 返回 array 的方法
    // =========================================================================

    /**
     * @param array $where
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return array ["total" => 0, 'items' => []]
     */
    public function search($where = [], $orderBy = null, $limit = null, $offset = null): array
    {
        $this->parseWhere($where)->orderBy($orderBy);
        //在未添加分页参数前复制qb对象以获取总数；
        // 获取总数
        $totalCount = $this->getCount(true);
        // 获取分页后的数据
        $result = $this->pagination($limit, $offset)->getResult();
        return ["total" => $totalCount, 'items' => $result];
    }

    /**
     * 列表查询
     * @param array $filter
     * @param array $names
     * @param array $order
     * @return array [total,list]
     */
    public function page(array $filter, array $names = [], array $order = ['id' => 'DESC']): array
    {
        $page = $filter['pageNum'] ?? 1;
        $limit = $filter['pageSize'] ?? 20;
        $offset = ($page - 1) * $limit;
        unset($filter['pageNum'], $filter['pageSize']);
        $data = $this->search($filter, $order, $limit, $offset);
        $list = [];
        if (!empty($names)) {
            $this->returnArray = false;
        }
        foreach ($data["items"] as &$entity) {
            $list[] = $this->returnArray ? $entity : $entity->toArray($names);
        }
        return [
            'total' => $data['total'],
            'list' => $list
        ];
    }

    /**
     * 列表查询
     * @param array $filter
     * @param array $order
     * @return array [total,list]
     */
    public function entityPage(array $filter, array $order = ['id' => 'DESC']): array
    {
        $page = $filter['pageNum'] ?? 1;
        $limit = $filter['pageSize'] ?? 20;
        $offset = ($page - 1) * $limit;
        unset($filter['pageNum'], $filter['pageSize']);
        $result = $this->search($filter, $order, $limit, $offset);
        return [
            'total' => $result['total'],
            'list' => $result["items"]
        ];
    }

    /**
     * 列表查询
     * @param array $filter
     * @param array $names 附加返回字段
     * @param array $order
     * @return array 实体转为数组后的数组集合
     */
    public function list(array $filter = [], array $names = [], array $order = ['id' => 'DESC']): array
    {
        // 如果有分页查询参数，则进行 分页查询
        if (isset($filter['pageNum']) && isset($filter['pageSize'])) {
            $page = $filter['pageNum'] ?? 1;
            $limit = $filter['pageSize'] ?? 20;
            $offset = ($page - 1) * $limit;
            unset($filter['pageNum'], $filter['pageSize']);
            $data = $this->search($filter, $order, $limit, $offset);
        } else {
            $data = $this->search($filter, $order);
        }
        if (!empty($names)) {
            $this->returnArray = false;
        }
        $list = [];
        foreach ($data["items"] as &$entity) {
            $list[] = $this->returnArray ? $entity : $entity->toArray($names);
        }
        return $list;
    }

    /**
     * @param array $kv ["id", "name"], "value"=>key[0],"label"=>key[1],"meta"=>keys[2...]
     * @param array $where
     * @param array|null $orderBy
     * @return array
     */
    public function getOptionList($kv = ["id", "name"], $where = [], $orderBy = null): ?array
    {
        $this->parseWhere($where)->orderBy($orderBy);
        // 如果$kv是字符串，则转换为数组
        if (is_string($kv)) {
            $kv = explode(",", $kv);
        }
        if (count($kv) < 2) {
            @Logger::error("$kv is uncompliance");
            throw new Exception("$kv is uncompliance");
        }
        $result = $this->getArrayResult();
        $items = [];
        $metaKey = array_slice($kv, 2);
        foreach ($result as $key => $value) {
            $items[$key]['value'] = $value[$kv[0]];
            $items[$key]['label'] = $value[$kv[1]];
            $meta = [];
            if (count($metaKey) > 0) {
                foreach ($metaKey as $metaKeyItem) {
                    if (array_key_exists($metaKeyItem, $value)) {
                        $meta[$metaKeyItem] = $value[$metaKeyItem];
                    } else {
                        Logger::log("$metaKeyItem is not exists");
                    }
                }
            }
            $items[$key]['meta'] = $meta;
        }
        return $items;
    }

    // =========================================================================
    // 返回 object / mixed 的方法
    // =========================================================================

    /** @return T */
    public function getEntity(): object
    {
        $entity = null;
        $reflectionClass = new ReflectionClass($this->entityClass);
        if ($reflectionClass->isInstantiable()) {
            $entity = $reflectionClass->newInstance();
        } else {
            Logger::log("$this->entityClass is not instantiable");
            throw new Exception("$this->entityClass is not instantiable");
        }
        return $entity;
    }

    /**
     * 创建实体
     * @param array $data
     * @param bool $flush
     * @return T
     */
    public function createEntity(array $data, bool $flush = false): ?object
    {
        $entity = $this->getEntity();
        // 添加空值检查
        if (!$entity) {
            throw new \RuntimeException('Failed to create entity instance');
        }
        foreach ($data as $key => $value) {
            if (!$key) continue;
            $setter = self::toCamelCase('set' . ucfirst($key));
            $methodName = self::toCamelCase($key);
            if (method_exists($entity, $setter)) {
                $entity->$setter($value);
            }
            if (method_exists($entity, $methodName)) {
                $entity->$methodName($value);
            }
        }
        if ($flush) {
            return $this->flush($entity);
        }
        return $entity;
    }

    /**
     * 复制实体
     * @param object $sourceEntity
     * @param array $newProps
     * @param array $ignoreProps
     * @return T
     */
    public function cpEntity(object $sourceEntity, array $newProps = [], array $ignoreProps = []): ?object
    {
        $entity = $this->getEntity();
        // 添加空值检查
        if (!$entity || !$sourceEntity) {
            throw new \InvalidArgumentException('Source entity and target entity cannot be null');
        }
        // 复制属性,除开id和createTime,updateTime等属性
        $props = $this->getEntityProperties(false);
        $ignoreProps = $ignoreProps + ["id", "createTime", "updateTime"];
        foreach ($props as $prop) {
            if (!$prop) continue;
            if (in_array($prop, $ignoreProps)) {
                continue;
            }
            $setter = self::toCamelCase('set' . ucfirst($prop));
            $getter = self::toCamelCase('get' . ucfirst($prop));
            if (method_exists($entity, $setter) && method_exists($sourceEntity, $getter)) {
                $entity = $entity->$setter($sourceEntity->$getter());
                // Logger::log("$prop=" . $getter);
            }
        }

        $entity = $this->updateEntity($entity, $newProps);
        return $entity;
    }

    /** 更新单个实体，返回更新后的实体
     * @param object $entity
     * @param array $data
     * @return T
     */
    public function updateEntity($entity, array $data, bool $flush = false): ?object
    {
        if (empty($data)) return $entity;
        // 添加空值检查
        if (!$entity || !is_object($entity)) {
            throw new \InvalidArgumentException('Entity must be a valid object');
        }
        foreach ($data as $key => $value) {
            if (!$key) continue;
            //如果存在该方法，则调用方法
            $setter = self::toCamelCase('set' . ucfirst($key));
            $methodName = self::toCamelCase($key);
            if (method_exists($entity, $setter)) {
                $entity->$setter($value);
            }
            if (method_exists($entity, $methodName)) {
                $entity->$methodName($value);
            }
        }
        if ($flush) {
            return $this->flush($entity);
        }
        return $entity;
    }

    public function getResult(): mixed
    {
        return $this->returnArray ? $this->getArrayResult() : $this->logSql()->qb->getQuery()->getResult();
    }

    public function getArrayResult(): array
    {
        return $this->logSql()->qb->getQuery()->getArrayResult();
    }

    public function getFirst()
    {
        $result = $this->getResult();
        return $result[0] ?? null;
    }

    public function getLatest(array $filter, array $orderBy = ["id" => "DESC"]): mixed
    {
        if ($this->debug) Logger::log($this->parseWhere($filter)->qb->setMaxResults(1)->getQuery()->getDQL());
        return $this->orderBy($orderBy)->parseWhere($filter)->qb->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }

    /**
     * @param array $where
     * @param array|null $orderBy
     * @return mixed
     */
    public function findEntities(array $where = [], $orderBy = null): mixed
    {

        try {
            $this->parseWhere($where)->orderBy($orderBy);
            return $this->getResult();
        } catch (\Exception $e) {
            Logger::critical($e->getMessage());
            return null;
        }
    }

    /**
     * @param array $filter
     * @param array $props
     * @return ?T
     */
    public function findOrCreate(array $filter, array $props = []): ?object
    {
        $entity = $this->findOneBy($filter);
        if ($entity) {
            return $this->updateEntity($entity, $props, true);
        } else {
            return $this->createEntity($filter + $props, true);
        }
    }

    public function getCount(bool $clone = false)
    {
        $countQb = $clone ? clone $this->qb : $this->qb;
        $countQb->setFirstResult(0)->setMaxResults(null);
        $countQuery = $countQb->select('COUNT(t)')->getQuery();
        $this->logSql($countQb);
        try {
            return $countQuery->getSingleScalarResult();
        } catch (Exception $e) {
            Logger::critical($e->getMessage());
            return 0;
        }
    }

    // =========================================================================
    // CRUD 操作方法
    // =========================================================================

    /**
     * 新增实体
     * @param array $data
     * @return ?T
     */
    public function create(array $data): ?object
    {
        $this->em->beginTransaction();
        try {
            $newEntity = $this->createEntity($data);
            if ($newEntity) {
                $this->em->persist($newEntity);
                $this->em->flush();
                $this->em->commit();
                return $newEntity;
            }
            Logger::error("create $this->entityClass entity failed");
            return null;
        } catch (\Exception $e) {
            $this->em->rollback();
            @Logger::error($e->getMessage());
            return null;
        }
    }

    /**
     * 批量新增实体,成功返回创建的实体集合，失败返回false
     * @param array $entityData
     * @return ?Collection 创建后的实体集合或null
     */
    public function batchCreate(array $entityData): ?ArrayCollection
    {
        try {
            $retEntities = new ArrayCollection();
            $this->em->beginTransaction();
            foreach ($entityData as $value) {
                $newEntity = $this->createEntity($value);
                if ($newEntity) {
                    $this->em->persist($newEntity);
                    $retEntities->add($newEntity);
                }
            }
            $this->em->flush();
            $this->em->commit();
            return $retEntities;
        } catch (\Exception $e) {
            $this->em->rollback();
            @Logger::error($e->getMessage());
            return null;
        }
    }

    /**
     * 更新实体
     * @param array $filter 筛选条件,key，array或entity;
     * @param array $data
     * @return ?T
     */
    public function update($filter, array $data): ?object
    {
        // 获取更新实体
        // $filter为字符表示根据主键id查询获取实体
        if (is_array($filter)) {
            // Logger::log("filter is array");
            $existEntity = $this->findOneBy($filter);
        } elseif ($filter instanceof $this->entityClass) {
            $existEntity = $filter;
        } else {
            $existEntity = $this->find($filter);
        }
        if (!$existEntity) {
            Logger::log("filter is $filter");
            return null;
        }
        try {
            $this->em->beginTransaction();
            $updatedEntity = $this->updateEntity($existEntity, $data);
            $this->em->persist($updatedEntity);
            $this->em->flush();
            $this->em->commit();
            return $updatedEntity;
        } catch (\Exception $e) {
            $this->em->rollback();
            @Logger::error($e->getMessage());
            return null;
        }
    }

    /**
     * 批量更新实体,成功则返回更新后的实体，否则返回false
     * @param mixed $entities 要更新的实体集合
     * @param array $updateValues 更新值数组
     * @return mixed 更新后的实体集合或null
     */
    public function batchUpdate(mixed $entities, array $updateValues = []): mixed
    {
        if (empty($entities)) return $entities;
        try {
            $this->em->beginTransaction();
            foreach ($entities as &$entity) {
                if ($updateValues) {
                    $entity = $this->updateEntity($entity, $updateValues);
                }
                $this->em->persist($entity);
            }
            $this->em->flush();
            $this->em->commit();
            return $entities;
        } catch (\Exception $e) {
            $this->em->rollback();
            @Logger::error($e->getMessage());
            return null;
        }
    }

    /**
     * 删除实体
     * @param array $ids
     * @return bool
     */
    public function delete(array $ids): bool
    {
        if (!$ids) return false;
        try {
            $this->em->beginTransaction();
            foreach ($ids as $id) {
                $entity = $this->find($id);
                if ($entity) {
                    $this->em->remove($entity);
                };
            }
            $this->em->flush();
            $this->em->commit(); //展示站点不提交事务
            return true;
        } catch (\Exception $e) {
            $this->em->rollback();
            @Logger::error($e->getMessage());
            $this->logSql();
            return false;
        }
    }

    /**
     * 删除实体
     * @param object $entity
     * @return bool
     */
    public function transactionRemove($entity): bool
    {
        try {
            $this->em->beginTransaction();
            $this->em->remove($entity);
            $this->em->flush();
            $this->em->commit();
            return true;
        } catch (\Exception $e) {
            $this->em->rollback();
            @Logger::error($e->getMessage());
            return false;
        }
    }

    /**
     * flush实体至数据库
     * @param mixed $entities 实体集合或单个实体
     * @return mixed 成功返回实体集合或单个实体，失败返回null
     * */
    public function flush(mixed $entities): mixed
    {
        // 检查 EntityManager 是否关闭，关闭则重新获取
        if (!$this->em->isOpen()) {
            Logger::log("em is closed, reopen it.");
            $this->em = $this->getEntityManager();
            return null;
        }
        if (!$entities) {
            Logger::error("flush entities is empty");
            return null;
        };
        $this->em->beginTransaction();
        try {
            if (is_countable($entities)) {
                foreach ($entities as &$entity) {
                    $this->em->persist($entity);
                }
            } else {
                $this->em->persist($entities);
            }
            $this->em->flush();
            $this->em->commit();
            return $entities;
        } catch (\Exception $e) {
            $this->em->rollback();
            @Logger::error($e->getMessage());
            return null;
        }
    }
}
