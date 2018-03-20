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

    public function testFactoryReturnsNewEmptyCollection()
    {
        $col = Collection::factory();
        $this->assertInstanceOf(Collection::class, $col);
        $this->assertTrue($col->isEmpty());
    }

    public function testFactoryReturnsNewCollectionWithItemsPassedToIt()
    {
        $arr = $this->getFixture('assoc');
        $col = Collection::factory($arr);
        $this->assertInstanceOf(Collection::class, $col);
        $this->assertFalse($col->isEmpty());
        $this->assertSame($arr, $col->toArray());
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

    public function testHasItemAtReturnsTrueIfCollectionHasItemAtGivenPosition()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertFalse($col->hasValueAt(10));
        $this->assertTrue($col->hasValueAt(1));
        $this->assertTrue($col->hasValueAt(2));
        $this->assertTrue($col->hasValueAt(3));
        $this->assertFalse($col->hasValueAt(4));
    }

    public function testHasItemAtReturnsTrueIfCollectionHasItemAtGivenNegativePosition()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertFalse($col->hasValueAt(-10));
        $this->assertTrue($col->hasValueAt(-1));
        $this->assertTrue($col->hasValueAt(-2));
        $this->assertTrue($col->hasValueAt(-3));
        $this->assertFalse($col->hasValueAt(-4));
    }

    public function testGetKeyAtReturnsKeyAtGivenPosition()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertEquals('1st', $col->getKeyAt(1));
        $this->assertEquals('2nd', $col->getKeyAt(2));
        $this->assertEquals('3rd', $col->getKeyAt(3));
        $this->assertEquals('1st', $col->getKeyAt(-3));
        $this->assertEquals('2nd', $col->getKeyAt(-2));
        $this->assertEquals('3rd', $col->getKeyAt(-1));
    }

    public function testGetValueAtReturnsValueAtGivenPosition()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertEquals('first', $col->getValueAt(1));
        $this->assertEquals('second', $col->getValueAt(2));
        $this->assertEquals('third', $col->getValueAt(3));
        $this->assertEquals('first', $col->getValueAt(-3));
        $this->assertEquals('second', $col->getValueAt(-2));
        $this->assertEquals('third', $col->getValueAt(-1));
    }

    public function testFlipReturnsNewCollectionWithKeysAndValuesFlipped()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertSame(array_flip($arr), $col->flip()->toArray());
    }

    /**
     * Testing for randomness doesnt really make sense so I just test that it returns itself.
     * I'm using PHP's internal "shuffle" within the method so it doesn't really need testing anyway.
     */
    public function testShuffleReturnsSelf()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertSame($col, $col->shuffle());
    }

    /**
     * Not really sure how to test this method...
     */
    public function testRandomReturnsARandomItem()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        for ($i=0;$i<100;$i++) {
            $item = $col->random();
            $this->assertTrue(in_array($item, $arr));
        }
    }

    public function testDistinctReturnsCollectionSansDuplicates()
    {
        $arr = $this->getFixture('dups');
        $col = new Collection($arr);

        $this->assertEquals([
            'zero' => 0,
            'one' => 1,
            'two' => 2,
            'three' => 3
        ], $col->distinct()->toArray());
    }

    public function testDeduplicateRemovesDuplicatesInPlace()
    {
        $arr = $this->getFixture('dups');
        $col = new Collection($arr);

        $this->assertSame($col, $col->deduplicate());
        $this->assertEquals([
            'zero' => 0,
            'one' => 1,
            'two' => 2,
            'three' => 3
        ], $col->toArray());
    }

    public function testFilterKeepsOnlyItemsPassingTest()
    {
        $arr = $this->getFixture('numwords');
        $col = new Collection($arr);

        $filtered = $col->filter(function($val, $key) {
            return is_int($val);
        });
        $this->assertEquals([
            'two' => 2,
            'four' => 4,
            'five' => 5
        ], $filtered->toArray());

        $filtered = $col->filter(function($val, $key) {
            return is_int($key);
        });
        $this->assertEquals([
            0 => 'zero',
            1 => 'one',
            3 => 'three',
            4 => 'four'
        ], $filtered->toArray());

        $filtered = $col->filter(function($val, $key, $i) {
            return $i % 2 == 0;
        });
        $this->assertEquals([
            0 => 'zero',
            'two' => 2,
            'four' => 4,
            4 => 'four'
        ], $filtered->toArray());
    }

    public function testFoldReturnsOneValue()
    {
        $arr = $this->getFixture('numwords');
        $col = new Collection($arr);

        $this->assertEquals(11, $col->fold(function($accum, $val, $key, $i) {
            if (is_int($val)) {
                return $accum + $val;
            }
            return $accum;
        }));

        $this->assertEquals('[[[[[[[init-zero-0-0]-one-1-1]-2-two-2]-three-3-3]-4-four-4]-5-five-5]-four-4-6]', $col->fold(function($accum, $val, $key, $i) {
            return "[{$accum}-{$val}-{$key}-{$i}]";
        }, 'init'), 'Initial value passed in should be the first value of $accum');
    }

    public function testMergeMergesArrayIntoNewCollection()
    {
        $arr1 = $this->getFixture('numwords');
        $arr2 = ['two' => 'two', 'three' => 3, 'four' => 'four', 4 => 'for'];

        $col = new Collection($arr1);

        $merged = $col->merge($arr2);
        $this->assertEquals([
            0 => 'zero',
            1 => 'one',
            'two' => 'two',
            3 => 'three',
            'four' => 'four',
            'five' => 5,
            4 => 'for',
            'three' => 3
        ], $merged->toArray());
    }

    public function testMergeMergesTraversableIntoNewCollection()
    {
        $arr1 = $this->getFixture('numwords');
        $arr2 = ['two' => 'two', 'three' => 3, 'four' => 'four', 4 => 'for'];

        $col = new Collection($arr1);
        $col2 = new Collection($arr2);

        $merged = $col->merge($col2);
        $this->assertEquals([
            0 => 'zero',
            1 => 'one',
            'two' => 'two',
            3 => 'three',
            'four' => 'four',
            'five' => 5,
            4 => 'for',
            'three' => 3
        ], $merged->toArray());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testMergeThrowsExceptionIfPassedInvalidInput()
    {
        $col = new Collection();
        $col->merge(false);
    }

    public function testUnionMergesArrayIntoNewCollectionWithoutOverwriting()
    {
        $arr1 = $this->getFixture('numwords');
        $arr2 = ['two' => 'two', 'three' => 3, 'four' => 'four', 4 => 'for'];

        $col = new Collection($arr1);
        $col2 = new Collection($arr2);

        $union = $col->union($col2);
        $this->assertEquals([
            0 => 'zero',
            1 => 'one',
            'two' => 2,
            3 => 'three',
            'four' => 4,
            'five' => 5,
            4 => 'four',
            'three' => 3
        ], $union->toArray());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testUnionThrowsExceptionIfPassedInvalidInput()
    {
        $col = new Collection();
        $col->union('invalid input');
    }

    public function testIndexOfReturnsFirstIndexOfFoundItem()
    {
        $arr = $this->getFixture('dups');
        $col = new Collection($arr);

        $this->assertEquals(1, $col->indexOf(1));
        $this->assertEquals(2, $col->indexOf(2));
        $this->assertEquals(4, $col->indexOf(3));
        $this->assertNull($col->indexOf(4));
    }

    public function testKeyOfReturnsFirstKeyOfFoundItem()
    {
        $arr = $this->getFixture('dups');
        $col = new Collection($arr);

        $this->assertEquals('one', $col->keyOf(1));
        $this->assertEquals('two', $col->keyOf(2));
        $this->assertEquals('three', $col->keyOf(3));
        $this->assertNull($col->keyOf(4));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testGetKeyAtThrowsExceptionIfPositionDoesntExist()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $col->getKeyAt(4);
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

    public function testSetShouldOptionallyNotOverwriteExistingKeys()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertEquals('first', $col->get('1st'));
        $this->assertSame($col, $col->set('1st', 'worst', false));
        $this->assertEquals('first', $col->get('1st'), "Collection item's value should not have changed because overwrite param was set to false");
        $col->set('1st', 'worst', true);
        $this->assertEquals('worst', $col->get('1st'), "Collection item's value should have changed because overwrite param was set to true");
        $col->set('totallynew', 'newval', false);
        $this->assertEquals('newval', $col->get('totallynew'), "Collection should add a new key even if overwrite is set to false");
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

    public function testContainsReturnsTrueIfCollectionContainsValue()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertFalse($col->contains('fourth'));
        $this->assertTrue($col->contains('first'));
    }

    public function testPullRemovesItemAndReturnsIt()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertTrue($col->has('2nd'));
        $this->assertEquals('second', $col->pull('2nd'));
        $this->assertFalse($col->has('2nd'));
        $this->assertNull($col->pull('2nd'));
    }

    public function testJoinReturnsDelimitedString()
    {
        $arr = $this->getFixture("assoc");
        $col = new Collection($arr);

        $this->assertEquals("first-second-third", $col->join('-'));
    }

    public function testIsEmptyReturnsTrueIfEmpty()
    {
        $col = new Collection();

        $this->assertTrue($col->isEmpty());
        $col->add('foo');
        $this->assertFalse($col->isEmpty());
    }

    public function testValuesReturnsCollectionOfValuesWithoutKeys()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertSame($arr, $col->toArray());
        $values = $col->values();
        $this->assertInstanceOf(Collection::class, $values);
        $this->assertSame(array_values($arr), $values->toArray());
    }

    public function testKeysReturnsCollectionOfKeys()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertSame($arr, $col->toArray());
        $keys = $col->keys();
        $this->assertInstanceOf(Collection::class, $keys);
        $this->assertSame(array_keys($arr), $keys->toArray());
    }

    public function testSortDefaultsToAlphabeticalCaseSensitiveOrder()
    {
        $namesarr = $this->getFixture('0index');
        $names = new Collection($namesarr);

        $this->assertSame([
            0 => 'zero',
            1 => 'one',
            2 => 'two',
            3 => 'three'
        ], $names->toArray());

        $this->assertSame([
            1 => 'one',
            3 => 'three',
            2 => 'two',
            0 => 'zero'
        ], $names->sort()->toArray());

        $case = new Collection($arr = ['Ag', 'AA', 'aa', 'BA', 'AB', 'a0', 0, 1, '0bs']);
        $this->assertSame([
            6 => 0,
            8 => '0bs',
            7 => 1,
            1 => 'AA',
            4 => 'AB',
            0 => 'Ag',
            3 => 'BA',
            5 => 'a0',
            2 => 'aa'
        ], $case->sort()->toArray());
    }

    public function testSortAcceptsAnonymousFunctionAsSortAlgorithm()
    {
        $arr = $this->getFixture('numwords');
        $col = new Collection($arr);

        $this->assertSame([
            'two' => 2,
            'four' => 4,
            'five' => 5,
            1 => 'one',
            0 => 'zero',
            4 => 'four',
            3 => 'three',
        ], $col->sort(function($a, $b) {
            if (!is_int($a)) {
                return strlen($a) - strlen($b);
            }
            return $a - $b;
        })->toArray());
    }

    public function testKSortDefaultsToAlphabeticalCaseSensitiveOrder()
    {
        $namesarr = array_flip($this->getFixture('0index'));
        $names = new Collection($namesarr);

        $this->assertSame([
            'one' => 1,
            'three' => 3,
            'two' => 2,
            'zero' => 0
        ], $names->ksort()->toArray());

        $case = new Collection(array_flip(['Ag', 'AA', 'aa', 'BA', 'AB', 'a0', 0, 1, '0bs']));
        $this->assertSame([
            0 => 6,
            '0bs' => 8,
            1 => 7,
            'AA' => 1,
            'AB' => 4,
            'Ag' => 0,
            'BA' => 3,
            'a0' => 5,
            'aa' => 2
        ], $case->ksort()->toArray());
    }

    public function testKSortAcceptsAnonymousFunctionAsSortAlgorithm()
    {
        $arr = $this->getFixture('numwords');
        $col = new Collection($arr);
        $this->assertSame([
            0 => 'zero',
            1 => 'one',
            3 => 'three',
            4 => 'four',
            'two' => 2,
            'four' => 4,
            'five' => 5,
        ], $col->ksort(function($a, $b) {
            if (is_int($a) && is_int($b)) {
                return $a - $b;
            }
            return strlen($a) - strlen($b);
        })->toArray());
    }

    public function testAppendAddsArrayToCollectionWithoutRegardToKey()
    {
        $arr1 = $this->getFixture('assoc');
        $arr2 = $this->getFixture('numwords');
        $col = new Collection($arr1);

        $this->assertSame($col, $col->append($arr2));
        $this->assertSame([
            '1st' => 'first', '2nd' => 'second', '3rd' => 'third',
            'zero',
            'one',
            2,
            'three',
            4,
            5,
            'four'
        ], $col->toArray());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testAppendThrowsRuntimeExceptionIfPassedNonIterable()
    {
        $col = new Collection();
        $col->append(true);
    }

    public function testFirstReturnsFirstItemInCollectionIfNoCallbackPassedIn()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertEquals('first', $col->first());
    }

    public function testFirstReturnsFirstItemMatchingCallbackUsingVal()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertEquals('third', $col->first(function($val) {
            return strpos($val, 'h') !== false;
        }));
    }

    public function testFirstReturnsFirstItemMatchingCallbackUsingValAndKey()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertEquals('second', $col->first(function($val, $key) {
            return strpos($key, 'n') !== false;
        }));
    }

    public function testFirstReturnsFirstItemMatchingCallbackUsingValKeyAndIndex()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertEquals('second', $col->first(function($val, $key, $index) {
            return $index > 0;
        }));
    }

    public function testLastReturnsLastItemMatchingCallbackUsingVal()
    {
        $arr = $this->getFixture('numwords');
        $col = new Collection($arr);

        $this->assertEquals(5, $col->last(function($val) {
            return is_int($val);
        }));
    }

    public function testLastReturnsLastItemMatchingCallbackUsingValAndKey()
    {
        $arr = $this->getFixture('numwords');
        $col = new Collection($arr);

        $this->assertEquals(4, $col->last(function($val, $key) {
            return strpos($key, 'o') !== false;
        }));
    }

    public function testLastReturnsLastItemMatchingCallbackUsingValKeyAndIndex()
    {
        $arr = $this->getFixture('numwords');
        $col = new Collection($arr);

        $this->assertEquals(5, $col->last(function($val, $key, $index) {
            return $index % 2 != 0;
        }));
    }

    public function testMapCallsCallbackOnEachItemAndReturnsCollectionOfResultsValueOnly()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertSame([
            '1st' => 5,
            '2nd' => 6,
            '3rd' => 5
        ], $col->map(function($val) {
            return strlen($val);
        })->toArray());
    }

    public function testMapCallsCallbackOnEachItemAndReturnsCollectionOfResultsValueAndKey()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertSame([
            '1st' => '1st: first',
            '2nd' => '2nd: second',
            '3rd' => '3rd: third'
        ], $col->map(function($val, $key) {
            return "{$key}: {$val}";
        })->toArray());
    }

    public function testMapCallsCallbackOnEachItemAndReturnsCollectionOfResultsValueKeyAndIndex()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertSame([
            '1st' => '1st: first [0]',
            '2nd' => '2nd: second [1]',
            '3rd' => '3rd: third [2]'
        ], $col->map(function($val, $key, $index) {
            return "{$key}: {$val} [{$index}]";
        })->toArray());
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

    public function testJsonSerializeReturnsCollectionAsArray()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);
        $this->assertEquals($arr, $col->jsonSerialize());
    }
}