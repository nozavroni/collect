<?php
namespace Noz\Tests;

use PHPUnit_Framework_TestCase;
use Faker\Factory;

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
        $faker = Factory::create();
        $faker->seed(5242017); // seed with my son's bday to keep data consistent

        $this->fixtures['0index'] = ['zero', 'one', 'two', 'three'];
        $this->fixtures['array'] = ['first', 'second', 'third'];
        $this->fixtures['assoc'] = ['1st' => 'first', '2nd' => 'second', '3rd' => 'third'];
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