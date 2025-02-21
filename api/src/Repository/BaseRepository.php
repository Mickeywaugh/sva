<?php

namespace App\Repository;

use App\Service\BaseService as Util;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use ReflectionClass;

abstract class BaseRepository extends ServiceEntityRepository
{

    private $qb;
    private $whereCond = "AND";
    private $entityClass;
    protected $em;
    protected $debug = false;
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
        'FIND_IN'
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, static::getEntityClass());
        $this->qb = $this->createQueryBuilder('t');
        $this->entityClass = static::getEntityClass();
        $this->debug = $_ENV['APP_DEBUG'];
        $this->resetWhere();
        $this->em = $this->getEntityManager();
    }

    /**
     * 抽象方法，子类需实现并返回实体类名
     *
     * @return string
     */
    abstract protected static function getEntityClass(): string;

    public function getEm()
    {
        return $this->em;
    }


    /**
     * 获取实体类属性
     *
     * @param bool $withRelated 是否获取关联属性
     * @return array
     */
    public function getEntityProperties(bool $withRelated = true): array
    {
        $metaData = $this->getClassMetadata($this->entityClass);
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
        // Util::log($properties);
        return $properties;
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

        // 合并单词，形成小驼峰格式
        return implode('', $words);
    }

    /**
     * 驼峰转下划线 ParentId => parent_id
     * @param string ParentId
     * @return string parent_id
     */

    public static function convertToSnakeCase(string $input): string
    {
        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $input));
    }

    public function getEntity()
    {
        $entity = null;
        $reflectionClass = new ReflectionClass($this->entityClass);
        if ($reflectionClass->isInstantiable()) {
            $entity = $reflectionClass->newInstance();
        } else {
            Util::log("$this->entityClass is not instantiable");
            throw new Exception("$this->entityClass is not instantiable");
        }
        return $entity;
    }

    public function createEntity(array $data, bool $flush = false)
    {
        $entity = $this->getEntity();
        foreach ($data as $key => $value) {
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

    /** 更新单个实体，返回更新后的实体
     * @param object $entity
     * @param array $data
     * @return object
     */
    public function updateEntity($entity, array $data)
    {
        foreach ($data as $key => $value) {
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
        return $entity;
    }

    public function getQueryBuiler()
    {
        return $this->qb;
    }

    public function select(...$args): static
    {
        $this->qb->select(...$args);
        return $this;
    }

    public function join(...$args)
    {
        $this->qb->join(...$args);
        return $this;
    }

    public function leftJoin(...$args): static
    {
        $this->qb->leftJoin(...$args);
        return $this;
    }

    public function from(...$args): static
    {
        $this->qb->from(...$args);
        return $this;
    }

    public function orderBy($orderBy): static
    {
        if ($orderBy) {
            foreach ($orderBy as $field => $order) {
                $this->qb->addOrderBy('t.' . $field, $order);
            }
        }
        return $this;
    }

    public function pagination($limit, $offset): static
    {
        if ($limit !== null) {
            $this->qb->setMaxResults($limit);
        }
        if ($offset !== null) {
            $this->qb->setFirstResult($offset);
        }
        return $this;
    }

    public function addSelect(...$args): static
    {
        $this->qb->addSelect(...$args);
        return $this;
    }

    /**
     * @param array $where
     * @param array|null $orderBy
     * @param int|null $limit
     * @param int|null $offset
     * @return array ["total" => 0, 'items' => []]
     */
    public function search($where = [], array $orderBy = null, $limit = null, $offset = null)
    {
        $this->parseWhere($where);  // append where array conditions to $qb;
        $this->orderBy($orderBy);
        //在未添加分页参数前复制qb对象以获取总数；
        // 获取总数
        $totalCount = $this->getCount();
        // 获取分页后的数据
        $result = $this->pagination($limit, $offset)->getResult();
        return ["total" => $totalCount, 'items' => $result];
    }

    public function findEntities($where = [], array $orderBy = null)
    {
        $this->parseWhere($where);  // append where array conditions to $qb;
        $this->orderBy($orderBy);
        return $this->getResult();
    }
    /**
     * @param array $kv ["id", "name"], "value"=>key[0],"label"=>key[1],"meta"=>keys[2...]
     * @param array $where
     * @param array|null $orderBy
     * @return array
     */
    public function getOptionList($kv = ["id", "name"], $where = [], array $orderBy = null): ?array
    {
        $this->parseWhere($where);  // append where array conditions to $qb;
        $this->orderBy($orderBy);
        // 如果$kv是字符串，则转换为数组
        if (is_string($kv)) {
            $kv = explode(",", $kv);
        }
        if (count($kv) < 2) {
            @Util::log("$kv is uncompliance");
            throw new Exception("$kv is uncompliance");
        }
        $result = $this->getArrayResult();
        $retArray = [];
        $metaKey = array_slice($kv, 2);
        foreach ($result as $key => $value) {
            $retArray[$key]['value'] = $value[$kv[0]];
            $retArray[$key]['label'] = $value[$kv[1]];
            $meta = [];
            if (count($metaKey) > 0) {
                foreach ($metaKey as $metaKeyItem) {
                    if (array_key_exists($metaKeyItem, $value)) {
                        $meta[$metaKeyItem] = $value[$metaKeyItem];
                    } else {
                        Util::log("$metaKeyItem is not exists");
                    }
                }
            }
            $retArray[$key]['meta'] = $meta;
        }
        return $retArray;
    }

    public function getResult()
    {
        if ($this->debug) {
            Util::log($this->qb->getQuery()->getDQL());
        }
        return $this->qb->getQuery()->getResult();
    }

    public function getArrayResult()
    {
        if ($this->debug) {
            Util::log($this->qb->getQuery()->getDQL());
        }
        return $this->qb->getQuery()->getArrayResult();
    }

    public function getLatest(array $filter)
    {
        if ($this->debug) Util::log($this->parseWhere($filter)->qb->setMaxResults(1)->getQuery()->getDQL());
        return $this->parseWhere($filter)->qb->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }

    public function findOrCreate(array $filter)
    {
        $entity = $this->findOneBy($filter);
        if ($entity) {
            return $entity;
        } else {
            return $this->createEntity($filter);
        }
    }

    public function getCount()
    {
        $countQb = clone $this->qb;
        return  $countQb->select('COUNT(t)')->getQuery()->getSingleScalarResult();
    }

    public function setWhereOr(): static
    {
        $this->whereCond = "OR";
        return $this;
    }

    public function setWhereAnd(): static
    {
        $this->whereCond = "AND";
        return $this;
    }

    public function resetWhere(): static
    {
        $this->qb->resetDQLPart("where");
        return $this;
    }

    public function parseWhere($where): static
    {
        if (empty($where)) return $this;
        $cond = $this->whereCond;
        $properties = $this->getEntityProperties();
        // 处理模糊搜索条件
        foreach ($where as $key => $expr) {
            //判断$where的数组的结构，支持[key=>val,[key,op,val]]结构
            if (is_int($key) && is_array($expr)) {
                // 数组元素为数组时，为模糊搜索条件
                $argc = count($expr);
                // 数组元素个数为1时，
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
                    // $field = self::convertToSnakeCase($field);
                    // if (!in_array($field, $properties)) continue;
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
                        if ($opName == "BETWEEN") { //包括start和end值
                            $subQb = $this->qb->expr()->between("t.$field", ":start", ":end");
                        } else {
                            $subQb = $this->qb->expr()->orX($this->qb->expr()->lt("t.$field", ':start'), $this->qb->expr()->gt("t.$field", ':end'));
                        }

                        $this->andOrWhere($cond, $subQb)
                            ->setParameter('start', $value[0])
                            ->setParameter('end', $value[1]);
                    } else {
                        switch ($opName) {
                            case "LIKE":
                                $this->andOrWhere($cond, $this->qb->expr()->like("t.$field", ':value'))
                                    ->setParameter('value', "%" . $value . "%");
                                break;

                            case "NOT_LIKE":
                                $this->andOrWhere($cond, $this->qb->expr()->notLike("t.$field", ':value'))
                                    ->setParameter('value', "%" . $value . "%");
                                break;

                            case "IN":
                                $this->andOrWhere($cond, $this->qb->expr()->in("t.$field", ':range'))
                                    ->setParameter('range', $value);
                                break;

                            case "NOT_IN":
                                $this->andOrWhere($cond, $this->qb->expr()->notIn("t.$field", ':range'))
                                    ->setParameter('range', $value);
                                break;
                            case "FIND_IN":
                                $this->andOrWhere($cond, "FIND_IN_SET(:value, t.$field)")
                                    ->setParameter('value', $value);
                            default:
                                $this->andOrWhere($cond, "t.$field $opName :$field")->setParameter($field, $value);
                                break;
                        }
                    }
                }
            } else {
                // $where=["key"=>"value"] 结构
                // 数组元素不为数组时，默认使用精确查询
                // 字段不在实体属性中，忽略
                // $key = $this->convertToSnakeCase($key);
                // if (!in_array($key, $properties)) continue;
                // 如何搜索字符串中有%，则使用主动使用模糊搜索
                if (is_string($expr) && strpos($expr, '%') !== false) {
                    $this->qb->orWhere($this->qb->expr()->like("t.$key", ':keyword'))
                        ->setParameter('keyword', $expr);
                    continue;
                } else {
                    $this->andOrWhere($cond, "t.$key = :$key")->setParameter($key, $expr);
                }
            }
            // Util::log($this->qb->getDQL());
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

    /**
     * 列表查询
     * @param array $filter
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
        foreach ($data["items"] as &$entity) {
            $list[] = $entity->toArray($names);
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
     * @param array $order
     * @param array $names 附加返回字段
     * @return array 实体转为数组后的数组集合
     */
    public function list(array $filter = [],  array $names = [], array $order = ['id' => 'DESC'],): array
    {
        $data = $this->search($filter, $order);
        $list = [];
        foreach ($data["items"] as &$entity) {
            $list[] = $entity->toArray($names);
        }
        return $list;
    }

    // 新增实体
    public function create(array $data)
    {
        $this->em->beginTransaction();
        try {
            $newEntity = $this->createEntity($data);
            if ($newEntity) {
                $this->em->persist($newEntity);
                $this->em->flush();
                $this->em->commit();
                return $newEntity;
            } else {
                throw new Exception("Failed to create entity:" . $this->entityClass);
            }
        } catch (\Exception $e) {
            $this->em->rollback();
            @Util::log($e->getMessage(), "error");
            return false;
        }
    }

    /**批量新增实体,成功返回创建的实体数，失败返回false
     * @param array $entityData
     * @return ArrayCollection|bool
     */
    public function batchCreate(array $entityData): ArrayCollection |bool
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
            @Util::log($e->getMessage(), "error");
            return false;
        }
    }

    /** 批量更新实体,成功则返回更新后的实体，否则返回false
     * @param PersistentCollection $entities
     * @param array $updateValues
     * @return PersistentCollection|bool
     */
    public function batchUpdate($entities, array $updateValues = [])
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
            @Util::log($e->getMessage(), "error");
            return false;
        }
    }

    /**
     * 更新实体
     * @param array $filter 筛选条件,key，array或entity;
     * @param array $data
     * @return bool|object
     */
    public function update($filter, array $data)
    {
        // 获取更新实体
        // $filter为字符表示根据主键id查询获取实体
        if (is_array($filter)) {
            // Util::log("filter is array");
            $existEntity = $this->findOneBy($filter);
        } elseif ($filter instanceof $this->entityClass) {
            $existEntity = $filter;
        } else {
            $existEntity = $this->find($filter);
        }
        if (!$existEntity) {
            Util::log("filter is $filter");
            return false;
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
            @Util::log($e->getMessage(), "error");
            return false;
        }
    }

    /**
     * 删除实体
     * @param array $ids
     * @return bool
     */
    public function delete(array $ids)
    {
        if (!$ids) return false;
        try {
            $this->em->beginTransaction();
            $deleteIds = [];
            foreach ($ids as $id) {
                $entity = $this->find($id);
                if ($entity) {
                    $this->em->remove($entity);
                    $deleteIds[] = $id;
                };
            }
            $this->em->flush();
            $this->em->commit();
            return $deleteIds;
        } catch (\Exception $e) {
            $this->em->rollback();
            @Util::log($e->getMessage());
            return false;
        }
    }

    /**
     * 删除实体
     * @param object $entity
     * @return bool
     */
    public function remove($entity)
    {
        try {
            $this->em->beginTransaction();
            $this->em->remove($entity);
            $this->em->flush();
            $this->em->commit();
            return true;
        } catch (\Exception $e) {
            $this->em->rollback();
            @Util::log($e->getMessage());
            return false;
        }
    }

    // flush实体至数据库
    /**
     * @param entity $entity[] //待保存
     * @return entity $entity //保存后的实体
     */
    public function flush($entities)
    {
        if (!$this->em->isOpen()) {
            Util::log("em is closed");
        }
        $this->em->beginTransaction();
        try {
            if (is_object($entities) || !is_array($entities)) {
                $this->em->persist($entities);
            } else {
                foreach ($entities as &$entity) {
                    $this->em->persist($entity);
                }
            }
            $this->em->flush();
            $this->em->commit();
            return $entities;
        } catch (\Exception $e) {

            if ($this->em->isOpen()) {
                $this->em->clear();
                $this->em->rollback();
            }

            @Util::log($e->getMessage());
            // @Util::log(var_export($entity, true));
            return false;
        }
    }
}
