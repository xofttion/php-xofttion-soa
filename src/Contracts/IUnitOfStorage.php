<?php

namespace Xofttion\SOA\Contracts;

use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Eloquent\Collection;
use Xofttion\ORM\Contracts\IModel;
use Xofttion\SOA\Contracts\IStorage;

interface IUnitOfStorage
{

    // Métodos de la interfaz IUnitOfStorage

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
     * @param string $classModel
     * @return IStorage
     */
    public function getStorage(string $classModel): ?IStorage;

    /**
     * 
     * @param IModel $model
     * @return void
     */
    public function attach(IModel $model): void;

    /**
     * 
     * @param IModel $model
     * @return void
     */
    public function persist(IModel $model): void;

    /**
     * 
     * @param Collection $collection
     * @return void
     */
    public function persists(Collection $collection): void;

    /**
     * 
     * @param IModel $model
     * @return void
     */
    public function safeguard(IModel $model): void;

    /**
     * 
     * @param Collection $collection
     * @return void
     */
    public function safeguards(Collection $collection): void;

    /**
     * 
     * @param IModel $model
     * @return void
     */
    public function destroy(IModel $model): void;

    /**
     * 
     * @param Collection $collection
     * @return void
     */
    public function destroys(Collection $collection): void;

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
