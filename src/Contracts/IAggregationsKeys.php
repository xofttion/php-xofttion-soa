<?php

namespace Xofttion\SOA\Contracts;

interface IAggregationsKeys {
    
    // Métodos de la interfaz IAggregationsKeys
    
    /**
     * 
     * @return array
     */
    public function all(): array;
    
    /**
     * 
     * @return array
     */
    public function cascade(): array;
    
    /**
     * 
     * @return array
     */
    public function composed(): array;
    
    /**
     * 
     * @return array
     */
    public function belong(): array;
}