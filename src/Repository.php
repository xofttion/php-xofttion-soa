<?php

namespace Xofttion\SOA;

use Illuminate\Database\Eloquent\Collection;
use Xofttion\ORM\Contracts\IQuery;
use Xofttion\ORM\Query;
use Xofttion\ORM\Contracts\IModel;
use Xofttion\SOA\Contracts\IUnitOfWork;
use Xofttion\SOA\Contracts\IRepository;
use Xofttion\SOA\Contracts\IEntity;
use Xofttion\SOA\Contracts\IEntityCollection;
use Xofttion\SOA\Contracts\IEntityMapper;

class Repository implements IRepository
{

    // Atributos de la clase Repository

    /**
     *
     * @var string 
     */
    protected $entity;

    /**
     *
     * @var IUnitOfWork 
     */
    private $unitOfWork;

    /**
     *
     * @var string 
     */
    private $context;

    /**
     *
     * @var IEntityMapper 
     */
    private $entityMapper;

    // Constructor de la clase Repository

    public function __construct(string $classEntity)
    {
        $this->entity = $classEntity;
    }

    // Métodos sobrescritos de la interfaz IRepository

    public function setContext(?string $context): void
    {
        $this->context = $context;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function setMapper(?IEntityMapper $entityMapper): void
    {
        $this->entityMapper = $entityMapper;
    }

    public function getMapper(): ?IEntityMapper
    {
        return $this->entityMapper;
    }

    public function setUnitOfWork(?IUnitOfWork $unitOfWork): void
    {
        $this->unitOfWork = $unitOfWork;
    }

    public function getUnitOfWork(): ?IUnitOfWork
    {
        return $this->unitOfWork;
    }

    public function getEntity(): ?IEntity
    {
        return new $this->entity();
    }

    public function insert(IEntity $entity): void
    {
        $hidrations = $entity->getAggregations()->keys()->refresh();

        $model = $this->getQuery()->insert($entity->toArray(), $hidrations);

        $this->mapper($entity, $model);
    }

    public function find(int $id): ?IEntity
    {
        return $this->createEntity($this->getQuery()->find($id));
    }

    public function findAll(): IEntityCollection
    {
        return $this->createCollection($this->getQuery()->rows());
    }

    public function fetch(int $id, ?array $aggregations = null): ?IEntity
    {
        return $this->createEntity($this->getQuery()->record($id, $aggregations));
    }

    public function fetchAll(?array $aggregations = null): IEntityCollection
    {
        return $this->createCollection($this->getQuery()->catalog($aggregations));
    }

    public function resources(): IEntityCollection
    {
        return $this->createCollection($this->getQuery($this->getEntity())->catalog());
    }

    public function update(int $id, array $data): void
    {
        $this->getQuery()->update($id, $data);
    }

    public function safeguard(IEntity $entity): void
    {
        $refreshs = $entity->getAggregations()->keys()->refresh();
        $primaryKey = $entity->getPrimaryKey();
        $data = $entity->toArray();

        $model = $this->getQuery()->safeguard($primaryKey, $data, $refreshs);

        $this->mapper($entity, $model);
    }

    public function delete(IEntity $entity): void
    {
        $this->getQuery()->delete($entity->getPrimaryKey());
    }

    // Métodos de la clase Repository


    /**
     * 
     * @param IEntity|null $entity
     * @return IQuery
     */
    protected function getQuery(?IEntity $entity = null): IQuery
    {
        if (is_null($entity)) {
            $entity = $this->getEntity();
        }

        $query = new Query($entity->getTable());
        $query->setContext($this->getContext());

        return $query;
    }

    /**
     * 
     * @param IEntity $entity
     * @param IModel|null $model
     * @return void
     */
    protected function mapper(IEntity $entity, ?IModel $model): void
    {
        if (!is_null($model)) {
            $this->getMapper()->clean()->ofArray($entity, $model->toArray());
        }
    }

    /**
     * 
     * @param IModel|null $model
     * @param string|null $classEntity
     * @return IEntity|null
     */
    protected function createEntity(?IModel $model, ?string $classEntity = null): ?IEntity
    {
        if (is_null($model)) {
            return null;
        }

        $entity = (is_null($classEntity)) ? $this->getEntity() : new $classEntity();

        $this->mapper($entity, $model);

        if (!is_null($this->getUnitOfWork())) {
            $this->getUnitOfWork()->attachCollection($this->getMapper()->getCollection());
        }

        return $entity;
    }

    /**
     * 
     * @param Collection $collection
     * @param string|null $classEntity
     * @return IEntityCollection
     */
    protected function createCollection(Collection $collection, ?string $classEntity = null): IEntityCollection
    {
        $entities = $this->getCollection();

        foreach ($collection as $model) {
            $entities->attach($this->createEntity($model, $classEntity));
        }

        return $entities;
    }

    /**
     * 
     * @return IEntityCollection
     */
    protected function getCollection(): IEntityCollection
    {
        return new EntityCollection();
    }
}
