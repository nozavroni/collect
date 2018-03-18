<?php
namespace Noz\Tests;

use ArrayIterator;
use function Noz\is_traversable;
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
}