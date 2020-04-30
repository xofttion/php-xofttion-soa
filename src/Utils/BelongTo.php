<?php

namespace Xofttion\SOA\Utils;

class BelongTo extends Aggregation {
    
    // Constructor de la clase BelongTo
    
    /**
     * 
     * @param string $class
     * @param string $column
     */
    public function __construct(string $class, string $column) {
        parent::__construct($class, false, false, false, false, $column);
    }
}