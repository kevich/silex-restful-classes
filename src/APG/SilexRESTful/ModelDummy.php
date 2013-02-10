<?php

namespace APG\SilexRESTful;

use APG\SilexRESTful\Interfaces\Model;

class ModelDummy implements Model
{
    private $object_name;
    private $id;

    public function __construct($object_name)
    {
        $this->object_name = $object_name;
    }

    public function fillFromArray($array)
    {
        $obj_vars = get_object_vars($this);
        foreach ($array as $key=>$value) {
            if (get_class($this) == 'ModelDummy') {
                $this->$key = $value;
            } else {
                if (array_key_exists($key, $obj_vars)) {
                    $this->$key = $value;
                }
            }

        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getObjectName()
    {
        return $this->object_name;
    }
    
}
