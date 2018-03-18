<?php
namespace Noz;

use Traversable;

function is_traversable($value)
{
    return is_array($value) || ($value instanceof Traversable);
}