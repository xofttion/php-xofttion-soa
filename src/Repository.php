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
        $query = $this->getQuery(); // Constructor de consulta
        
        $aggregationsKeys = $entity->getAggregations()->keys();
        
        $refresh = $aggregationsKeys->refresh();
        
        $data = $entity->toArray();
        
        $model = $query->insert($data, $refresh);

        $this->mapper($entity, $model);
    }

    public function find(int $id): ?IEntity
    {
        $query = $this->getQuery(); // Constructor de consulta
        
        $model = $query->find($id);
        
        return $this->createEntity($model);
    }

    public function findAll(): IEntityCollection
    {
        $query = $this->getQuery(); // Constructor de consulta
        
        $models = $query->rows();
        
        return $this->createCollection($models);
    }

    public function fetch(int $id, ?array $aggregations = null): ?IEntity
    {
        $query = $this->getQuery(); // Constructor de consulta
        
        $model = $query->record($id, $aggregations);
        
        return $this->createEntity($model);
    }

    public function fetchAll(?array $aggregations = null): IEntityCollection
    {
        $query = $this->getQuery(); // Constructor de consulta
        
        $models = $query->catalog($aggregations);
        
        return $this->createCollection($models);
    }

    public function resources(): IEntityCollection
    {
        $query = $this->getQuery(); // Constructor de consulta
        
        $models = $query->catalog();
        
        return $this->createCollection($models);
    }

    public function update(int $id, array $data): void
    {
        $query = $this->getQuery(); // Constructor de consulta
        
        $query->update($id, $data);
    }

    public function safeguard(IEntity $entity): void
    {
        $query = $this->getQuery(); // Constructor de consulta
        
        $aggregationsKeys = $entity->getAggregations()->keys();
        
        $refresh = $aggregationsKeys->refresh();
        
        $data = $entity->toArray();
        
        $primaryKey = $entity->getPrimaryKey();

        $model = $query->safeguard($primaryKey, $data, $refresh);

        $this->mapper($entity, $model);
    }

    public function delete(IEntity $entity): void
    {
        $query = $this->getQuery(); // Constructor de consulta
        
        $query->delete($entity->getPrimaryKey());
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
            $mapper = $this->getMapper()->clean();
            
            $mapper->ofArray($entity, $model->toArray());
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
            $collection = $this->getMapper()->getCollection();
            
            $this->getUnitOfWork()->collection($collection);
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
            $entity = $this->createEntity($model, $classEntity);
            
            $entities->attach($entity);
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
