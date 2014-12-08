<?php
namespace APG\SilexRESTful;

use Silex\ControllerCollection;

class ControllerProviderDummy extends ControllerProviderAbstract
{
    /**
     * @param string $object_name
     */
    public function __construct($object_name) {
        $this->object_name = $object_name;
    }


    /**
     * @param ControllerCollection $controllers
     */
    protected function registerAdditionalControllers($controllers)
    {
    }
}
