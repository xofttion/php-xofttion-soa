<?php

namespace Xofttion\SOA;

use Xofttion\ORM\Contracts\IModel;

class StatusModel
{

    // Constantes de la clase StatusModel

    const STATE_DIRTY = 101;

    const STATE_NEW = 102;

    const STATE_REMOVE = 103;

    // Atributos de la clase StatusModel

    /**
     *
     * @var int 
     */
    private $status;

    /**
     *
     * @var IModel 
     */
    private $model;

    // Constructor de la clase StatusModel

    /**
     * 
     * @param int $status
     * @param IModel|null $model
     */
    public function __construct(int $status, ?IModel $model = null)
    {
        $this->status = $status;
        $this->model = $model;
    }

    // Métodos de la clase StatusModel

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
     * @param IModel $model
     * @return void
     */
    public function setModel(IModel $model): void
    {
        $this->model = $model;
    }

    /**
     * 
     * @return IModel
     */
    public function getModel(): ?IModel
    {
        return $this->model;
    }
}
