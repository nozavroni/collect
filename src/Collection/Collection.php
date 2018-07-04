<?php
/**
 * nozavroni/collect
 *
 * This is a basic utility library for PHP5.6+ with special emphesis on Collections.
 *
 * @author Luke Visinoni <luke.visinoni@gmail.com>
 * @copyright (c) 2018 Luke Visinoni <luke.visinoni@gmail.com>
 * @license MIT (see LICENSE file)
 */
namespace Noz\Collection;

use Countable;
use JsonSerializable;
use Iterator;
use ArrayAccess;
use RuntimeException;
use Traversable;

use function Noz\is_traversable,
             Noz\to_array;

/**
 * Nozavroni Collection
 *
 * Basically an array wrapper with a bunch of super useful methods for working with its items and/or create new collections from its items.
 *
 * @note None of the methods in this class have a $preserveKeys param. That is by design. I don't think it's necessary.
 *       Instead, keys are ALWAYS preserved and if you want to NOT preserve keys, simply call Collection::values().
 * @note The majority, if not all callback methods in this library accept three arguments; key, value, and index (which
 *       is simply an incrementing value starting at zero).
 */
class Collection implements ArrayAccess, Iterator, Countable, JsonSerializable
{
    /** @var array The items for this collection */
    protected $items;

    /**
     * Collection constructor.
     *
     * @param array $items The items in the collection
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
     * @param mixed $items The items in the collection
     *
     * @return Collection
     */
    public static function factory($items = null)
    {
        if (is_null($items)) {
            $items = [];
        }
        return new Collection(to_array($items));
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
     *
     *
     * @param int $position Can be positive and start from the beginning or it can be negative and start from the end.
     *                      Indexed from one (rather than zero).
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
     * Returns the key at the given position, starting from one.
     *
     * If the position does not exist, a RuntimeException is thrown.
     *
     * @param int $position Can be positive and start from the beginning or it can be negative and start from the end.
     *                      Indexed from one (rather than zero).
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
     * Returns the value at the given position, starting from one.
     *
     * If the position does not exist, a RuntimeException is thrown.
     *
     * @param int $position Can be positive and start from the beginning or it can be negative and start from the end.
     *                      Indexed from one (rather than zero).
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
     * Get the key of the first item found matching $item
     *
     * @param mixed|callable $item The item value to look for
     *
     * @return mixed|null
     */
    public function keyOf($item)
    {
        $index = 0;
        foreach ($this as $key => $val) {
            if (is_callable($item)) {
                if ($item($val, $key, $index++)) {
                    return $key;
                }
            } elseif ($item === $val) {
                return $key;
            }
        }

        throw new RuntimeException("No item found at given key: {$item}");
    }

    /**
     * Get the offset (index) of the first item found that matches $item
     *
     * @param mixed|callable $item The item value to look for
     *
     * @return int|null
     */
    public function indexOf($item)
    {
        $index = 0;
        foreach ($this as $key => $val) {
            if (is_callable($item)) {
                if ($item($val, $key, $index)) {
                    return $index;
                }
            } else {
                if ($item === $val) {
                    return $index;
                }
            }
            $index++;
        }

        throw new RuntimeException("No key found for given item: {$item}");
    }

    /**
     * Get item by key, with an optional default return value
     *
     * @param mixed $key The key of the item you want
     * @param mixed $default A default to return if key does not exist
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
     * Simply adds a value at the end of the collection. A numeric index will be created automatically.
     *
     * @param mixed $value The value to add to the collection
     *
     * @return self
     */
    public function add($value)
    {
        $this->items[] = $value;

        return $this;
    }

    /**
     * Set an item at a given key
     *
     * Sets the specified key to the specified value. By default the key will be overwritten if it already exists, but
     * this behavior may be changed by setting the third parameter ($overwrite) to false.
     *
     * @param mixed $key The desired key
     * @param mixed $value The desired value to set the key to
     * @param bool  $overwrite If false, do not overwrite existing key
     *
     * @return self
     */
    public function set($key, $value, $overwrite = true)
    {
        if ($overwrite || !$this->has($key)) {
            $this->items[$key] = $value;
        }

        return $this;
    }

    /**
     * Delete an item by key
     *
     * @param mixed $key The key whose value you want to delete
     *
     * @return self
     */
    public function delete($key)
    {
        unset($this->items[$key]);

        return $this;
    }

    /**
     * Clear the collection of all its items.
     *
     * @return self
     */
    public function clear()
    {
        $this->items = [];

        return $this;
    }

    /**
     * Determine if collection contains given value
     *
     * Used to determine if the collection contains a given value. Or, for more complex use cases, a callback may be
     * provided in place of a value, which will be used to determine a match.
     *
     * You may also provide a key as the second argument if you wish to match a key as well.
     *
     * @param mixed|callable $val The value to search for or a callback used to determine a match
     * @param mixed $key The (optional) key to match
     *
     * @return bool
     */
    public function contains($val, $key = null)
    {
        $index = 0;
        foreach ($this as $k => $v) {
            $matchkey = is_null($key) || $key === $k;
            if (is_callable($val)) {
                if ($val($v, $k, $index)) {
                    return $matchkey;
                }
            } else {
                if ($val === $v) {
                    return $matchkey;
                }
            }
            $index++;
        }
        return false;
    }

    /**
     * Fetch item by key and remove it from the collection
     *
     * @param mixed $key The keyof the value you would like to pull
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
     * @param string $delim The character(s) to delimit the results with
     *
     * @return string
     */
    public function join($delim = '')
    {
        return implode($delim, $this->items);
    }

    /**
     * Determine if collection is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->count() == 0;
    }

    /**
     * Get only collection's values
     *
     * Return a new collection with only the current collection's values. The keys will be indexed numerically from zero
     *
     * @return Collection
     */
    public function values()
    {
        return static::factory(array_values($this->items));
    }

    /**
     * Get only collection's keys
     *
     * Return a new collection with only the current collection's keys as its values. The new collection's keys will be
     * indexed numerically from zero.
     *
     * @return Collection
     */
    public function keys()
    {
        return static::factory(array_keys($this->items));
    }

    /**
     * Get a collection of key/value pairs
     *
     * Returns a new collection containing arrays of key/value pairs in the format [key, value].
     *
     * @return Collection
     */
    public function pairs()
    {
        return $this->map(function($val, $key) {
            return [$key, $val];
        })->values();
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
     * Get a collection with keys and values flipped.
     *
     * Returns a new collection containing the keys as values and the values as keys.
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
     * Shuffle (randomize) the order of this collection's values (in-place)
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
     * Sort the collection's values (in-place)
     *
     * Sorts the collection by value using the provided algorithm (which can be either the name of a native php function
     * or a callable).
     *
     * @param callable $alg The sorting algorithm (defaults to strcmp)
     *
     * @return self
     */
    public function sort(callable $alg = null)
    {
        if (is_null($alg)) {
            // case-sensitive string comparison is the default sorting mechanism
            $alg = 'strcmp';
        }
        uasort($this->items, $alg);

        return $this;
    }

    /**
     * Sort the collection's keys (in-place)
     *
     * Sorts the collection by key using the provided algorithm (which can be either the name of a native php function
     * or a callable).
     *
     * @param callable $alg The sorting algorithm (defaults to strcmp)
     *
     * @return self
     */
    public function ksort(callable $alg = null)
    {
        if (is_null($alg)) {
            // case-sensitive string comparison is the default sorting mechanism
            $alg = 'strcmp';
        }
        uksort($this->items, $alg);

        return $this;
    }

    /**
     * Append items to collection without regard to key
     *
     * @param array|Traversable $items A list of values to append to the collection
     *
     * @return self
     */
    public function append($items)
    {
        if (!is_traversable($items)) {
            throw new RuntimeException("Invalid input type for " . __METHOD__ . ", must be array or Traversable");
        }

        foreach ($items as $val) {
            $this->add($val);
        }

        return $this;
    }

    /**
     * Return first item or first item where callback returns true
     *
     * Returns the first item in the collection or, if passed a callback, the first item that causes the callback to
     * return true.
     *
     * @param callable|null $callback A callback to compare items with
     *
     * @return mixed|null
     */
    public function first(callable $callback = null)
    {
        $index = 0;
        foreach ($this as $key => $val) {
            if (is_null($callback) || $callback($val, $key, $index++)) {
                return $val;
            }
        }

        return null;
    }

    /**
     * Return last item or last item where callback returns true
     *
     * Returns the last item in the collection or, if passed a callback, the last item that causes the callback to
     * return true.
     *
     * @param callable|null $callback A callback to compare items with
     *
     * @return mixed|null
     */
    public function last(callable $callback = null)
    {
        return $this->reverse()->first($callback);
    }

    /**
     * Map collection
     *
     * Create a new collection using the results of a callback function on each item in this collection.
     *
     * @param callable $callback A callback to generate the new values
     *
     * @return Collection
     */
    public function map(callable $callback)
    {
        $collection = static::factory();

        $index = 0;
        foreach ($this as $key => $val) {
            $collection->set($key, $callback($val, $key, $index++));
        }

        return $collection;
    }

    /**
     * Combine collection with another traversable/collection
     *
     * Using this collection's keys, and the incoming collection's values, a new collection is created and returned.
     *
     * @param array|Traversable $items The values to combine with this collection's keys
     *
     * @return Collection
     */
    public function combine($items)
    {
        if (!is_traversable($items)) {
            throw new RuntimeException("Invalid input type for " . __METHOD__ . ", must be array or Traversable");
        }

        $items = to_array($items);
        if (count($items) != count($this->items)) {
            throw new RuntimeException("Invalid input for " . __METHOD__ . ", number of items does not match");
        }

        return static::factory(array_combine($this->items, $items));
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
     * Remove all duplicate values from collection (in-place)
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
     * @param callable $callback The callback used to filter with
     *
     * @return Collection
     */
    public function filter(callable $callback = null)
    {
        $collection = static::factory();
        $index = 0;
        foreach ($this as $key => $value) {
            if (is_null($callback)) {
                if ($value) {
                    $collection->set($key, $value);
                }
            } else {
                if ($callback($value, $key, $index++)) {
                    $collection->set($key, $value);
                }
            }
        }

        return $collection;
    }

    /**
     * Fold collection into a single value (a.k.a. reduce)
     *
     * Loop through collection calling a callback function and passing the result to the next iteration, eventually
     * returning a single value.
     *
     * @param callable $callback The callback used to "fold" or "reduce" the collection into a single value
     * @param mixed $initial The (optional) initial value to pass to the callback
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
     * Returns a new collection with a merge of this collection and $items. Values from $items will overwrite values in
     * the current collection.
     *
     * @param array|Traversable $items The items to merge with the collection
     *
     * @return Collection
     */
    public function merge($items)
    {
        if (!is_traversable($items)) {
            throw new RuntimeException("Invalid input type for " . __METHOD__ . ", must be array or Traversable");
        }

        $collection = clone $this;
        foreach ($items as $key => $val) {
            $collection->set($key, $val);
        }

        return $collection;
    }

    /**
     * Create a new collection with a union of this collection and $items
     *
     * This method is similar to merge, except that existing values will not be overwritten.
     *
     * @param $items
     */
    public function union($items)
    {
        if (!is_traversable($items)) {
            throw new RuntimeException("Invalid input type for " . __METHOD__ . ", must be array or Traversable");
        }

        $collection = clone $this;
        foreach ($items as $key => $val) {
            $collection->set($key, $val, false);
        }

        return $collection;
    }

    /**
     * Call callback for each item in collection, passively
     *
     * If at any point the callback returns false, iteration stops.
     *
     * @param callable $callback The callback to use on each item in the collection
     *
     * @return self
     */
    public function each(callable $callback)
    {
        $index = 0;
        foreach ($this as $key => $val) {
            if ($callback($val, $key, $index++) === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * Assert callback returns $expected value for each item in collection.
     *
     * This method will loop over each item in the collection, passing them to the callback. If the callback doesn't
     * return $expected value for every item in the collection, it will return false.
     *
     * @param callable $callback Assertion callback
     * @param bool $expected Expected value from callback
     *
     * @return bool
     */
    public function assert(callable $callback, $expected = true)
    {
        $index = 0;
        foreach ($this as $key => $val) {
            if ($callback($val, $key, $index++) !== $expected) {
                return false;
            }
        }

        return true;
    }

    /**
     * Pipe collection through a callback
     *
     * Simply passes the collection as an argument to the given callback.
     *
     * @param callable $callback The callback function
     *
     * @return mixed
     */
    public function pipe(callable $callback)
    {
        return $callback($this);
    }

    /**
     * Get new collection in chunks of $size
     *
     * Creates a new collection of arrays of $size length. The remainder items will be placed at the end.
     *
     * @param int $size The size of the arrays you want returned
     *
     * @return Collection
     */
    public function chunk($size)
    {
        return static::factory(array_chunk($this->items, $size, true));
    }

    /**
     * Get a new collection of $count chunks
     *
     * Returns a collection of $count number of equally-sized arrays, placing remainders at the end.
     *
     * @param int $count The number of arrays you want returned
     *
     * @return Collection
     */
    public function split($count = 1)
    {
        return $this->chunk(ceil($this->count() / $count));
    }

    /**
     * Get a slice of this collection.
     *
     * Returns a collection with a slice of this collection's items, starting at $offset and continuing until $length
     *
     * @param int $offset The offset at which you want the slice to begin
     * @param int|null $length The length of the slice (number of items)
     *
     * @return Collection
     */
    public function slice($offset, $length = null)
    {
        return static::factory(array_slice($this->items, $offset, $length, true));
    }

    /**
     * Get collection with only differing items
     *
     * Returns a collection containing only the items not present in *both* this collection and $items.
     *
     * @param array|Traversable $items The items to compare with
     *
     * @return Collection
     */
    public function diff($items)
    {
        return static::factory(array_diff($this->items, to_array($items)));
    }

    /**
     * Get collection with only differing items (by key)
     *
     * Returns a collection containing only the values whose keys are not present in *both* this collection and $items.
     *
     * @param array|Traversable $items The items to compare with
     *
     * @return Collection
     */
    public function kdiff($items)
    {
        return static::factory(array_diff_key($this->items, to_array($items)));
    }

    /**
     * Get collection with only intersecting items
     *
     * Returns a collection containing only the values present in *both* this collection and $items
     *
     * @param array|Traversable $items The items to compare with
     *
     * @return Collection
     */
    public function intersect($items)
    {
        return static::factory(array_intersect($this->items, to_array($items)));
    }

    /**
     * Get collection with only intersecting items (by key)
     *
     * Returns a collection containing only the values whose keys are present in *both* this collection and $items
     *
     * @param array|Traversable $items The items to compare with
     *
     * @return Collection
     */
    public function kintersect($items)
    {
        return static::factory(array_intersect_key($this->items, to_array($items)));
    }

    /**
     * Remove last item in collection and return it
     *
     * @return mixed
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * Remove first item in collection and return it
     *
     * If the collection is numerically indexed, this method will re-index it from 0 after returning the item.
     *
     * @return mixed
     */
    public function shift()
    {
        return array_shift($this->items);
    }

    /**
     * Add item to the end of the collection
     *
     * @note This method is no different than add() but I included it for consistency's sake since I have the others
     *
     * @param mixed $item The item to add to the collection
     *
     * @return self
     */
    public function push($item)
    {
        return $this->add($item);
    }

    /**
     * Add item to the beginning of the collection
     *
     * The collection will be re-indexed if it has numeric keys.
     *
     * @param mixed $item The item to add to the collection
     *
     * @return self
     */
    public function unshift($item)
    {
        array_unshift($this->items, $item);

        return $this;
    }

    /**
     * Get new collection padded to specified $size with $value
     *
     * Using $value, pad the collection to specified $size. If $size is smaller or equal to the size of the collection,
     * then no padding takes place. If $size is positive, padding is added to the end, while if negative, padding will
     * be added to the beginning.
     *
     * @param int $size The number of items collection should have
     * @param mixed $value The value to pad with
     *
     * @return Collection
     */
    public function pad($size, $value = null)
    {
        $collection = clone $this;
        while ($collection->count() < abs($size)) {
            if ($size > 0) {
                $collection->add($value);
            } else {
                $collection->unshift($value);
            }
        }

        return $collection;
    }

    /**
     * Partition collection into two collections using a callback
     *
     * Iterates over each element in the collection with a callback. Items where callback returns true are placed in one
     * collection and the rest in another. Finally, the two collections are placed in an array and returned for easy use
     * with the list() function. ( list($a, $b) = $col->partition(function($val, $key, $index) {}) )
     *
     * @param callable $callback The comparison callback
     *
     * @return Collection[]
     */
    public function partition(callable $callback)
    {
        $pass = static::factory();
        $fail = static::factory();

        $index = 0;
        foreach ($this as $key => $val) {
            if ($callback($val, $key, $index++)) {
                $pass->set($key, $val);
            } else {
                $fail->set($key, $val);
            }
        }

        return [$pass, $fail];
    }

    /**
     * Get column values by key
     *
     * This method expects the collection's data to be tabular in nature (two-dimensional and for the rows to have
     * consistently named keys). If the data is not structured this way, it will do the best it can but it is not meant
     * for unstructured, non-tabular data so don't expect consistent results.
     *
     * @param string|int $column The key of the column you want to get
     *
     * @return Collection
     */
    public function getColumn($column)
    {
        return static::factory(array_column($this->items, $column));
    }

    /**
     * Is collection tabular?
     *
     * Returns true if the data in the collection is tabular in nature, meaning it is at least two-dimensional and each
     * row contains the same number of values with the same keys.
     *
     * @return bool
     */
    public function isTabular()
    {
        $first = $this->first();
        return $this->assert(function($row) use ($first) {
            if (!is_traversable(($first)) || !is_traversable($row)) {
                return false;
            }
            return Collection::factory($row)
                ->kdiff($first)
                ->isEmpty();
        });
    }

    /** ++++                  ++++ **/
    /** ++ Interface Compliance ++ **/
    /** ++++                  ++++ **/

    /**
     * JSON serialize
     *
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
     * Does offset exist?
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Get item at offset
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if (!$this->has($offset)) {
            throw new RuntimeException("Unknown offset: {$offset}");
        }

        return $this->get($offset);
    }

    /**
     * Unset item at offset
     *
     * @param mixed $offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->delete($offset);
    }

    /**
     * Set item at offset
     *
     * @param mixed $offset
     * @param mixed $value
     *
     * @return self
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
     * @ignore
     */
    public function current()
    {
        return current($this->items);
    }

    /**
     * @ignore
     */
    public function key()
    {
        return key($this->items);
    }

    /**
     * @ignore
     */
    public function next()
    {
        return next($this->items);
    }

    /**
     * @ignore
     */
    public function rewind()
    {
        reset($this->items);
    }

    /**
     * @ignore
     */
    public function valid()
    {
        return $this->has(key($this->items));
    }

    /** ++++                  ++++ **/
    /** ++   Countable Method   ++ **/
    /** ++++                  ++++ **/

    /**
     * Get number of items in the collection
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }
}