<?php

namespace Xofttion\SOA\Contracts;

use Xofttion\Kernel\Contracts\IDataStorage;

interface IAggregationsStorage
{

    // Métodos de la interfaz IAggregationsStorage

    /**
     * 
     * @param IEntity $entity
     * @return IDataStorage
     */
    public function cascade(IEntity $entity): IDataStorage;

    /**
     * 
     * @param IEntity $entity
     * @return IDataStorage
     */
    public function refresh(IEntity $entity): IDataStorage;

    /**
     * 
     * @param IEntity $entity
     * @return IDataStorage
     */
    public function belong(IEntity $entity): IDataStorage;
}
