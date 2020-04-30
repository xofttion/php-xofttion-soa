<?php

namespace Xofttion\SOA\Contracts;

interface IAggregation {
    
    // Métodos de la interfaz IAggregation
    
    /**
     * 
     * @return string|null
     */
    public function getClass(): ?string;
    
    /**
     * 
     * @return bool|null
     */
    public function isArray(): ?bool;
    
    /**
     * 
     * @return bool|null
     */
    public function isCascade(): ?bool;
    
    /**
     * 
     * @return bool|null
     */
    public function isComposed(): ?bool;
    
    /**
     * 
     * @return bool|null
     */
    public function isBelong(): ?bool;
    
    /**
     * 
     * @return string|null
     */
    public function getColumn(): ?string;
}