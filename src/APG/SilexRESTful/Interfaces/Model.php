<?php
namespace APG\SilexRESTful\Interfaces;

interface Model
{
    /**
     * @param $array
     * @return self
     */
    public function fillFromArray($array);

    /**
     * @return int
     */
    public function getId();

    /**
     * @param int $id
     * @return self
     */
    public function setId($id);

    /**
     * @return string
     */
    public function getObjectName();

}
