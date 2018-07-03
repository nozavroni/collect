<?php
namespace Noz\Tests;

use ArrayIterator;
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

    public function testFactoryAcceptsAnythingToArrayAccepts()
    {
        $obj = new Collection([1,2,3]);
        $iter = new ArrayIterator([3,2,1]);
        $json = '{"1st":"first","2nd":"second","3rd":"third"}';

        $objcol = Collection::factory($obj);
        $this->assertSame([1,2,3], $objcol->toArray());

        $itercol = Collection::factory($iter);
        $this->assertSame([3,2,1], $itercol->toArray());

        $jsoncol = Collection::factory($json);
        $this->assertSame(['1st' => 'first', '2nd' => 'second', '3rd' => 'third'], $jsoncol->toArray());
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

        for ($i = 0; $i < 100; $i++) {
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

        $filtered = $col->filter(function ($val, $key) {
            return is_int($val);
        });
        $this->assertEquals([
            'two' => 2,
            'four' => 4,
            'five' => 5
        ], $filtered->toArray());

        $filtered = $col->filter(function ($val, $key) {
            return is_int($key);
        });
        $this->assertEquals([
            0 => 'zero',
            1 => 'one',
            3 => 'three',
            4 => 'four'
        ], $filtered->toArray());

        $filtered = $col->filter(function ($val, $key, $i) {
            return $i % 2 == 0;
        });
        $this->assertEquals([
            0 => 'zero',
            'two' => 2,
            'four' => 4,
            4 => 'four'
        ], $filtered->toArray());
    }

    public function testFilterKeepsOnlyItemsThatEvaluateToTrueIfNoCallbackProvided()
    {
        $arr = [
            '0th' => 0,
            '1st' => 'first',
            'null' => null,
            'true' => true,
            'empty' => [],
            'notempty' => [1,2,3],
            'false' => false,
            2,
            100,
            'emptystring' => '',
        ];
        $col = new Collection($arr);

        $this->assertSame([
            '1st' => 'first',
            'true' => true,
            'notempty' => [1,2,3],
            2,
            100,
        ], $col->filter()->toArray());
    }

    public function testFoldReturnsOneValue()
    {
        $arr = $this->getFixture('numwords');
        $col = new Collection($arr);

        $this->assertEquals(11, $col->fold(function ($accum, $val, $key, $i) {
            if (is_int($val)) {
                return $accum + $val;
            }
            return $accum;
        }));

        $this->assertEquals('[[[[[[[init-zero-0-0]-one-1-1]-2-two-2]-three-3-3]-4-four-4]-5-five-5]-four-4-6]', $col->fold(function ($accum, $val, $key, $i) {
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
    }

    public function testIndexOfAcceptsCallback()
    {
        $arr = $this->getFixture('dups');
        $col = new Collection($arr);

        $this->assertEquals(4, $col->indexOf(function($val) {
            return $val > 2;
        }));

        $this->assertEquals(0, $col->indexOf(function($val, $key) {
            return strlen($key) > 3;
        }));

        $this->assertEquals(1, $col->indexOf(function($val, $key, $index) {
            return $index % 2 != 0;
        }));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testIndexOfThrowsExceptionIfIndexNotFound()
    {
        $col = Collection::factory(['test' => 'val', 'foobar']);
        $col->indexOf('foo');
    }

    public function testKeyOfReturnsFirstKeyOfFoundItem()
    {
        $arr = $this->getFixture('dups');
        $col = new Collection($arr);

        $this->assertEquals('one', $col->keyOf(1));
        $this->assertEquals('two', $col->keyOf(2));
        $this->assertEquals('three', $col->keyOf(3));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testKeyOfThrowsExceptionOnItemNotFound()
    {
        $col = Collection::factory(['foo' => 'bar']);
        $col->keyOf('poo');
    }

    public function testKeyOfAcceptsCallback()
    {
        $arr = $this->getFixture('dups');
        $col = new Collection($arr);

        $this->assertEquals('three', $col->keyOf(function($val) {
            return $val > 2;
        }));

        $this->assertEquals('zero', $col->keyOf(function($val, $key) {
            return strlen($key) > 3;
        }));

        $this->assertEquals('one', $col->keyOf(function($val, $key, $index) {
            return $index % 2 != 0;
        }));
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

    public function testContainsAcceptsKeyAsSecondArgumentAndChecksKeyIfProvided()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertTrue($col->contains('first'));
        $this->assertFalse($col->contains('first', '2nd'));
        $this->assertFalse($col->contains('second', '1st'));
        $this->assertTrue($col->contains('first', '1st'));
    }

    public function testContainAcceptsCallbackForEqualityTest()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertTrue($col->contains(function($val) { return strpos($val, 's') === 0; }));
        $this->assertTrue($col->contains(function($val, $key) { return strpos($key, '1') === 0; }));
        $this->assertFalse($col->contains(function($val) { return strpos($val, 's') === 0; }, '1st'));
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
        $arr[0] = 'ze';
        $col = new Collection($arr);

        $this->assertSame([
            0 => 'ze',
            1 => 'one',
            4 => 'four',
            3 => 'three',
            'two' => 2,
            'four' => 4,
            'five' => 5,
        ], $col->sort(function ($a, $b) {
            if (!is_int($a)) {
                if (!is_int($b)) {
                    return strlen($a) - strlen($b);
                } else {
                    return 0;
                }
            }
            if ($a == $b) return 0;
            elseif ($a > $b) return 1;
            else return -1;
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
        ], $col->ksort(function ($a, $b) {
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

        $this->assertEquals('third', $col->first(function ($val) {
            return strpos($val, 'h') !== false;
        }));
    }

    public function testFirstReturnsFirstItemMatchingCallbackUsingValAndKey()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertEquals('second', $col->first(function ($val, $key) {
            return strpos($key, 'n') !== false;
        }));
    }

    public function testFirstReturnsFirstItemMatchingCallbackUsingValKeyAndIndex()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertEquals('second', $col->first(function ($val, $key, $index) {
            return $index > 0;
        }));
    }

    public function testLastReturnsLastItemMatchingCallbackUsingVal()
    {
        $arr = $this->getFixture('numwords');
        $col = new Collection($arr);

        $this->assertEquals(5, $col->last(function ($val) {
            return is_int($val);
        }));
    }

    public function testLastReturnsLastItemMatchingCallbackUsingValAndKey()
    {
        $arr = $this->getFixture('numwords');
        $col = new Collection($arr);

        $this->assertEquals(4, $col->last(function ($val, $key) {
            return strpos($key, 'o') !== false;
        }));
    }

    public function testLastReturnsLastItemMatchingCallbackUsingValKeyAndIndex()
    {
        $arr = $this->getFixture('numwords');
        $col = new Collection($arr);

        $this->assertEquals(5, $col->last(function ($val, $key, $index) {
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
        ], $col->map(function ($val) {
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
        ], $col->map(function ($val, $key) {
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
        ], $col->map(function ($val, $key, $index) {
            return "{$key}: {$val} [{$index}]";
        })->toArray());
    }

    public function testCombineUsesCollectionForKeysAndIncomingItemsForValues()
    {
        $arr = $this->getFixture('array');
        $col = new Collection($arr);

        $arr2 = ['it', 'wasn\'t', 'me'];

        $this->assertSame([
            'first' => 'it',
            'second' => 'wasn\'t',
            'third' => 'me'
        ], $col->combine($arr2)->toArray());
    }

    public function testCombineUsesCollectionForKeysAndIncomingCollectionForValues()
    {
        $arr = $this->getFixture('array');
        $col = new Collection($arr);

        $arr2 = ['it', 'wasn\'t', 'me'];
        $col2 = new Collection($arr2);

        $this->assertSame([
            'first' => 'it',
            'second' => 'wasn\'t',
            'third' => 'me'
        ], $col->combine($col2)->toArray());
    }

    public function testCombineUsesCollectionForKeysAndIncomingTraversableForValues()
    {
        $arr = $this->getFixture('array');
        $col = new Collection($arr);

        $arr2 = ['it', 'wasn\'t', 'me'];
        $iter = new ArrayIterator($arr2);

        $this->assertSame([
            'first' => 'it',
            'second' => 'wasn\'t',
            'third' => 'me'
        ], $col->combine($iter)->toArray());
    }

    public function testEachCallsCallbackOnEachItemPassively()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $test = [];

        $this->assertSame($col, $col->each(function($val, $key, $index) use (&$test) {
            $test[] = "{$key}-{$val}-{$index}";
        }));
        $this->assertSame([
            "1st-first-0",
            "2nd-second-1",
            "3rd-third-2"
        ], $test);
    }

    public function testEachBreaksFromIterationIfCallbackReturnsFalse()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $test = [];

        $this->assertSame($col, $col->each(function($val, $key, $index) use (&$test) {
            $test[] = "{$key}-{$val}-{$index}";
            if ($index) return false;
        }));
        $this->assertSame([
            "1st-first-0",
            "2nd-second-1"
        ], $test);
    }

    public function testAssertEnsuresGivenValueReturnedFromCallback()
    {
        $arr = $this->getFixture('array');
        $col = new Collection($arr);

        $this->assertTrue($col->assert(function($val, $key, $index) { return true; }), "Assert should default to true");
        $this->assertFalse($col->assert(function($val, $key, $index) { return true; }, false), "Assert should default to true");
        $this->assertFalse($col->assert(function($val, $key, $index) { return $val; }, ''), "If callback doesn't return the assert value, assert should return false");
        $this->assertTrue($col->assert(function($val, $key, $index) { return strlen($val) == 10; }, false));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testCombineThrowsExceptionIfPassedInvalidInput()
    {
        $arr = $this->getFixture('array');
        $col = new Collection($arr);
        $col->combine('foo');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testCombineThrowsExceptionIfPassedItemsWithDifferentNumItems()
    {
        $arr = $this->getFixture('array');
        $arr2 = $this->getFixture('0index');
        $col = new Collection($arr);
        $col->combine($arr2);
    }

    public function testPipePassesCollectionThroughCallback()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertFalse($col->pipe(function(Collection $col){
            return $col->isEmpty();
        }));

        $col->clear();
        $this->assertTrue($col->pipe(function(Collection $col){
            return $col->isEmpty();
        }));
    }

    public function testChunkReturnsNewCollectionOfArraysOfSpecifiedSize()
    {
        $arr = range('a', 'z');
        $col = new Collection($arr);

        $this->assertSame([
            ['a','b','c','d','e'],
            [5 => 'f',6 => 'g',7 => 'h',8 => 'i',9 => 'j'],
            [10 => 'k',11 => 'l',12 => 'm',13 => 'n',14 => 'o'],
            [15 => 'p',16 => 'q',17 => 'r',18 => 's',19 => 't'],
            [20 => 'u',21 => 'v',22 => 'w',23 => 'x',24 => 'y'],
            [25 => 'z',]
        ], $col->chunk(5)->toArray());
    }

    public function testSplitReturnsNewCollectionOfSpecifiedAmountOfArrays()
    {
        $arr = range('a', 'z');
        $col = new Collection($arr);

        $this->assertSame([
            [0 => 'a',1 => 'b',2 => 'c',3 => 'd',4 => 'e', 5 => 'f', 6 => 'g'],
            [7 => 'h', 8 => 'i', 9 => 'j', 10 => 'k', 11 => 'l', 12 => 'm', 13 => 'n'],
            [14 => 'o', 15 => 'p', 16 => 'q', 17 => 'r', 18 => 's', 19 => 't', 20 => 'u'],
            [21 => 'v', 22 => 'w', 23 => 'x', 24 => 'y', 25 => 'z'],
        ], $col->split(4)->toArray());
    }

    public function testSliceReturnsCollectionOfSliceOfCurrentCollection()
    {
        $arr = range('a', 'z');
        $col = new Collection($arr);

        $this->assertSame([
            5 => 'f',
            6 => 'g',
            7 => 'h',
            8 => 'i',
            9 => 'j',
            10 => 'k',
            11 => 'l',
            12 => 'm',
            13 => 'n',
            14 => 'o',
            15 => 'p',
            16 => 'q',
            17 => 'r',
            18 => 's',
            19 => 't',
            20 => 'u',
            21 => 'v',
            22 => 'w',
            23 => 'x',
            24 => 'y',
            25 => 'z'
        ], $col->slice(5)->toArray(), "Slice should be able to start at a position and go to the end (if no length provided)");
        $this->assertSame([
            0 => 'a',
            1 => 'b',
            2 => 'c',
            3 => 'd',
            4 => 'e'
        ], $col->slice(0, 5)->toArray(), "Slice should be able to start at zero and go to given length");
        $this->assertSame([
            5 => 'f',
            6 => 'g',
            7 => 'h'
        ], $col->slice(5, 3)->toArray(), "Slice should be able to start at a given position and go to given length");
        $this->assertSame([
            16 => 'q',
            17 => 'r',
            18 => 's',
            19 => 't',
            20 => 'u'
        ], $col->slice(-10, 5)->toArray(), "Slice should be able to start at a negative position and go to a given length");
        $this->assertSame([
            16 => 'q',
            17 => 'r',
            18 => 's',
            19 => 't',
            20 => 'u',
            21 => 'v',
            22 => 'w'
        ], $col->slice(-10, -3)->toArray(), "Slice should be able to start at a negative position and go to a negative length");
    }

    public function testDiffReturnsCollectionContainingOnlyDifferingItems()
    {
        $arr = $this->getFixture('array');
        $col = new Collection($arr);

        $this->assertSame([1=>'second', 2=>'third'], $col->diff(['b', 'first', 'SECOND', '0'])->toArray(), "Ensure diff accepts array");
    }

    public function testKDiffReturnsCollectionContainingOnlyDifferingItemsByKey()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertSame(['1st' => 'first', '3rd' => 'third'], $col->kdiff(['2nd' => 'SECOND!', '4th' => 'fourth'])->toArray(), "Ensure kdiff accepts array");
    }

    public function testIntersectReturnsCollectionContainingOnlyIntersectingItems()
    {
        $arr = $this->getFixture('array');
        $col = new Collection($arr);

        $this->assertSame([0 => 'first'], $col->intersect(['b', 'first', 'SECOND', '0'])->toArray(), "Ensure diff accepts array");
    }

    public function testKIntersectReturnsCollectionContainingOnlyIntersectingItemsByKey()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertSame(['1st' => 'first', '2nd' => 'second'], $col->kintersect(['1st' => 'foorst', '2nd' => 'SECOND!', '4th' => 'fourth'])->toArray(), "Ensure kdiff accepts array");
    }

    public function testPopRemovesLastElementAndReturnsIt()
    {
        $arr = $this->getFixture('array');
        $col = new Collection($arr);

        $this->assertSame(['first','second','third'], $col->toArray());
        $this->assertEquals('third', $col->pop());
        // note: pop() does not require re-index for numerically indexed arrays
        $this->assertSame(['first','second'], $col->toArray());
    }

    public function testShiftReindexesNumericallyIndexedCollections()
    {
        $arr = $this->getFixture('array');
        $col = new Collection($arr);

        $this->assertSame(['first','second','third'], $col->toArray());
        $this->assertEquals('first', $col->shift());
        // note: shift() requires re-index for numerically indexed arrays
        $this->assertSame([0=>'second',1=>'third'], $col->toArray(), "Ensure shift() causes re-index for numerically indexed collections");
    }

    public function testShiftRemovesFirstElementAndReturnsItAndDoesntReindexNonnumericallyIndexedCollections()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertSame(['1st' => 'first','2nd' => 'second','3rd' => 'third'], $col->toArray());
        $this->assertEquals('first', $col->shift());
        // note: shift() requires re-index for numerically indexed arrays
        $this->assertSame(['2nd' => 'second','3rd' => 'third'], $col->toArray(), "Ensure shift() doesn't cause re-index for non-numerically indexed collections");
    }

    public function testPushWorksJustLikeAdd()
    {
        $arr = $this->getFixture('array');
        $col = new Collection($arr);

        $this->assertSame(['first','second','third'], $arr);
        $this->assertSame($col, $col->push('fourth'));
        $this->assertSame(['first','second','third','fourth'], $col->toArray());
    }

    public function testUnshiftAddsItemToBeginningOfCollection()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $this->assertSame(['1st' => 'first','2nd' => 'second','3rd' => 'third'], $col->toArray());
        $this->assertSame($col, $col->unshift('fourth'));
        $this->assertSame(['fourth','1st' => 'first','2nd' => 'second','3rd' => 'third'], $col->toArray());
    }

    public function testUnshiftAddsItemToBeginningOfCollectionAndReindexesIfNumericallyIndexedCollection()
    {
        $arr = $this->getFixture('array');
        $col = new Collection($arr);

        $this->assertSame(['first','second','third'], $col->toArray());
        $this->assertSame($col, $col->unshift('fourth'));
        $this->assertSame(['fourth','first','second','third'], $col->toArray());
    }

    public function testPadReturnsNewCollectionPaddedToSpecifiedLength()
    {
        $arr = $this->getFixture('array');
        $col = new Collection($arr);

        $padded = $col->pad(5);
        $this->assertNotSame($col, $padded, "Ensure pad returns new collection");
        $this->assertSame([
            'first',
            'second',
            'third',
            null,
            null
        ], $padded->toArray(), "Ensure positive pad adds to end");

        $padded = $padded->pad(-8);
        $this->assertSame([
            null,
            null,
            null,
            'first',
            'second',
            'third',
            null,
            null
        ], $padded->toArray(), "Ensure negative pad adds to beginning and reindexes if indexed numerically");
    }

    public function testPadPreservesKeysIfNotIndexedNumerically()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $padded = $col->pad(5);
        $this->assertSame([
            '1st' => 'first',
            '2nd' => 'second',
            '3rd' => 'third',
            null,
            null
        ], $padded->toArray(), "Ensure keys are preserved in non-numerically indexed arrays");

        $negpadded = $padded->pad(-8);
        $this->assertSame([
            null,
            null,
            null,
            '1st' => 'first',
            '2nd' => 'second',
            '3rd' => 'third',
            null,
            null
        ], $negpadded->toArray(), "Ensure negative pad adds to beginning and preserves associative keys");
    }

    public function testPadUsesProvidedValueToPadCollection()
    {
        $arr = $this->getFixture('assoc');
        $col = new Collection($arr);

        $padded = $col->pad(5, 'foo');
        $this->assertSame([
            '1st' => 'first',
            '2nd' => 'second',
            '3rd' => 'third',
            'foo',
            'foo'
        ], $padded->toArray());
        
        $negpadded = $padded->pad(-10, 'poo');
        $this->assertSame([
            'poo',
            'poo',
            'poo',
            'poo',
            'poo',
            '1st' => 'first',
            '2nd' => 'second',
            '3rd' => 'third',
            'foo',
            'foo'
        ], $negpadded->toArray());
    }

    public function testPartitionUsesCallbackToPartitionCollection()
    {
        $arr = [];
        $col = new Collection($arr);

        $partition = $col->partition(function($val, $key, $index) {
            // do nothing for now...
        });
        $this->assertCount(2, $partition);
        $this->assertInternalType('array', $partition);
        $this->assertContainsOnlyInstancesOf(Collection::class, $partition);
    }

    public function testPartitionPutsPassInFirstCollectionAndFailInSecondUsingCallback()
    {
        $arr = [1,5,10,-4,0,12,-100,-1,-3,-10];
        $col = new Collection($arr);

        list($neg, $pos) = $col->partition(function($val, $key, $index) {
            return $val < 0;
        });
        $this->assertSame([
            3 => -4,
            6 => -100,
            7 => -1,
            8 => -3,
            9 => -10
        ], $neg->toArray());
        $this->assertSame([
            0 => 1,
            1 => 5,
            2 => 10,
            4 => 0,
            5 => 12
        ], $pos->toArray());

        list($even, $odd) = $col->partition(function($val, $key, $index) {
            return $key % 2 == 0;
        });
        $this->assertSame([
            0 => 1,
            2 => 10,
            4 => 0,
            6 => -100,
            8 => -3,
        ], $even->toArray());
        $this->assertSame([
            1 => 5,
            3 => -4,
            5 => 12,
            7 => -1,
            9 => -10
        ], $odd->toArray());

        list($first, $rest) = $col->partition(function($val, $key, $index) {
            return !$index;
        });
        $this->assertSame([1,], $first->toArray());
        $this->assertSame([
            1=>5,
            2=>10,
            3=>-4,
            4=>0,
            5=>12,
            6=>-100,
            7=>-1,
            8=>-3,
            9=>-10
        ], $rest->toArray());
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

    public function testGetColumnOnNonTabularDataDoesTheBestItCan()
    {
        $onedim = [1,2,3,4,5];
        $nontable = [
            'foo',
            1,
            'boo' => 'far',
            [1,2,3,4,5],
            'foo' => ['foo' => 'bar', 'boo' => 'far'],
            [1 => 2, 3 => 4, 5 => 6],
            ['foo' => 'gar', 'boo' => 'rar'],
            'FOOBAR!',
            ['goo' => 'nar', 'boo' => 'har', 'foo' => 'dar', 'doo' => 'lar']
        ];
        $c1d = new Collection($onedim);
        $col = new Collection($nontable);
        $this->assertSame(['bar', 'gar', 'dar'], $col->getColumn('foo')->toArray());
        $this->assertSame([2, 2], $col->getColumn(1)->toArray());
        $this->assertSame([], $c1d->getColumn(1)->toArray());
    }

    public function testGetColumnWorksOnThreeDimensionalOrMore()
    {
        $threedim = [
            ['foo' => [1,2,3], 'bar' => ['one' => 1, 'two' => 2]],
            ['foo' => [3,2,1], 'bar' => ['two' => 2, 'three' => 3]],
            ['foo' => [4,5,6], 'bar' => ['four' => 4, 'two' => 'two']],
        ];
        $col = new Collection($threedim);
        $this->assertSame([[1,2,3], [3,2,1], [4,5,6]], $col->getColumn('foo')->toArray());
    }

    public function testIsTabularChecksIfCollectionIsTwoDimensionalWithConsistentKeys()
    {
        // minimum required to be a tabular collection
        $mintabular = [
            [],
        ];
        $tabular1 = $this->getTestTable();
        $tabular2 = [
            [1,2,3],
            [4,5,6],
            [7,8,9]
        ];
        $tabular3 = [
            ['one' => 1],
            ['one' => 2]
        ];
        // it is okay for tabular data to contain complex data
        $tabular4 = [
            ['one' => [1,2,3], 'two' => [1,2,3,4,5]],
            ['one' => 2, 'two' => 1],
            ['one' => true, 'two' => false],
            ['one' => new \stdClass, 'two' => new \stdClass]
        ];

        $untabular1 = [
            ['one' => 1],
            ['two' => 2]
        ];
        $untabular2 = [1,2,3,4];
        $untabular3 = [
            [1,2,3,4],
            [1,2,3,4,5]
        ];

        $this->assertTrue(Collection::factory($mintabular)->isTabular());
        $this->assertTrue(Collection::factory($tabular1)->isTabular());
        $this->assertTrue(Collection::factory($tabular2)->isTabular());
        $this->assertTrue(Collection::factory($tabular3)->isTabular());
        $this->assertTrue(Collection::factory($tabular4)->isTabular());

        $this->assertFalse(Collection::factory($untabular1)->isTabular());
        $this->assertFalse(Collection::factory($untabular2)->isTabular());
        $this->assertFalse(Collection::factory($untabular3)->isTabular());
    }

}