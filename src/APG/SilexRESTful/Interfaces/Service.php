<?php
namespace APG\SilexRESTful\Interfaces;

use Worker\Model as WorkerModel;

interface Service
{
    /**
     * @param Model $object
     * @return int
     */
    public function saveObject($object);

    /**
     * @param Model $object
     * @return int
     */
    public function updateObject($object);

    /**
     * @return array
     */
    public function getAllAssoc();

    /**
     * @param int $id
     * @return array
     */
    public function getByIdAssoc($id);

    /**
     * @param int $id
     * @return Model
     */
    public function getById($id);

    /**
     * @param int $id
     * @return boolean
     */
    public function deleteById($id);

    /**
     * @param array $filters
     * @return null
     */
    public function setFilters($filters);

    /**
     * @param array $sorters
     */
    public function setSorters($sorters);

    /**
     * @param WorkerModel $user
     * @return null|void
     */
    public function setUser($user);

}
