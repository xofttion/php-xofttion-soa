<?php

namespace Xofttion\SOA;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Capsule\Manager;
use Xofttion\SOA\Contracts\IUnitOfWork;
use Xofttion\SOA\Contracts\IRepository;
use Xofttion\SOA\Contracts\IEntity;
use Xofttion\SOA\Contracts\IEntityCollection;
use Xofttion\SOA\Contracts\IAggregationsStorage;
use Xofttion\SOA\Contracts\IEntityMapper;
use Xofttion\SOA\Utils\AggregationsStorage;
use Xofttion\SOA\Utils\ReflectiveEntity;

class UnitOfWork implements IUnitOfWork
{

    // Atributos de la clase UnitOfWork

    /**
     *
     * @var Manager 
     */
    protected $connectionManager;

    /**
     *
     * @var ConnectionInterface 
     */
    private $connection;

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

    /**
     *
     * @var int 
     */
    private $time;

    /**
     *
     * @var StoreEntity 
     */
    protected $storeEntity;

    /**
     *
     * @var StoreRepository 
     */
    protected $storeRepository;

    // Constructor de la clase UnitOfWork

    public function __construct()
    {
        $this->time = time();
    }

    // Métodos sobrescritos de la interfaz IUnitOfWork

    public function setConnectionManager(?Manager $connectionManager): void
    {
        $this->connectionManager = $connectionManager;
    }

    public function setContext(?string $context): void
    {
        $this->context = $context;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function getNow(): int
    {
        return $this->time;
    }

    public function getRepository(string $classEntity): ?IRepository
    {
        if (!$this->storeRepository->contains($classEntity)) {
            $repository = $this->getInstanceRepository($classEntity);
            $repository->setUnitOfWork($this);
            $repository->setMapper($this->getMapper());

            $repository->setContext($this->getContext());

            $this->storeRepository->attach($classEntity, $repository);
        }

        return $this->storeRepository->getValue($classEntity);
    }

    public function setMapper(?IEntityMapper $entityMapper): void
    {
        $this->entityMapper = $entityMapper;
    }

    public function getMapper(): ?IEntityMapper
    {
        return $this->entityMapper;
    }

    public function attach(IEntity $entity): void
    {
        $this->storeEntity->attach($entity, new StatusEntity(StatusEntity::STATE_DIRTY, clone $entity));
    }

    public function collection(array $entities): void
    {
        foreach ($entities as $entity) {
            if ($entity instanceof IEntity) {
                $this->attach($entity);
            }
        }
    }

    public function persist(IEntity $entity): void
    {
        $this->setBelongAggregations($entity);

        $this->insert($entity);

        $cascades = $this->getAggregationsStorage()->cascade($entity);

        foreach ($cascades as $aggregation) {
            $this->insertAggregation($entity, $cascades->getValue($aggregation));
        }
    }

    public function persists(IEntityCollection $collection): void
    {
        foreach ($collection as $entity) {
            $this->persist($entity);
        }
    }

    public function safeguard(IEntity $entity): void
    {   
        $this->modify($entity);
        
        $storage = $this->getAggregationsStorage();

        $cascades = $storage->cascade($entity);

        foreach ($cascades as $cascadeAggregation) {
            $aggregation = $cascades->getValue($cascadeAggregation);
            
            $this->modifyAggregation($entity, $aggregation);
        }
    }

    public function safeguards(IEntityCollection $collection): void
    {
        foreach ($collection as $entity) {
            $this->safeguard($entity);
        }
    }

    public function destroy(IEntity $entity): void
    {
        $this->storeEntity->attach($entity, new StatusEntity(StatusEntity::STATE_REMOVE));
    }

    public function destroys(IEntityCollection $collection): void
    {
        foreach ($collection as $entity) {
            $this->destroy($entity);
        }
    }

    public function transaction(): void
    {
        $this->getConnection()->beginTransaction();
    }

    public function commit(): void
    {
        foreach ($this->storeEntity as $entity) {
            $entityStatus = $this->storeEntity->getValue($entity);

            switch ($entityStatus->getStatus()) {
                case (StatusEntity::STATE_DIRTY): {
                    $this->update($entity, $entityStatus);
                    break;
                }

                case (StatusEntity::STATE_NEW): {
                    break;
                }

                case (StatusEntity::STATE_REMOVE): {
                    $this->delete($entity);
                    break;
                }
            }
        }

        $this->storeEntity->clear();
        $this->getConnection()->commit();
    }

    public function rollback(): void
    {
        $this->getConnection()->rollback();
    }

    // Métodos de la clase UnitOfWork

    /**
     * 
     * @param StoreEntity $storeEntity
     * @return void
     */
    public function setStoreEntity(StoreEntity $storeEntity): void
    {
        $this->storeEntity = $storeEntity;
    }

    /**
     * 
     * @param StoreRepository $storeRepository
     * @return void
     */
    public function setStoreRepository(StoreRepository $storeRepository): void
    {
        $this->storeRepository = $storeRepository;
    }

    /**
     * 
     * @return ConnectionInterface
     */
    protected function getConnection(): ConnectionInterface
    {
        if (is_null($this->connection)) {
            $this->connection = $this->getConnectionInterface();
        }

        return $this->connection;
    }

    /**
     * 
     * @return ConnectionInterface
     */
    protected function getConnectionInterface(): ConnectionInterface
    {
        return $this->connectionManager->getConnection($this->getContext());
    }

    /**
     * 
     * @param string $classEntity
     * @return IRepository
     */
    protected function getInstanceRepository(string $classEntity): IRepository
    {
        return new Repository($classEntity);
    }

    /**
     * 
     * @param IEntity $entity
     * @return void
     */
    protected function setBelongAggregations(IEntity $entity): void
    {
        $belongs = $this->getAggregationsStorage()->belong($entity);

        $reflective = new ReflectiveEntity($entity);

        foreach ($belongs as $aggregation) {
            $entityAggregation = $belongs->getValue($aggregation);

            if (is_null($entityAggregation->getPrimaryKey())) {
                $this->persist($entityAggregation);
            }
            
            $primaryKey = $entityAggregation->getPrimaryKey();
            $column = $aggregation->getColumn();

            $reflective->setSetter($column, $primaryKey);
        }
    }

    /**
     * 
     * @param IEntity $entity
     * @return void
     */
    protected function insert(IEntity $entity): void
    {
        $repository = $this->getRepository(get_class($entity));

        $repository->insert($entity);

        $this->attach($entity);
    }

    /**
     * 
     * @param IEntity $parent
     * @param mixed $aggregation
     * @return void
     */
    protected function insertAggregation(IEntity $parent, $aggregation): void
    {
        if ($aggregation instanceof IEntity) {
            $aggregation->setParentKey($parent->getPrimaryKey());
            $this->persist($aggregation);
        }

        if ($aggregation instanceof IEntityCollection) {
            foreach ($aggregation as $entity) {
                $entity->setParentKey($parent->getPrimaryKey());
                $this->persist($entity);
            }
        }
    }

    /**
     * 
     * @param IEntity $entity
     * @return void
     */
    protected function modify(IEntity $entity): void
    {
        $classEntity = get_class($entity);
        
        $repository = $this->getRepository($classEntity);
        
        $repository->safeguard($entity);
    }

    /**
     * 
     * @param IEntity $parent
     * @param mixed $aggregation
     * @return void
     */
    protected function modifyAggregation(IEntity $parent, $aggregation): void
    {
        if ($aggregation instanceof IEntity) {
            $aggregation->setParentKey($parent->getPrimaryKey());
            $this->safeguard($aggregation);
        }

        if ($aggregation instanceof IEntityCollection) {
            foreach ($aggregation as $entity) {
                $entity->setParentKey($parent->getPrimaryKey());
                $this->safeguard($entity);
            }
        }
    }

    /**
     * 
     * @param IEntity $entity
     * @param StatusEntity $status
     * @return void
     */
    protected function update(IEntity $entity, StatusEntity $status): void
    {
        if ($status->getEntity() != $entity) {
            $data = $this->getArrayUpdate($entity, $status->getEntity());

            $repository = $this->getRepository(get_class($entity));

            $repository->update($entity->getPrimaryKey(), $data);
        }
    }

    /**
     * 
     * @param IEntity $entity
     * @param IEntity $clone
     * @return array
     */
    protected function getArrayUpdate(IEntity $entity, IEntity $clone): array
    {
        $nulleables = $entity->getNulleables();

        $arrayEntity = $entity->toArray();
        $arrayClone = $clone->toArray();

        $arrayUpdate = [];

        foreach ($arrayEntity as $key => $value) {
            if (!isset($arrayClone[$key])) {
                $arrayUpdate[$key] = $value;
            }
            else if (($value != $arrayClone[$key])) {
                $arrayUpdate[$key] = $value;
            }
            else if (in_array($key, $nulleables)) {
                $arrayUpdate[$key] = $value;
            }
        }

        return $arrayUpdate;
    }

    /**
     * 
     * @param IEntity $entity
     * @return void
     */
    protected function delete(IEntity $entity): void
    {
        $classEntity = get_class($entity);
        
        $repository = $this->getRepository($classEntity);
        
        $repository->delete($entity);
    }

    /**
     * 
     * @return IAggregationsStorage
     */
    protected function getAggregationsStorage(): IAggregationsStorage
    {
        return AggregationsStorage::getInstance();
    }
}
