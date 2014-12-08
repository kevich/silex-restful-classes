<?php

namespace APG\SilexRESTful;


class TestModel extends ModelDummy {

    public $name;
    public $surname;
    public $login;

}

class ModelDummyTest extends \PHPUnit_Framework_TestCase {

    public function testDummyModelSavesAllArrayValues()
    {
        $values = array(
            'item1' => 'value1',
            'item2' => 'value2',
            'item3' => 'value3',
            'item4' => 'value4',
            'item5' => 'value5'
        );

        $model = self::dummyModel();
        $model->fillFromArray($values);

        foreach ($values as $name=>$value) {
            $this->assertEquals($value, $model->$name);
        }

    }

    public function testExcludesPropertiesIfModelIsSet()
    {
        $values = array(
            'name' => 'Andrew',
            'surname' => 'Kevich',
            'login' => 'kevich',
            'id' => 20,
            'item5' => 'value5'
        );

        $model = new TestModel();
        $model->fillFromArray($values);

        $this->assertEquals('Andrew', $model->name);
        $this->assertEquals('Kevich', $model->surname);
        $this->assertEquals('kevich', $model->login);
        $this->assertEquals(20, $model->getId());
        $this->assertNull($model->item5);
    }

    public function testCanSetAndGetId()
    {
        $model = self::dummyModel();

        $this->assertEquals((int)null, $model->getId());
        $model->setId(12);
        $this->assertEquals(12, $model->getId());
    }

    public function testSanitiseId()
    {
        $model = self::dummyModel();
        $model->setId('abc12');
        $this->assertEquals(0, $model->getId());
    }

    private static function dummyModel()
    {
        return new ModelDummy();
    }
}
