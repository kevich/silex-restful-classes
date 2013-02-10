<?php
namespace APG\SilexRESTful\Interfaces;

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

}
