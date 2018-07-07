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
namespace Noz;

use Noz\Collection\Collection;
use RuntimeException;
use Traversable;

/**
 * Is a value traversable?
 *
 * @param mixed $value
 *
 * @return bool
 */
function is_traversable($value)
{
    return is_array($value) || ($value instanceof Traversable);
}

/**
 * Convert $items to an array
 *
 * @param mixed $items The var to convert to an array
 * @param bool $force Force item to array no matter what?
 *
 * @throws RuntimeException if cannot be converted to an array
 *
 * @return array
 */
function to_array($items, $force = false)
{
    if (method_exists($items, 'toArray')) {
        return $items->toArray();
    } elseif (is_array($items)) {
        return $items;
    } elseif ($items instanceof Traversable) {
        return iterator_to_array($items);
    } elseif (is_string($items)) {
        $json = json_decode($items, true);
        if (is_array($json) && json_last_error() == JSON_ERROR_NONE) {
            return $json;
        }
    }

    if ($force) {
        return (array) $items;
    }

    throw new RuntimeException(__FUNCTION__ . " could not convert items to an array");
}

/**
 * Collection factory function
 *
 * @param array|Traversable $items
 *
 * @return Collection
 */
function collect($items)
{
    return Collection::factory($items);
}

/**
 * Assign a value to a variable if a condition is met
 *
 * @param mixed $var
 * @param mixed $value
 * @param bool|callable $condition
 *
 * @return bool
 */
function assign_if(&$var, $value, $condition)
{
    if (is_callable($condition)) {
        $condition = $condition($value);
    }
    if ($condition) {
        $var = $value;
    }
}

/**
 * Get numeric value of any variable
 *
 * Pass any string, int or float and this will convert it to its appropriate int or float value.
 *
 * @param mixed $val Variable you want the numeric value of
 *
 * @return int
 */
function to_numeric($val)
{
    if (is_numeric($val)) {
        return $val + 0;
    }
    return 0;
}
