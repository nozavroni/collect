<?php
namespace Noz\Collection;

use Countable;
use JsonSerializable;
use Iterator;
use ArrayAccess;
use RuntimeException;
use Traversable;

/**
 * Nozavroni Collection
 */
class Collection implements ArrayAccess, Iterator, Countable, JsonSerializable
{
    /** @var array */
    protected $items;

    /**
     * Collection constructor.
     *
     * @param array $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
        $this->rewind();
    }

    /**
     * Generate a collection from an array of items.
     * I created this method so that it's possible to extend a collection more easily.
     *
     * @param array $items
     *
     * @return Collection
     */
    public static function factory(array $items = [])
    {
        return new Collection($items);
    }

    /**
     * Get collection as an array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->items;
    }

    /**
     * Determine if collection has a given key
     *
     * @param mixed $key The key to look for
     *
     * @return bool
     */
    public function has($key)
    {
        return isset($this->items[$key]) || array_key_exists($key, $this->items);
    }

    /**
     * Does collection have item at position?
     *
     * Determine if collection has an item at a particular position (indexed from one).
     * Position can be positive and start from the beginning or it can be negative and
     * start from the end.
     *
     * @param int $position
     *
     * @return bool
     */
    public function hasValueAt($position)
    {
        try {
            $this->getKeyAt($position);
            return true;
        } catch (RuntimeException $e) {
            return false;
        }
    }

    /**
     * Get key at given position
     *
     * Returns the key at the given position, starting from one. Position can be positive (start from beginning) or
     * negative (start from the end).
     *
     * If the position does not exist, a RuntimeException is thrown.
     *
     * @param int $position
     *
     * @return string
     *
     * @throws RuntimeException
     */
    public function getKeyAt($position)
    {
        $collection = $this;
        if ($position < 0) {
            $collection = $this->reverse();
        }
        $i = 1;
        foreach ($collection as $key => $val) {
            if (abs($position) == $i++) {
                return $key;
            }
        }
        throw new RuntimeException("No key at position {$position}");
    }

    /**
     * Get value at given position
     *
     * Returns the value at the given position, starting from one. Position can be positive (start from beginning) or
     * negative (start from the end).
     *
     * If the position does not exist, a RuntimeException is thrown.
     *
     * @param int $position
     *
     * @return mixed
     *
     * @throws RuntimeException
     */
    public function getValueAt($position)
    {
        return $this->get($this->getKeyAt($position));
    }

    /**
     * Get item by key, with an optional default return value
     *
     * @param mixed $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->items[$key];
        }

        return $default;
    }

    /**
     * Add an item with no regard to key
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function add($value)
    {
        $this->items[] = $value;

        return $this;
    }

    /**
     * Set an item at a given key
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return $this
     */
    public function set($key, $value)
    {
        $this->items[$key] = $value;

        return $this;
    }

    /**
     * Delete an item by key
     *
     * @param mixed $key
     *
     * @return $this
     */
    public function delete($key)
    {
        unset($this->items[$key]);

        return $this;
    }

    /**
     * Clear the collection of all its items.
     *
     * @return $this
     */
    public function clear()
    {
        $this->items = [];

        return $this;
    }

    /**
     * Determine if collection contains given value
     *
     * @param mixed $val
     *
     * @return bool
     */
    public function contains($val)
    {
        return in_array($val, $this->items, true);
    }

    /**
     * Fetch item from collection by key and remove it from collection
     *
     * @param mixed $key
     *
     * @return mixed
     */
    public function pull($key)
    {
        if ($this->has($key)) {
            $value = $this->get($key);
            $this->delete($key);
            return $value;
        }
    }

    /**
     * Join collection items using a delimiter
     *
     * @param string $delim
     *
     * @return string
     */
    public function join($delim = '')
    {
        return implode($delim, $this->items);
    }

    /**
     * Determine if collection has any items
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->count() == 0;
    }

    /**
     * Get a collection of only this collection's values (without its keys)
     *
     * @return Collection
     */
    public function values()
    {
        return static::factory(array_values($this->items));
    }

    /**
     * Get a collection of only this collection's keys
     *
     * @return Collection
     */
    public function keys()
    {
        return static::factory(array_keys($this->items));
    }

    /**
     * Get a collection with order reversed
     *
     * @return Collection
     */
    public function reverse()
    {
        return static::factory(array_reverse($this->items));
    }

    /**
     * Get a collection with keys and values flipped
     *
     * @return Collection
     */
    public function flip()
    {
        $collection = static::factory();
        foreach ($this as $key => $val) {
            $collection->set($val, $key);
        }
        return $collection;
    }

    /**
     * Shuffle the order of this collection's values
     *
     * @return Collection
     */
    public function shuffle()
    {
        shuffle($this->items);
        return $this;
    }

    /**
     * Get a random value from the collection
     *
     * @return mixed
     */
    public function random()
    {
        return $this->getValueAt(rand(1, $this->count()));
    }

    /**
     * Sort the collection
     *
     * @param mixed $algo
     *
     * @return Collection
     */
    public function sort($algo = null)
    {

    }

    /**
     * Get a new collection with only distinct values
     *
     * @return Collection
     */
    public function distinct()
    {
        $collection = static::factory();
        foreach ($this as $key => $val) {
            if (!$collection->contains($val)) {
                $collection->set($key, $val);
            }
        }
        return $collection;
    }

    /**
     * Remove all duplicate values from collection in-place
     *
     * @return Collection
     */
    public function deduplicate()
    {
        $this->items = array_unique($this->items);

        return $this;
    }

    /**
     * Return a new collection with only filtered keys/values
     *
     * The callback accepts value, key, index and should return true if the item should be added to the returned
     * collection
     *
     * @param callable $callback
     *
     * @return Collection
     */
    public function filter(callable $callback)
    {
        $collection = static::factory();
        $index = 0;
        foreach ($this as $key => $value) {
            if ($callback($value, $key, $index++)) {
                $collection->set($key, $value);
            }
        }

        return $collection;
    }

    /**
     * Fold collection into a single value
     *
     * Loop through collection calling a callback function and passing the result to the next iteration, eventually
     * returning a single value.
     *
     * @param callable $callback
     * @param mixed $initial
     *
     * @return null
     */
    public function fold(callable $callback, $initial = null)
    {
        $index = 0;
        $folded = $initial;
        foreach ($this as $key => $val) {
            $folded = $callback($folded, $val, $key, $index++);
        }
        return $folded;
    }

    /**
     * Return a merge of this collection and $items
     *
     * @param array|Traversable $items
     *
     * @return Collection
     */
    public function merge($items)
    {
        if (!is_array($items) && !($items instanceof Traversable)) {
            throw new RuntimeException("Invalid input type for merge, must be array or Traversable");
        }

        $collection = clone $this;
        foreach ($items as $key => $val) {
            $collection->set($key, $val);
        }
        return $collection;
    }



    /** ++++                  ++++ **/
    /** ++ Interface Compliance ++ **/
    /** ++++                  ++++ **/

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /** ++++                  ++++ **/
    /** ++ Array Access Methods ++ **/
    /** ++++                  ++++ **/

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        if (!$this->has($offset)) {
            throw new RuntimeException("Unknown offset: {$offset}");
        }

        return $this->get($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset)
    {
        $this->delete($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value)
    {
        if (!isset($offset)) {
            $this->add($value);
        }

        $this->set($offset, $value);
    }

    /** ++++                  ++++ **/
    /** ++   Iterator Methods   ++ **/
    /** ++++                  ++++ **/

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return current($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        return key($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function next()
    {
        return next($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        reset($this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function valid()
    {
        return $this->has(key($this->items));
    }

    /** ++++                  ++++ **/
    /** ++   Countable Method   ++ **/
    /** ++++                  ++++ **/

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return count($this->items);
    }
}