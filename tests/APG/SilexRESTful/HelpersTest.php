<?php
namespace APG\SilexRESTful;


class HelpersTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider toCamelCaseProvider
     * @param string $expect
     * @param string $input
     * @param bool $capitaliseFirstChar
     */
    public function testConvertsToCamelCase($expect, $input, $capitaliseFirstChar = false)
    {
        $this->assertEquals($expect, Helpers::to_camel_case($input, $capitaliseFirstChar));
    }

    /**
     * @return array
     */
    public function toCamelCaseProvider()
    {
        return array(
            array('testCamelCase', 'test_camel_case'),
            array('TestCamelCaseFirstLetter', 'test_camel_case_first_letter', true),
            array('TestWordCase', 'Test_wordCase')
        );
    }

    /**
     * @dataProvider fromCamelCaseProvider
     * @param string $expect
     * @param string $input
     */
    public function testConvertsFromCamelCase($expect, $input)
    {
        $this->assertEquals($expect, Helpers::from_camel_case($input));
    }

    /**
     * @return array
     */
    public function fromCamelCaseProvider()
    {
        return array(
            array('test_camel_case', 'testCamelCase'),
            array('test_camel_case_first_letter', 'TestCamelCaseFirstLetter'),
            array('test_word_case', 'TestWordCase')
        );
    }
}
