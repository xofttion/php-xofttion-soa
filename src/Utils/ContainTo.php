<?php

namespace Xofttion\SOA\Utils;

class ContainTo extends Aggregation {
    
    // Constructor de la clase ContainTo
    
    /**
     * 
     * @param string $class
     */
    public function __construct(string $class) {
        parent::__construct($class, false, false, false, false, null);
    }
}