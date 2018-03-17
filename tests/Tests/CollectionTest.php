<?php
namespace Noz\Tests;

use Noz\Collection\Collection;
use RuntimeException;

/**
 * Collection Tests
 */
class CollectionTest extends TestCase
{
    public function testConstructCreatesEmptyCollection()
    {
        $col = new Collection();

        $this->assertInstanceOf(Collection::class, $col);
        $this->assertEquals([], $col->toArray());
    }

    public function testToArrayReturnsItemsAsArray()
    {
        $arr = $this->getFixture('0index');
        $col = new Collection($arr);

        $this->assertInternalType("array", $col->toArray());
        $this->assertEquals($arr, $col->toArray());
    }

    public function testHasChecksForKeyWithinCollection()
    {
        $assoc = $this->getFixture('assoc');
        $col = new Collection($assoc);

        $this->assertTrue($col->has('1st'));
        $this->assertFalse($col->has('4th'));
        $this->assertFalse($col->has(0));
    }

    public function testHasChecksForKeyEvenIfValueIsNull()
    {
        $arr = ['nullval' => null, 'zeroval' => 0, 'emptyval' => ''];
        $col = new Collection($arr);

        $this->assertTrue($col->has('nullval'));
        $this->assertTrue($col->has('zeroval'));
        $this->assertTrue($col->has('emptyval'));
    }

    public function testGetReturnsValueAssociatedWithGivenKey()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertEquals('first', $col->get('1st'));
        $this->assertNull($col->get('not here'));
    }

    public function testGetReturnsProvidedDefaultIfKeyDoesntExist()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $neg1 = -1;
        $this->assertNotEquals($neg1, $col->get('1st', $neg1));
        $this->assertSame($neg1, $col->get('not here', $neg1));
    }

    public function testAddAddsItemWithoutRegardToKeyAndReturnsSelf()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $item = 'foo';
        $this->assertFalse($col->has(0));
        $arr2 = $col->toArray();
        $this->assertFalse(in_array($item, $arr2));
        $self = $col->add($item);
        $this->assertSame($self, $col);
        $arr3 = $col->toArray();
        $this->assertTrue(in_array($item, $arr3));
        $this->assertTrue($col->has(0));
    }

    public function testSetAddsItemOrReplacesByKey()
    {
        $col = new Collection();

        $this->assertEquals([], $col->toArray());
        $this->assertNull($col->get('foo'));
        $this->assertSame($col, $col->set('foo', 'bar'), "Ensure Collection->set() returns self");
        $this->assertEquals('bar', $col->get('foo'));
        $col->set('foo', 'car');
        $this->assertEquals('car', $col->get('foo'));
        $col->set(null, 'nullkey');
        $this->assertEquals('nullkey', $col->get(null), "Ensure null can be used as a key");
    }

    public function testCollectionKeysMustBeScalar()
    {
        // I'm thinking keys should be required to be scalars. I could create a specialized collection
        // for SplObjectStorage type collections that use objects as keys or maybe even arrays? I don't
        // know what that would be used for so not a priority right now.

        // For now, a runtime exception should be thrown if you attempt to use a non-scalar collection key
    }

    public function testDeleteRemovesItemByKey()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertTrue($col->has('2nd'));
        $this->assertSame($col, $col->delete('2nd'), "Ensure Collection->delete() returns self");
        $this->assertFalse($col->has('2nd'));
    }

    public function testClearRemovesAllItemsFromCollection()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $toArray = $col->toArray();
        $this->assertSame($arr, $toArray);
        $this->assertInternalType('array', $toArray);
        $this->assertSame($col, $col->clear(), "Ensure Collection->clear() returns self");

        $cleared = $col->toArray();
        $this->assertInternalType('array', $cleared);
        $this->assertEmpty($cleared);
    }

    /** ++++                        ++++ **/
    /** ++ Interface Compliance Tests ++ **/
    /** ++++                        ++++ **/

    public function testArrayAccessInterfaceMethods()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        // offset exists
        $this->assertTrue(isset($col['1st']));
        $this->assertFalse(isset($col['21st']));
        $this->assertArrayHasKey('1st', $col);
        $this->assertArrayNotHasKey('21st', $col);

        // get offset
        $this->assertEquals($arr['1st'], $col['1st']);

        // set offset
        $this->assertFalse(isset($col['test']));
        $col['test'] = 'value';
        $this->assertTrue(isset($col['test']));
        $this->assertEquals('value', $col['test']);
        $this->assertFalse(isset($col[0]));
        $col[] = 'empty key';
        $this->assertTrue(isset($col[0]));
        $this->assertEquals('empty key', $col[0]);

        // offset unset
        $this->assertArrayHasKey('test', $col);
        unset($col['test']);
        $this->assertArrayNotHasKey('test', $col);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testArrayAccessGetOffsetThrowsExceptionIfInvalidKey()
    {
        $col = new Collection();
        $foo = $col['unknown'];
    }

    public function testIteratorInterfaceMethods()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        // test interface methods individually
        $this->assertEquals('first', $col->current());
        $this->assertEquals('1st', $col->key());
        $this->assertEquals('second', $col->next());
        $this->assertNull($col->rewind());
        $this->assertEquals('first', $col->current());
        $col->next();
        $col->next();
        $this->assertTrue($col->valid());
        $col->next();
        $this->assertFalse($col->valid());

        // test that foreach hits each item in collection
        $count = 0;
        foreach ($col as $key => $val) {
            $count++;
        }
        $this->assertEquals(3, $count);
    }

    public function testCountInterfaceMethodReturnsTotalItems()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertEquals(count($arr), $col->count());
    }
}