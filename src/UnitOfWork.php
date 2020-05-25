<?php

namespace Xofttion\SOA;

use ReflectionClass;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Capsule\Manager;

use Xofttion\Kernel\Utils\Reflection;

use Xofttion\SOA\Contracts\IUnitOfWork;
use Xofttion\SOA\Contracts\IRepository;
use Xofttion\SOA\Contracts\IEntity;
use Xofttion\SOA\Contracts\IEntityCollection;
use Xofttion\SOA\Contracts\IAggregationsStorage;
use Xofttion\SOA\Contracts\IEntityMapper;
use Xofttion\SOA\Utils\AggregationsStorage;

class UnitOfWork implements IUnitOfWork {
    
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
    
    public function __construct() {
        $this->time = time();
    }

    // Métodos sobrescritos de la interfaz IUnitOfWork
    
    public function setConnectionManager(?Manager $connectionManager): void {
        $this->connectionManager = $connectionManager;
    }

    public function setContext(?string $context): void {
        $this->context = $context;
    }

    public function getContext(): ?string {
        return $this->context;
    }
    
    public function getNow(): int {
        return $this->time;
    }

    public function getRepository(string $classEntity): ?IRepository {
        if (!$this->storeRepository->contains($classEntity)) {
            $repository = $this->getInstanceRepository($classEntity);
            $repository->setUnitOfWork($this);
            $repository->setMapper($this->getMapper());
            
            $repository->setContext($this->getContext());

            $this->storeRepository->attach($classEntity, $repository);
        } // No existe estancia del repositorio, definiendo repositorio
        
        return $this->storeRepository->getValue($classEntity); // Repositorio
    }
    
    public function setMapper(?IEntityMapper $entityMapper): void {
        $this->entityMapper = $entityMapper;
    }
    
    public function getMapper(): ?IEntityMapper {
        return $this->entityMapper;
    }
    
    public function attach(IEntity $entity): void {
        $this->storeEntity->attach($entity, new StatusEntity(StatusEntity::STATE_DIRTY, clone $entity));
    }
    
    public function attachCollection(array $entities): void {
        foreach ($entities as $entity) { 
            if ($entity instanceof IEntity) {
                $this->attach($entity); // Cargando entidad del listado
            }
        }
    }
    
    public function persist(IEntity $entity): void {
        $this->setBelongAggregations($entity); // Cargando referencias
        
        $this->insert($entity); // Registrando entidad principal
        
        $cascades = $this->getAggregationsStorage()->cascade($entity);
        
        foreach ($cascades as $aggregation) {
            $this->insertAggregation($entity, $cascades->getValue($aggregation));
        } // Registrando listado de agregaciones en cascada
    }
    
    public function persists(IEntityCollection $collection): void {
        foreach ($collection as $entity) {
            $this->persist($entity); // Persistiendo entidad de colección
        }
    }
    
    public function safeguard(IEntity $entity): void {
        $this->modify($entity); // Actualizando entidad principal
        
        $cascades = $this->getAggregationsStorage()->cascade($entity);
        
        foreach ($cascades as $aggregation) {
            $this->modifyAggregation($entity, $cascades->getValue($aggregation));
        } // Actualizando listado de agregaciones en cascada
    }
    
    public function safeguards(IEntityCollection $collection): void {
        foreach ($collection as $entity) {
            $this->safeguard($entity); // Actualizando entidad de colección
        }
    }
                
    public function destroy(IEntity $entity): void {
        $this->storeEntity->attach($entity, new StatusEntity(StatusEntity::STATE_REMOVE));
    }
    
    public function destroys(IEntityCollection $collection): void {
        foreach ($collection as $entity) {
            $this->destroy($entity); // Eliminando entidad de colección
        }
    }

    public function transaction(): void {
        $this->getConnection()->beginTransaction(); // Iniciando transacción
    }

    public function commit(): void {
        foreach ($this->storeEntity as $entity) {
            $entityStatus = $this->storeEntity->getValue($entity); // Datos
            
            switch ($entityStatus->getStatus()) {
                case (StatusEntity::STATE_DIRTY) :
                    $this->update($entity, $entityStatus); // Actualizando
                break;
            
                case (StatusEntity::STATE_NEW) :
                    // Se debería registrar la entidad
                break;
            
                case (StatusEntity::STATE_REMOVE) :
                    $this->delete($entity); // Eliminando
                break;
            }
        }
        
        $this->storeEntity->clear(); $this->getConnection()->commit(); // Confirmando comandos
    }

    public function rollback(): void {
        $this->getConnection()->rollback(); // Revertiendo todos los comandos
    }
    
    // Métodos de la clase UnitOfWork

    /**
     * 
     * @param StoreEntity $storeEntity
     * @return void
     */
    public function setStoreEntity(StoreEntity $storeEntity): void {
        $this->storeEntity = $storeEntity;
    }
    
    /**
     * 
     * @param StoreRepository $storeRepository
     * @return void
     */
    public function setStoreRepository(StoreRepository $storeRepository): void {
        $this->storeRepository = $storeRepository;
    }
    
    /**
     * 
     * @return ConnectionInterface
     */
    protected function getConnection(): ConnectionInterface {
        if (is_null($this->connection)) {
            $this->connection = $this->getConnectionInterface();
        } // Definiendo conexión de la transacción 
        
        return $this->connection; // Conexión con base de datos
    }
    
    /**
     * 
     * @return ConnectionInterface
     */
    protected function getConnectionInterface(): ConnectionInterface {
        return $this->connectionManager->getConnection($this->getContext());
    }
    
    /**
     * 
     * @param string $classEntity
     * @return IRepository
     */
    protected function getInstanceRepository(string $classEntity): IRepository {
        return new Repository($classEntity);
    }
    
    /**
     * 
     * @param IEntity $entity
     * @return void
     */
    protected function setBelongAggregations(IEntity $entity): void {
        $belongs    = $this->getAggregationsStorage()->belong($entity);
        $reflection = new ReflectionClass($entity);
        
        foreach ($belongs as $aggregation) {
            $entityAggregation = $belongs->getValue($aggregation);
            
            if (is_null($entityAggregation->getPrimaryKey())) {
                $this->persist($entityAggregation); // Persistiendo entidad
            }
            
            $valuePK = $entityAggregation->getPrimaryKey();
            $namePK  = $aggregation->getColumn();
            
            Reflection::assingSetter($entity, $namePK, $valuePK, $reflection);
        }
    }

    /**
     * 
     * @param IEntity $entity
     * @return void
     */
    protected function insert(IEntity $entity): void {
        $this->getRepository(get_class($entity))->insert($entity); $this->attach($entity); 
    }

    /**
     * 
     * @param IEntity $parent
     * @param object $aggregation
     * @return void
     */
    protected function insertAggregation(IEntity $parent, $aggregation): void {
        if ($aggregation instanceof IEntity) {
            $aggregation->setParentKey($parent->getPrimaryKey()); $this->persist($aggregation);
        } // Agregación del padre es una entidad simple

        if ($aggregation instanceof IEntityCollection) {
            foreach ($aggregation as $entity) {
                $entity->setParentKey($parent->getPrimaryKey()); $this->persist($entity);
            }
        } // Agregación del padre es un listado de entidades
    }
    
    /**
     * 
     * @param IEntity $entity
     * @return void
     */
    protected function modify(IEntity $entity): void  {
        $this->getRepository(get_class($entity))->safeguard($entity);
    }

    /**
     * 
     * @param IEntity $parent
     * @param object $aggregation
     * @return void
     */
    protected function modifyAggregation(IEntity $parent, $aggregation): void {
        if ($aggregation instanceof IEntity) {
            $aggregation->setParentKey($parent->getPrimaryKey()); $this->safeguard($aggregation);
        } // Agregación del padre es una entidad simple

        if ($aggregation instanceof IEntityCollection) {
            foreach ($aggregation as $entity) {
                $entity->setParentKey($parent->getPrimaryKey()); $this->safeguard($entity);
            }
        } // Agregación del padre es un listado de entidades
    }

    /**
     * 
     * @param IEntity $entity
     * @param StatusEntity $status
     * @return void
     */
    protected function update(IEntity $entity, StatusEntity $status): void {
        if ($status->getEntity() != $entity) {
            $data = $this->getArrayUpdate($entity, $status->getEntity());

            $this->getRepository(get_class($entity))->update($entity->getPrimaryKey(), $data);
        } // Entidad modificada, requiere ser actualizada en el Repositorio
    }
    
    /**
     * 
     * @param IEntity $entity
     * @param IEntity $clone
     * @return array
     */
    protected function getArrayUpdate(IEntity $entity, IEntity $clone): array {
        $arrayUpdate = []; // Array para actualizar
        
        $arrayEntity = $entity->toArray();
        $arrayClone  = $clone->toArray();
        
        foreach ($arrayEntity as $key => $value) {
            if (!isset($arrayClone[$key])) {
                $arrayUpdate[$key] = $value;
            } else if (($value != $arrayClone[$key])) {
                $arrayUpdate[$key] = $value;
            } // Se detecto valor diferente en la clave
        }
        
        return $arrayUpdate; // Retornando datos de actualización
    }
    
    /**
     * 
     * @param IEntity $entity
     * @return void
     */
    protected function delete(IEntity $entity): void {
        $this->getRepository(get_class($entity))->delete($entity);
    }

    /**
     * 
     * @return IAggregationsStorage
     */
    protected function getAggregationsStorage(): IAggregationsStorage {
        return AggregationsStorage::getInstance();
    }
}