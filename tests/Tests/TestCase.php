<?php
namespace Noz\Tests;

use PHPUnit_Framework_TestCase;

/**
 * Class TestCase.
 *
 * Base test case to share fixtures and useful methods between all test case classes.
 */
class TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $fixtures = [];

    public function setUp()
    {
        $this->fixtures['0index'] = ['zero', 'one', 'two', 'three'];
        $this->fixtures['array'] = ['first', 'second', 'third'];
        $this->fixtures['assoc'] = ['1st' => 'first', '2nd' => 'second', '3rd' => 'third'];
        $this->fixtures['dups'] = [
            'zero' => 0,
            'one' => 1,
            'two' => 2,
            'secondzero' => 0,
            'three' => 3,
            'secondtwo' => 2,
            'secondthree' => 3,
            'thirdzero' => 0
        ];
        $this->fixtures['numwords'] = [
            0 => 'zero',
            1 => 'one',
            'two' => 2,
            3 => 'three',
            'four' => 4,
            'five' => 5,
            4 => 'four'
        ];
    }

    public function tearDown()
    {
        // nothing to do here...
    }

    protected function getFixture($name)
    {
        if (isset($this->fixtures[$name])) {
            return $this->fixtures[$name];
        }
        return [];
    }
}