<?php
use Symfony\Component\VarDumper\VarDumper;

/**
 * Test functions
 */

function dd($data)
{
    VarDumper::dump($data);
}