<?php

namespace Xofttion\SOA\Contracts;

use IteratorAggregate;
use Countable;
use JsonSerializable;

interface IEntityCollection extends IteratorAggregate, Countable, JsonSerializable {
    
    // Métodos de la interfaz IEntityCollection
    
    /**
     * 
     * @return bool
     */
    public function isEmpty(): bool;
    
    /**
     * 
     * @param IEntity $entity
     * @return void
     */
    public function attach(IEntity $entity): void;
    
    /**
     * 
     * @param IEntity $entity
     * @return int
     */
    public function indexOf(IEntity $entity): int;
    
    /**
     * 
     * @param int $index
     * @return IEntity|null
     */
    public function getValue(int $index): ?IEntity;
    
    /**
     * 
     * @return IEntity|null
     */
    public function first(): ?IEntity;
    
    /**
     * 
     * @return IEntity|null
     */
    public function last(): ?IEntity;
    
    /**
     * 
     * @param IEntity $entity
     * @return void
     */
    public function detach(IEntity $entity): void;

    /**
     * 
     * @return void
     */
    public function clear(): void;

    /**
     * 
     * @return array
     */
    public function toArray(): array;
}