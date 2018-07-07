<?php
namespace Noz\Tests;

use ArrayIterator;
use Noz\Collection\Collection;
use function Noz\is_traversable,
             Noz\to_array,
             Noz\collect,
             Noz\assign_if,
             Noz\to_numeric,
             Noz\is_numeric;
use SebastianBergmann\GlobalState\RuntimeException;
use stdClass;

/**
 * utility functions tests
 */
class FunctionsTest extends TestCase
{
    public function testIsTraversableReturnsTrueForArraysOrTraversables()
    {
        $arr = $this->getFixture('assoc');
        $iter = new ArrayIterator($arr);
        $this->assertFalse(is_traversable(1));
        $this->assertFalse(is_traversable(true));
        $this->assertFalse(is_traversable('foo'));
        $this->assertFalse(is_traversable(new stdClass));
        $this->assertTrue(is_traversable($arr));
        $this->assertTrue(is_traversable($iter));
    }

    public function testToArrayConvertsCollectionToArray()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertSame([
            '1st' => 'first',
            '2nd' => 'second',
            '3rd' => 'third'
        ], to_array($col));
    }

    public function testToArrayConvertsArrayToArray()
    {
        $arr = $this->getFixture('assoc');

        $this->assertSame([
            '1st' => 'first',
            '2nd' => 'second',
            '3rd' => 'third'
        ], to_array($arr));
    }

    public function testToArrayConvertsTraversableToArray()
    {
        $arr = $this->getFixture('assoc');
        $iter = new ArrayIterator($arr);

        $this->assertSame([
            '1st' => 'first',
            '2nd' => 'second',
            '3rd' => 'third'
        ], to_array($arr));
    }

    public function testToArrayConvertsJsonStringArrayToArray()
    {
        $arr = $this->getFixture('assoc');
        $json = json_encode($arr);

        $this->assertInternalType('string', $json);
        $this->assertJson($json);
        $this->assertSame([
            '1st' => 'first',
            '2nd' => 'second',
            '3rd' => 'third'
        ], to_array($json));
    }

    public function testToArrayConvertsAnythingToArrayIfForceParamSetToTrue()
    {
        $this->assertSame([], to_array(null, true));
        $this->assertSame([1,], to_array(1, true));
        $this->assertSame([true,], to_array(true, true));
        $this->assertSame([false,], to_array(false, true));
        $this->assertSame(['',], to_array('', true));
        $this->assertSame(['foo',], to_array('foo', true));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testToArrayThrowsExceptionIfCannotConvert()
    {
        to_array(null);
    }

    public function testCollectCreatesCollection()
    {
        $arr = [];
        $this->assertInstanceOf(Collection::class, collect($arr));
    }

    public function testAssignIfAssignsBasedOnConditionUsingNonexistingVariable()
    {
        assign_if($var, 10, true);
        $this->assertSame(10, $var);
    }

    public function testAssignIfAssignsBasedOnConditionUsingExistingVariable()
    {
        $var = 5;
        assign_if($var, 10, true);
        $this->assertSame(10, $var);
    }

    public function testAssignIfDoesNotAssignIfConditionFails()
    {
        assign_if($var, 10, false);
        $this->assertNull($var);

        $var1 = 5;
        assign_if($var1, 10, false);
        $this->assertEquals(5, $var1);
    }

    public function testAssignIfAcceptsNamedFunction()
    {
        assign_if($var, $val = 10, 'is_numeric');
        $this->assertEquals($val, $var);

        assign_if($var1, $val = 'ten', 'is_numeric');
        $this->assertNotEquals($val, $var1);
    }

    public function testAssignIfAcceptsAnonymousFunction()
    {
        assign_if($var, $val = 10, function($val) { return $val > 5; });
        $this->assertEquals($val, $var);

        assign_if($var1, $val = 10, function($val) { return $val > 50; });
        $this->assertNotEquals($val, $var1);
    }

    public function testToNumericReturnsNumericValueOfStrings()
    {
        $this->assertSame(1, to_numeric('1'));
        $this->assertSame(0, to_numeric(true));
        $this->assertSame(1.0, to_numeric('1.0'));
        $this->assertSame(1.1, to_numeric('1.1'));
        $this->assertSame(0, to_numeric('0'));
    }

    public function testIsNumericIsAliasOfPhpIsNumeric()
    {
        $this->assertTrue(is_numeric('1'));
        $this->assertTrue(is_numeric('1.1'));
        $this->assertTrue(is_numeric('100'));
        $this->assertTrue(is_numeric('0.000001'));
        $this->assertTrue(is_numeric(100));
        $this->assertFalse(is_numeric('foo'));
        $this->assertFalse(is_numeric(''));
        $this->assertFalse(is_numeric(false));
        $this->assertFalse(is_numeric(true));
        $this->assertFalse(is_numeric(new \stdClass));
    }

}
