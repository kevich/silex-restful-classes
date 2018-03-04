<?php

namespace APG\SilexRESTful;

use APG\SilexRESTful\Interfaces\Service;
use APG\SilexRESTful\Interfaces\Model;
use Doctrine\DBAL\Types\Type;
use Worker\Model as WorkerModel;

class ServiceDefault implements Service
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $db;

    /**
     * @var string
     */
    protected $table_name;

    /**
     * @var array
     */
    protected $filters;

    /**
     * @var array
     */
    protected $sorters;

    /**
     * @var WorkerModel
     */
    protected $user;

    /**
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->sorters = array(
            (object)array('property' => 'id', 'direction' => 'ASC')
        );
    }

    /**
     * @param string $table_name
     * @return ServiceDefault
     */
    public function setTableName($table_name)
    {
        $this->table_name = $table_name;
        return $this;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->table_name;
    }

    /**
     * @param array $filters
     * @return null|void
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;
    }

    /**
     * @param WorkerModel $user
     * @return null|void
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @param array $sorters
     */
    public function setSorters($sorters)
    {
        $this->sorters = $sorters;
    }

    /**
     * @param Model $object
     * @return int
     */
    public function saveObject($object)
    {
        $data = get_object_vars($object);
        $status = $this->db->insert($this->table_name, $data, $this->prepareTypes($data));
        $object->setId($this->db->lastInsertId($this->table_name . "_id_seq"));
        return $status;
    }

    /**
     * @param Model $object
     * @return int
     */
    public function updateObject($object)
    {
        $data = get_object_vars($object);
        return $this->db->update($this->table_name, $data, array('id' => $object->getId()), $this->prepareTypes($data));
    }

    /**
     * @param null|int $start
     * @param null|int $limit
     * @return array
     */
    public function getAllAssoc($start = null, $limit = null)
    {
        $qb = $this->applyFilters(
            $this->applySorters(
                $this->db->createQueryBuilder()
                    ->select($this->get_fields())
                    ->from($this->table_name, '')
            )
        );
        if ($limit != null) {
            $qb->setMaxResults($limit);
        }
        if ($start != null) {
            $qb->setFirstResult($start);
        }
        return $this->table_name
            ? $this->db->fetchAll(
                $qb->getSQL(),
                $qb->getParameters(),
                $qb->getParameterTypes()
            )
            : array();
    }

    public function getTotalCount()
    {
        $qb = $this->applyFilters(
            $this->db->createQueryBuilder()
                ->select('COUNT(1)')
                ->from($this->table_name, '')
        );
        return $this->table_name ?
            $this->db->fetchColumn(
                $qb->getSQL(),
                $qb->getParameters(),
                0,
                $qb->getParameterTypes()
            )
            : 0;
    }

    /**
     * @param int $id
     * @return array
     */
    public function getByIdAssoc($id)
    {
        $sql = 'SELECT ' . $this->get_fields() . ' FROM ' . $this->table_name . ' WHERE id = ?';
        return $this->db->fetchAssoc($sql, array($id));
    }

    /**
     * @param $id
     * @return Model
     */
    public function getById($id)
    {
        $array = $this->getByIdAssoc($id);
        if (!$array) {
            return false;
        }
        $class_name = Helpers::to_camel_case($this->table_name) . '\Model';
        $object = class_exists($class_name) ? new $class_name() : new ModelDummy($this->table_name);
        $object->fillFromArray($array);
        return $object;
    }

    /**
     * @param int $id
     * @return boolean
     */
    public function deleteById($id)
    {
        return (boolean)$this->db->delete($this->table_name, array('id' => $id));

    }

    /**
     * @return string
     */
    protected function get_fields()
    {
        $class_name = Helpers::to_camel_case($this->table_name) . '\Model';
        return class_exists($class_name) ? implode(',', array_merge(array('id'), array_keys(get_class_vars($class_name)))) : '*';
    }

    protected function prepareTypes($values)
    {
        $types = array();
        foreach ($values as $key => $value) {
            if (!is_null($value)) {
                $types[$key] = Type::getType(gettype($value));
            }
        }
        return $types;
    }

    /**
     * @param \Doctrine\DBAL\Query\QueryBuilder $select
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function applyFilters($select)
    {
        $where = 'TRUE';
        if (count($this->filters) > 0) {
            foreach ($this->filters as $filter) {
                $field = false;
                if (property_exists($filter, 'field')) $field = $filter->field;
                if (property_exists($filter, 'property')) $field = $filter->property;
                if (!property_exists($filter, 'type') && property_exists($filter, 'value')) $filter->type = gettype($filter->value);
                if ($field && property_exists($filter, 'value')) {
                    switch ($filter->type) {
                        case 'integer':
                            $select->andWhere(
                                $select->expr()->eq(
                                    $this->db->quoteIdentifier($field),
                                    $select->createPositionalParameter(intval($filter->value), Type::INTEGER)
                                )
                            );
                            break;
                        case 'list':
                            $select->andWhere(
                                $select->expr()->in(
                                    $this->db->quoteIdentifier($field),
                                    array_map([$this->db, 'quote'], $filter->value)
                                )
                            );
                            break;
                        /** [{"type":"boolean","value":true,"field":"have_unread_comments"}] */
                        case 'boolean':
                            $select->andWhere(
                                $select->expr()->eq(
                                    $this->db->quoteIdentifier($field),
                                    $select->createPositionalParameter(boolval($filter->value), Type::BOOLEAN)
                                )
                            );
                            break;
                        case 'date':
                            $select->andWhere(
                                $select->expr()->eq(
                                    $this->db->quoteIdentifier($field),
                                    $this->db->quote((new \DateTime($filter->value))->format('Y-m-d')) . '::DATE'
                                )
                            );
                            break;
                        case 'string':
                            $select->andWhere(
                                $select->expr()->comparison(
                                    $this->db->quoteIdentifier($field),
                                    '~*',
                                    preg_quote($this->db->quote($filter->value))
                                )
                            );
                            break;
                        case 'pg_array':
                            $select->andWhere(
                                "(" . implode(' OR ', array_map(function ($value) use ($field) {
                                    $value = gettype($value) == 'string' ? $this->db->quote($value) : intval($value);
                                    return "$value = ANY($field)";
                                }, is_array($filter->value) ? $filter->value : array($filter->value))) . ")"
                            );
                            break;
                        case 'array':
                            $select->andWhere(
                                $select->expr()->eq(
                                    $this->db->quoteIdentifier($field),
                                    "ANY([" . implode(",", array_map([$this->db, 'quote'], $filter->value)) . "])"
                                )
                            );
                            break;
                        case 'foreingKey':
                            $select->andWhere(
                                $select->expr()->eq(
                                    $this->db->quoteIdentifier($field),
                                    $this->db->quote($filter->value)
                                )
                            );
                    }
                }
            }
        }
        return $select;
    }

    /**
     * @param \Doctrine\DBAL\Query\QueryBuilder $select
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function applySorters($select)
    {
        foreach ($this->sorters as $sort) {
            $select->addOrderBy($sort->property, $sort->direction);
        }
        return $select;
    }
}
