<?php

namespace Xofttion\SOA\Utils;

class RequiredTo extends Aggregation
{

    // Constructor de la clase RequiredTo

    /**
     * 
     * @param string $class
     * @param string $column
     */
    public function __construct(string $class, string $column)
    {
        parent::__construct($class, false, false, true, true, $column);
    }
}
