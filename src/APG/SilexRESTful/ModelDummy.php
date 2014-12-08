<?php

namespace APG\SilexRESTful;

use APG\SilexRESTful\Interfaces\Model;

class ModelDummy implements Model
{
    /**
     * @var int
     */
    private $id;

    /**
     * @param $array
     * @return $this
     */
    public function fillFromArray($array)
    {
        $obj_vars = get_object_vars($this);
        foreach ($array as $key=>$value) {
            if (get_class($this) == 'APG\SilexRESTful\ModelDummy') {
                $this->$key = $value;
            } else {
                if (array_key_exists($key, $obj_vars)) {
                    $this->$key = $value;
                }
            }
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return (int)$this->id;
    }

    /**
     * @param int $id
     * @return $this|Model
     */
    public function setId($id)
    {
        $this->id = (int)$id;
        return $this;
    }

}
