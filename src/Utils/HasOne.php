<?php

namespace Xofttion\SOA\Utils;

class HasOne extends Aggregation
{

    // Constructor de la clase HasOne

    /**
     * 
     * @param string $class
     */
    public function __construct(string $class)
    {
        parent::__construct($class, false, true, false, false, null);
    }
}
