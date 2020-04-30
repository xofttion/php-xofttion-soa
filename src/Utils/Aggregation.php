<?php

namespace Xofttion\SOA\Utils;

use Xofttion\SOA\Contracts\IAggregation;

class Aggregation implements IAggregation {
    
    // Atributos de la clase Aggregation
    
    /**
     *
     * @var string 
     */
    private $class;
    
    /**
     *
     * @var bool 
     */
    private $array;
    
    /**
     *
     * @var bool 
     */
    private $cascade;
    
    /**
     *
     * @var bool 
     */
    private $composed;
    
    /**
     *
     * @var bool 
     */
    private $belong;
    
    /**
     *
     * @var string 
     */
    private $column;
    
    // Constructor de la clase Aggregation
    
    /**
     * 
     * @param string $class
     * @param bool $array
     * @param bool $cascade
     * @param bool $composed
     * @param bool $belong
     * @param string|null $column
     */
    public function __construct(
            string $class, 
            bool $array    = false, 
            bool $cascade  = false, 
            bool $composed = false,
            bool $belong   = false,
            ?string $column   = null) {
        
        $this->class    = $class;
        $this->array    = $array;
        $this->cascade  = $cascade; 
        $this->composed = $composed;
        $this->belong   = $belong;
        $this->column   = $column;
    }
    
    // Métodos de la clase Aggregation
    
    public function getClass(): ?string {
        return $this->class;
    }
    
    public function isArray(): ?bool {
        return $this->array;
    }
    
    public function isCascade(): ?bool {
        return $this->cascade;
    }
    
    public function isComposed(): ?bool {
        return $this->composed;
    }
    
    public function isBelong(): ?bool {
        return $this->belong;
    }
    
    public function getColumn(): ?string {
        return $this->column;
    }
}