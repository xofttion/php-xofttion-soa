<?php

namespace Xofttion\SOA;

use Illuminate\Database\Eloquent\Collection;
use Xofttion\ORM\Contracts\IModel;
use Xofttion\ORM\Storage as StorageORM;
use Xofttion\SOA\Contracts\IUnitOfStorage;
use Xofttion\SOA\Contracts\IStorage;

class Storage extends StorageORM implements IStorage
{

    // Atributos de la clae Storage

    /**
     *
     * @var IUnitOfStorage 
     */
    private $unitOfStorage;

    // Métodos sobrescritos de la interfaz IStorage

    public function setUnitOfStorage(?IUnitOfStorage $unitOfStorage): void
    {
        $this->unitOfStorage = $unitOfStorage;
    }

    public function getUnitOfStorage(): ?IUnitOfStorage
    {
        return $this->unitOfStorage;
    }

    public function find(int $id): ?IModel
    {
        return $this->attachModel(parent::find($id));
    }

    public function findAll(): Collection
    {
        return $this->attachCollection(parent::findAll());
    }

    public function fetch(int $id, ?array $aggregations = null): ?IModel
    {
        return $this->attachModel(parent::fetch($id, $aggregations));
    }

    public function fetchAll(?array $aggregations = null): Collection
    {
        return $this->attachCollection(parent::fetchAll($aggregations));
    }

    // Métodos de la interfaz Storage

    /**
     * 
     * @param IModel|null $model
     * @param string|null $classEntity
     * @return IModel|null
     */
    protected function attachModel(?IModel $model): ?IModel
    {
        if (is_null($model)) {
            return null;
        }

        if (!is_null($this->getUnitOfStorage())) {
            $this->getUnitOfStorage()->attach($model);
        }

        return $model;
    }

    /**
     * 
     * @param Collection $collection
     * @return Collection
     */
    protected function attachCollection(Collection $collection): Collection
    {
        foreach ($collection as $model) {
            $this->attachModel($model);
        }

        return $collection;
    }
}
