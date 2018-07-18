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
             Noz\to_array,
             Noz\to_numeric;

/**
 * Nozavroni Collection
 *
 * Basically an array wrapper with a bunch of super useful methods for working with its items and/or create new collections from its items.
 *
 * @note None of the methods in this class have a $preserveKeys param. That is by design. I don't think it's necessary.
 *       Instead, keys are ALWAYS preserved and if you want to NOT preserve keys, simply call Collection::values().
 *
 * @note The signature for callbacks throughout this class, unless otherwise stated, will be:
 *       (mixed $value, mixed $key, int $index), where $index will be simply a numeric value starting at zero, that is
 *       incremented by one for each successive call to the callback. The other two arguments should be obvious. The
 *       expected return value will depend on the method for which it is being used.
 */
class Collection implements ArrayAccess, Iterator, Countable, JsonSerializable
{
    /** @var array The items for this collection */
    protected $items;

    /**
     * Collection constructor.
     * 
     * Although most methods in this class are more forgiving and accept anything that is traversable rather than
     * strictly an array, the constructor is an exception. It expects an array. If you have an Array-ish object and it 
     * is traversable, you may use the factory method instead to generate a collection from it.
     *
     * @param array $items The items to include in the collection
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
        $this->rewind();
    }

    /**
     * Generate a collection from any iterable
     *
     * This is the method used internally to generate new collections. This allows for this class to be extended if 
     * necessary. This way, the child class will use its own factory method to generate new collections (or otherwise 
     * use this one).
     *
     * @param array|Traversable $items The items to include in the collection
     *
     * @return Collection
     */
    public static function factory($items = null)
    {
        return new Collection(to_array($items, true));
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
     * @param mixed $key The key to check for
     *
     * @return bool
     */
    public function has($key)
    {
        return isset($this->items[$key]) || array_key_exists($key, $this->items);
    }

    /**
     * Determine if collection has a value at given position
     *
     * If the $position argument is positive, counting will start at the beginning and start from one (rather than zero).
     * If $position is negative, counting will start at the end and work backwards. This is not the same as array
     * indexing, as that begins from zero.
     *
     * @param int $position The numeric position to check for a value at
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
     * If the $position argument is positive, counting will start at the beginning and start from one (rather than zero).
     * If $position is negative, counting will start at the end and work backwards. If an item exists at the specified
     * position, its key will be returned. Otherwise a RuntimeException will be thrown.
     *
     * @param int $position The numeric position to get a key at
     *
     * @return mixed
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
     * If the $position argument is positive, counting will start at the beginning and start from one (rather than zero).
     * If $position is negative, counting will start at the end and work backwards. If an item exists at the specified
     * position, its value will be returned. Otherwise a RuntimeException will be thrown.
     *
     * @param int $position The numeric position to get a value at
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
     * Get the key of first item exactly equal to $item
     *
     * Searches the collection for an item exactly equal to $item, returning its key if found. If a callback is provided
     * rather than a value, it will be passed the conventional three arguments ($value, $key, $index) and returning true
     * from this callback would be considered a "match". If no match is found, a RuntimeException will be thrown.
     *
     * @param mixed|callable $item The value to look for or a callback
     *
     * @throws RuntimeException
     *
     * @return mixed
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

        throw new RuntimeException("Could not find item equal to '{$item}'");
    }

    /**
     * Get the numeric index of first item exactly equal to $item
     *
     * Searches the collection for an item exactly equal to $item, returning its numeric index if found. If a callback
     * is provided rather than a value, it will be passed the conventional three arguments ($value, $key, $index) and
     * returning true from this callback would be considered a "match". If no match is found, a RuntimeException will be
     * thrown.
     *
     * @param mixed|callable $item The value to look for or a callback
     *
     * @throws RuntimeException
     *
     * @return int
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

        throw new RuntimeException("Could not find item equal to '{$item}'");
    }

    /**
     * Get item by key
     *
     * Fetches an item from the collection by key. If no item is found with the given key, a default may be provided as
     * the second argument. If no default is provided, null will be returned instead.
     *
     * @param mixed $key The key of the item you want returned
     * @param mixed $default A default value to return if key does not exist
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
     * Assign a value to the given key
     *
     * Sets the specified key to the specified value. By default the key will be overwritten if it already exists, but
     * this behavior may be changed by setting the third parameter ($overwrite) to false.
     *
     * @param mixed $key The key to assign a value to
     * @param mixed $value The value to assign to $key
     * @param bool $overwrite Whether to overwrite existing values (default is true)
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
     * Remove the item at the given key from the collection.
     *
     * @param mixed $key The key of the item to remove
     *
     * @return self
     */
    public function delete($key)
    {
        unset($this->items[$key]);

        return $this;
    }

    /**
     * Clear (remove) all items from the collection.
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
     * Checks the collection for an item exactly equal to $value. If $value is a callback function, it will be passed
     * the typical arguments ($value, $key, $index) and a true return value will count as a match.
     *
     * If $key argument is provided, key must match it as well. By default key is not required.
     *
     * @param mixed|callable $value The value to check for or a callback function
     * @param mixed $key The key to check for in addition to the value (optional)
     *
     * @return bool
     */
    public function contains($value, $key = null)
    {
        $index = 0;
        foreach ($this as $k => $v) {
            $matchkey = is_null($key) || $key === $k;
            if (is_callable($value)) {
                if ($value($v, $k, $index)) {
                    return $matchkey;
                }
            } else {
                if ($value === $v) {
                    return $matchkey;
                }
            }
            $index++;
        }
        return false;
    }

    /**
     * Pull an item out of the collection and return it
     *
     * @param mixed $key The key whose value should be removed and returned
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
     * Similar to implode() or join(), this method will attempt to return every item in the collection  delimited
     * (separated) by the specified character(s).
     *
     * @param string $delim The character(s) to delimit (separate) the results with
     *
     * @return string
     */
    public function join($delim = '')
    {
        return implode($delim, $this->items);
    }

    /**
     * Determine if collection is empty (has no items)
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->count() == 0;
    }

    /**
     * Get new collection with only values
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
     * Get new collection with only keys
     *
     * Return a new collection with only the current collection's keys as its values.
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
        return static::factory(array_reverse($this->items, true));
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
        $new = [];
        $keys = array_keys($this->items);
        shuffle($keys);
        foreach ($keys as $key) {
            $new[$key] = $this->items[$key];
        }
        $this->items = $new;

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
     * Sort the collection by value (in-place)
     *
     * Sorts the collection by value using the provided algorithm (which can be either the name of a native php function
     * or a callable).
     *
     * @note The sorting methods are exceptions to the usual callback signature. The callback for this method accepts
     *       the standard arguments for sorting algorithms ( string $str1 , string $str2 ) and should return an integer.
     *
     * @see http://php.net/manual/en/function.strcmp.php
     *
     * @param callable $alg The sorting algorithm (defaults to strcmp)
     *
     * @return self
     */
    public function sort(callable $alg = null)
    {
        if (is_null($alg)) {
            $flag = $this->assert('Noz\is_numeric') ? SORT_NUMERIC : SORT_NATURAL;
            asort($this->items, $flag);
        } else {
            uasort($this->items, $alg);
        }

        return $this;
    }

    /**
     * Sort the collection by key (in-place)
     *
     * Sorts the collection by key using the provided algorithm (which can be either the name of a native php function
     * or a callable).
     *
     * @note The sorting methods are exceptions to the usual callback signature. The callback for this method accepts
     *       the standard arguments for sorting algorithms ( string $str1 , string $str2 ) and should return an integer.
     *
     * @see http://php.net/manual/en/function.strcmp.php
     *
     * @param callable $alg The sorting algorithm (defaults to strcmp)
     *
     * @return self
     */
    public function ksort(callable $alg = null)
    {
        if (is_null($alg)) {
            $flag = $this->keys()->assert('Noz\is_numeric') ? SORT_NUMERIC : SORT_NATURAL;
            ksort($this->items, $flag);
        } else {
            uksort($this->items, $alg);
        }

        return $this;
    }

    /**
     * Append items to collection without regard to key
     *
     * Much like Collection::add(), except that it accepts multiple items to append rather than just one.
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
     * Returns the first item in the collection. If a callback is provided, it will accept the standard arguments
     * ($value, $key, $index) and returning true will be considered a "match".
     *
     * @param callable|null $callback A callback to compare items with (optional)
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
     * Returns the last item in the collection. If a callback is provided, it will accept the standard arguments
     * ($value, $key, $index) and returning true will be considered a "match".
     *
     * @param callable|null $callback A callback to compare items with (optional)
     *
     * @return mixed|null
     */
    public function last(callable $callback = null)
    {
        return $this->reverse()->first($callback);
    }

    /**
     * Create a new collection by applying a callback to each item in the collection
     *
     * The callback for this method should accept the standard arguments ($value, $key, $index). It will be called once
     * for every item in the collection and a new collection will be created with the results.
     *
     * @note It is worth noting that keys will be preserved in the resulting collection, so if you do not want this
     *       behavior, simply call values() on the resulting collection and it will be indexed numerically.
     *
     * @param callable $callback A callback that is applied to every item in the collection
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
     * Combine collection with another collection/array/traversable
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
     * Create new collection with specified keys
     *
     * A new collection is created using this collection's values and the provided $keys (the opposite of combine)
     *
     * @param array|Traversable $keys A new set of keys
     *
     * @return Collection
     */
    public function rekey($keys)
    {
        if (!is_traversable($keys)) {
            throw new RuntimeException("Invalid input type for " . __METHOD__ . ", must be array or Traversable");
        }

        $keys = to_array($keys);
        if (count($keys) != count($this->items)) {
            throw new RuntimeException("Invalid input for " . __METHOD__ . ", number of items does not match");
        }

        return static::factory(array_combine($keys, $this->items));
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
     * Get frequency of each distinct item in collection
     *
     * Returns a new collection with each distinct scalar value converted to a string as its keys and the number if
     * times it occurs in the collection (its frequency) as its values. Non-scalar values will simply be discarded.
     *
     * @return Collection
     */
    public function frequency()
    {
        return $this->fold(function(Collection $freq, $val) {
            if (is_scalar($val)) {
                $str = (string) $val;
                if (!isset($freq[$str])) {
                    $freq[$str] = 0;
                }
                $freq[$str] += 1;
            }
            return $freq;
        }, new Collection);
    }

    /**
     * Get new collection with only filtered values
     *
     * Loops through every item in the collection, applying the given callback and creating a new collection with only
     * those items which return true from the callback. The callback should accept the standard arguments
     * ($value, $key, $index). If no callback is provided, items with "truthy" values will be kept.
     *
     * @param callable $callback A callback function used to determine which items are kept (optional)
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
     * Apply a callback function to each item in the collection, passing the result to the next call until only a single
     * value remains. The arguments provided to this callback are ($folded, $val, $key, $index) where $folded is the
     * result of the previous call (or if the first call it is equal to the $initial param).
     *
     * @param callable $callback The callback function used to "fold" or "reduce" the collection into a single value
     * @param mixed $initial The (optional) initial value to pass to the callback
     *
     * @return mixed
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
     * Fold collection into a single value (in opposite direction of fold)
     *
     * Folds/reduces a collection in the same way as fold(), except that it starts from the end and works backwards.
     *
     * @param callable $callback The callback function used to "fold" or "reduce" the collection into a single value
     * @param mixed $initial The (optional) initial value to pass to the callback
     *
     * @return mixed
     */
    public function foldr(callable $callback, $initial = null)
    {
        return $this->reverse()->fold($callback, $initial);
    }

    /**
     * Create a new collection by looping over this one
     *
     * Behaves in much the same way as fold, except that the accumulator is automatically a new collection which is
     * ultimately returned.
     *
     * @param callable $callback The callback used to create the new collection
     *
     * @return Collection
     */
    public function recollect(callable $callback)
    {
        return $this->fold($callback, static::factory());
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
     * Apply a callback function to each item in the collection passively
     *
     * To stop looping through the items in the collection, return false from the callback.
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
     * @param callable $callback The callback function (passed only one arg, the collection itself)
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
     * Zip together any number of arrays/traversables
     *
     * Merges together the values from this collection with the values of each of the provided traversables at the
     * corresponding index. So [1,2,3] + [4,5,6] + [7,8,9] would end up [[1,4,7], [2,5,8], [3,6,9]].
     *
     * @param array|Traversable ...$items The collections/arrays to zip
     *
     * @return Collection
     */
    public function zip(...$items)
    {
        $args = [null, $this->items];
        foreach ($items as $x) {
            $args[] = to_array($x);
        }
        return static::factory(call_user_func_array('array_map', $args));
    }

    /**
     * Get every n-th item from the collection
     *
     * @param int $n Get every $n-th item
     */
    public function nth($n)
    {
        return $this->filter(function($val, $key, $index) use ($n) {
            return ($index+1) % $n == 0;
        });
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
     * with the list() function. ( `list($a, $b) = $col->partition(function($val, $key, $index) {})` )
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
     * Get sum of all numeric items
     *
     * Returns the sum of all numeric items in the collection, silently ignoring any non-numeric values.
     *
     * @return float|int
     */
    public function sum()
    {
        return $this->fold(function($accum, $val) {
            return is_numeric($val) ? $accum + $val : $accum;
        }, 0);
    }

    /**
     * Get product of all numeric items
     *
     * Returns the product of all numeric items in the collection, silently ignoring any non-numeric values.
     *
     * @return float|int
     */
    public function product()
    {
        if ($this->isEmpty()) {
            return 0;
        }
        return $this->fold(function($accum, $val) {
            return is_numeric($val) ? $accum * $val : $accum;
        }, 1);
    }

    /**
     * Get average of all numeric items
     *
     * Returns the average of all numeric items in the collection, silently ignoring any non-numeric values.
     *
     * @return float|int
     */
    public function average()
    {
        $numeric = $this->filter('Noz\is_numeric');
        if (!$count = $numeric->count()) {
            return 0;
        }
        return $numeric->sum() / $count;
    }

    /**
     * Get the median numeric value
     *
     * Returns the median of all numeric items in the collection, silently ignoring any non-numeric values.
     *
     * @return float|int
     */
    public function median()
    {
        $numeric = $this->filter('Noz\is_numeric')->sort();
        if (!$count = $numeric->count()) {
            return 0;
        }
        $pos = ($count + 1) / 2;
        if (!is_int($pos)) {
            return ($numeric->getValueAt(floor($pos)) + $numeric->getValueAt(ceil($pos))) / 2;
        }
        return to_numeric($numeric->getValueAt($pos));
    }

    /**
     * Get the mode numeric value
     *
     * Returns the mode of all numeric items in the collection, silently ignoring any non-numeric values.
     *
     * @return float|int
     */
    public function mode()
    {
        $mode = $this->filter('Noz\is_numeric')
            ->frequency()
            ->sort()
            ->keys()
            ->pop();

        return to_numeric($mode);
    }

    /**
     * Get maximum numeric value from collection
     *
     * Returns the max of all numeric items in the collection, silently ignoring any non-numeric values.
     *
     * @return float|int
     */
    public function max()
    {
        return to_numeric(max($this->items));
    }

    /**
     * Get minimum numeric value from collection
     *
     * Returns the min of all numeric items in the collection, silently ignoring any non-numeric values.
     *
     * @return float|int
     */
    public function min()
    {
        return to_numeric(min($this->items));
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
     * @ignore
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
     * @ignore
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
     * @ignore
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
     * @ignore
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
     * @ignore
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