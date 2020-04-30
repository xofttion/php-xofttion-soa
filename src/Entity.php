<?php

namespace Xofttion\SOA;

use Xofttion\SOA\Contracts\IEntity;
use Xofttion\SOA\Contracts\IAggregations;
use Xofttion\SOA\Utils\Aggregations;
use Xofttion\SOA\Utils\EntityToArray;

class Entity implements IEntity {
    
    // Atributos de la clase Entity
    
    /**
     *
     * @var array 
     */
    protected $inoperatives = [];
    
    /**
     *
     * @var array 
     */
    protected $protecteds = [];

    // Métodos sobrescritos de la interfaz IEntity

    public function setPrimaryKey(int $primaryKey): void {
        
    }

    public function getPrimaryKey(): ?int {
        return null;
    }

    public function setParentKey(int $parentKey): void {
        
    }

    public function getParentKey(): ?int {
        return null;
    }
    
    public function getTable(): string {
        return "entity";
    }

    public function getAggregations(): IAggregations {
        return new Aggregations();
    }

    public function getInoperativesKeys(): array {
        $aggregations = $this->getAggregations()->keys()->all();
        
        return array_merge($this->inoperatives, $aggregations);
    }

    public function getProtectedsKeys(): array {
        return $this->protecteds;
    }

    public function toArray(): array {
        return EntityToArray::getInstance()->forTransaction($this);
    }

    public function jsonSerialize() {
        return EntityToArray::getInstance()->forRequest($this);
    }
}