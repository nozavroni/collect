<?php
namespace Noz\Tests;

use ArrayIterator;
use Noz\Collection\Collection;
use function Noz\is_traversable,
             Noz\to_array,
             Noz\collect;
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
}