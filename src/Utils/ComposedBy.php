<?php

namespace Xofttion\SOA\Utils;

class ComposedBy extends Aggregation {
    
    // Constructor de la clase ComposedBy
    
    /**
     * 
     * @param string $class
     */
    public function __construct(string $class) {
        parent::__construct($class, false, false, true, false, null);
    }
}