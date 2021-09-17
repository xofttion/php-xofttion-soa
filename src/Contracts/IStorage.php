<?php

namespace Xofttion\SOA\Contracts;

use Xofttion\ORM\Contracts\IStorage as IStorageORM;

interface IStorage extends IStorageORM
{

    // Métodos de la interfaz IStorage

    /**
     * 
     * @param IUnitOfStorage|null $unitOfStorage
     * @return void
     */
    public function setUnitOfStorage(?IUnitOfStorage $unitOfStorage): void;

    /**
     * 
     * @return IUnitOfStorage|null
     */
    public function getUnitOfStorage(): ?IUnitOfStorage;
}
