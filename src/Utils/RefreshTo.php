<?php

namespace Xofttion\SOA\Utils;

class RefreshTo extends Aggregation
{

    // Constructor de la clase RefreshTo

    /**
     * 
     * @param string $class
     */
    public function __construct(string $class)
    {
        parent::__construct($class, false, false, true, false, null);
    }
}
