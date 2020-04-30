<?php

namespace Xofttion\SOA\Utils;

class HasMany extends Aggregation {
    
    // Constructor de la clase HasMay
    
    /**
     * 
     * @param string $class
     */
    public function __construct(string $class) {
        parent::__construct($class, true, true, false, false, null);
    }
}