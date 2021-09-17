<?php

namespace Xofttion\SOA;

use Xofttion\SOA\Contracts\IEntity;

class StatusEntity
{

    // Constantes de la clase StatusEntity

    const STATE_DIRTY = 101;

    const STATE_NEW = 102;

    const STATE_REMOVE = 103;

    // Atributos de la clase StatusEntity

    /**
     *
     * @var int 
     */
    private $status;

    /**
     *
     * @var IEntity 
     */
    private $entity;

    // Constructor de la clase StatusEntity

    /**
     * 
     * @param int $status
     * @param IEntity|null $entity
     */
    public function __construct(int $status, ?IEntity $entity = null)
    {
        $this->status = $status;
        $this->entity = $entity;
    }

    // Métodos de la clase StatusEntity

    /**
     * 
     * @param int $status
     * @return void
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * 
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * 
     * @param IEntity $entity
     * @return void
     */
    public function setEntity(IEntity $entity): void
    {
        $this->entity = $entity;
    }

    /**
     * 
     * @return IEntity
     */
    public function getEntity(): ?IEntity
    {
        return $this->entity;
    }
}
