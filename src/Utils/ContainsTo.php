<?php

namespace Xofttion\SOA\Utils;

class ContainsTo extends Aggregation {
    
    // Constructor de la clase ContainsTo
    
    /**
     * 
     * @param string $class
     */
    public function __construct(string $class) {
        parent::__construct($class, true, false, false, false, null);
    }
}