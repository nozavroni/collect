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

    protected function getTestTable()
    {
        return [
            ['id' => 10, 'name' => 'Foo Barsen', 'active' => true, 'age' => 49],
            ['id' => 11, 'name' => 'Fooby McBar', 'active' => false, 'age' => 19],
            ['id' => 12, 'name' => 'Bar Fooerson', 'active' => true, 'age' => 82],
            ['id' => 13, 'name' => 'Foobar McFoobar', 'active' => true, 'age' => 32],
            ['id' => 14, 'name' => 'Foo B. Bar', 'active' => false, 'age' => 31],
            ['id' => 15, 'name' => 'Barry Fooerston', 'active' => false, 'age' => 25],
            ['id' => 20, 'name' => 'Fooey Barenstein', 'active' => true, 'age' => 32],
            ['id' => 34, 'name' => 'Boo Farson', 'active' => true, 'age' => 21],
            ['id' => 41, 'name' => 'Foobles McBarlot', 'active' => true, 'age' => 44],
        ];
    }

    public function testGetColumnReturnsValuesForSpecifiedKey()
    {
        $table = $this->getTestTable();
        $col = new Collection($table);
        $this->assertSame([49, 19, 82, 32, 31, 25, 32, 21, 44], $col->getColumn('age')->toArray());
    }

//    public function testGetColumnOnNonTabularData()
//    {
//        $nontable = [
//            'foo',
//            1,
//            'boo' => 'far',
//            [1,2,3,4,5],
//            'foo' => ['foo' => 'bar', 'boo' => 'far'],
//            [1 => 2, 3 => 4, 5 => 6],
//            ['foo' => 'gar', 'boo' => 'rar'],
//            'FOOBAR!'
//        ];
//        $col = new Collection($table);
//        $this->assertSame([49, 19, 82, 32, 31, 25, 32, 21, 44], $col->getColumn('age'));
//    }
}