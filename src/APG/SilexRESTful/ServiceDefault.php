<?php
namespace APG\SilexRESTful;

use APG\SilexRESTful\Interfaces\Service;
use APG\SilexRESTful\Interfaces\Model;

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
     * @param \Doctrine\DBAL\Connection $db
     */
    public function __construct($db)
    {
        $this->db = $db;
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

    public function setFilters($filters)
    {
        $this->filters = $filters;
    }

    /**
     * @param Model $object
     * @return int
     */
    public function saveObject($object)
    {
        $status = $this->db->insert($object->getObjectName(), get_object_vars($object));
        $object->setId($this->db->lastInsertId($object->getObjectName()."_id_seq"));
        return $status;
    }

    /**
     * @param Model $object
     * @return int
     */
    public function updateObject($object)
    {
        return $this->db->update($object->getObjectName(), get_object_vars($object), array('id' => $object->getId()));
    }

    /**
     * @param null|int $start
     * @param null|int $limit
     * @return array
     */
    public function getAllAssoc($start = null, $limit = null)
    {
        return $this->table_name ?
            $this->db->fetchAll(
                'SELECT ' . $this->get_fields() . ' FROM ' . $this->table_name . $this->createWhere()
                    . (($start != null && $limit != null) ? (' LIMIT ' . $start . ',' . $limit) : '')
            )
            : array();
    }

    public function getTotalCount()
    {
        return $this->table_name ?
            $this->db->fetchColumn('SELECT COUNT(1) FROM ' . $this->table_name . $this->createWhere())
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
        return class_exists($class_name) ? implode(',', array_keys(get_class_vars($class_name))) : '*';
    }

    protected function createWhere()
    {
        $where = ' WHERE TRUE';
        if (count($this->filters) > 0) {
            foreach ($this->filters as $filter) {
                $field = false;
                if (property_exists($filter, 'field')) $field = $filter->field;
                if (property_exists($filter, 'property')) $field = $filter->property;
                if (!property_exists($filter, 'type')) $filter->type = 'string';
                if ($field) {
                    switch ($filter->type) {
                        case 'string':
                            $where .= " AND {$field} LIKE ('%{$filter->value}%')";
                            break;
                        case 'foreingKey':
                            $where .= " AND {$field} = " . $filter->value;
                    }
                }
            }
        }
        return $where;
    }
}
