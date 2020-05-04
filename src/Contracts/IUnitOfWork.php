<?php

namespace Xofttion\SOA\Contracts;

use Illuminate\Database\Capsule\Manager;

use Xofttion\SOA\Contracts\IRepository;
use Xofttion\SOA\Contracts\IEntity;
use Xofttion\SOA\Contracts\IEntityCollection;

interface IUnitOfWork {
    
    // Métodos de la interfaz IUnitOfWork
    
    /**
     * 
     * @param Manager|null $connectionManager
     * @return void
     */
    public function setConnectionManager(?Manager $connectionManager): void;
    
    /**
     * 
     * @param string|null $context
     * @return void
     */
    public function setContext(?string $context): void;
    
    /**
     * 
     * @return string|null
     */
    public function getContext(): ?string;
    
    /**
     * 
     * @return int
     */
    public function getNow(): int;

    /**
     * 
     * @param string $classEntity
     * @return IRepository
     */
    public function getRepository(string $classEntity): ?IRepository;
    
    /**
     * 
     * @param IEntityMapper|null $entityMapper
     * @return void
     */
    public function setMapper(?IEntityMapper $entityMapper): void;
    
    /**
     * 
     * @return IEntityMapper|null
     */
    public function getMapper(): ?IEntityMapper;
    
    /**
     * 
     * @param IEntity $entity
     * @return void
     */
    public function attach(IEntity $entity): void;
    
    /**
     * 
     * @param array $collection
     * @return void
     */
    public function attachCollection(array $collection): void;
    
    /**
     * 
     * @param IEntity $entity
     * @return void
     */
    public function persist(IEntity $entity): void;
    
    /**
     * 
     * @param IEntityCollection $collection
     * @return void
     */
    public function persists(IEntityCollection $collection): void;
    
    /**
     * 
     * @param IEntity $entity
     * @return void
     */
    public function safeguard(IEntity $entity): void;
    
    /**
     * 
     * @param IEntityCollection $collection
     * @return void
     */
    public function safeguards(IEntityCollection $collection): void;

    /**
     * 
     * @param IEntity $entity
     * @return void
     */
    public function destroy(IEntity $entity): void;

    /**
     * 
     * @param IEntityCollection $collection
     * @return void
     */
    public function destroys(IEntityCollection $collection): void;

    /**
     * 
     * @return void
     */
    public function transaction(): void;

    /**
     * 
     * @return void
     */
    public function commit(): void;
    
    /**
     * 
     * @return void
     */
    public function rollback(): void;
}