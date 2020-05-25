<?php

namespace Xofttion\SOA\Utils;

use ReflectionClass;

use Xofttion\Kernel\Utils\Reflection;

use Xofttion\SOA\Contracts\IEntity;
use Xofttion\SOA\Contracts\IEntityCollection;
use Xofttion\SOA\Contracts\IEntityMapper;
use Xofttion\SOA\EntityCollection;

class EntityMapper implements IEntityMapper {
    
    // Atributos de la clase EntityMapper
    
    /**
     *
     * @var array 
     */
    private $entities = [];
    
    /**
     *
     * @var IEntityMapper 
     */
    private static $instance = null;
    
    // Constructor de la clase IEntityMapper
    
    private function __construct() {
        
    }
    
    // Métodos estáticos de la clase IEntityMapper

    /**
     * 
     * @return IEntityMapper
     */
    public static function getInstance(): IEntityMapper {
        if (is_null(self::$instance)) {
            self::$instance = new static(); // Instanciando IEntityMapper
        } 
        
        return self::$instance; // Retornando IEntityMapper
    }
    
    // Métodos sobrescritos de la interfaz IEntityMapper
    
    public function ofArray(IEntity $entity, ?array $data): ?IEntity {
        if (is_null($data)) { 
            return null; // No se ha definido origen de datos
        } 
        
        $reflection = new ReflectionClass($entity);
        
        foreach ($data as $key => $value) {
            $this->setValueKeyEntity($reflection, $entity, $key, $value);
        } // Recorriendo claves y valores del origen
        
        array_push($this->entities, $entity); // Agregando
        
        return $entity; // Retornando entidad con sus atributos mapeados
    }
    
    public function clean(): IEntityMapper {
        $this->entities = []; return $this;
    }
    
    public function getCollection(): array {
        return $this->entities;
    }
    
    // Métodos de la clase EntityMapper
    
    /**
     * 
     * @param ReflectionClass $reflection
     * @param IEntity $entity
     * @param string $key
     * @param object $value
     * @return void
     */
    protected function setValueKeyEntity(ReflectionClass $reflection, IEntity $entity, string $key, $value): void {
        Reflection::assingSetter($entity, $key, $this->getValue($entity, $key, $value), $reflection);
    }

    /**
     * 
     * @param IEntity $entity
     * @param string $key
     * @param object $value
     * @return object|null
     */
    protected function getValue(IEntity $entity, string $key, $value) {
        if (is_null($value)) { 
            return null; // Valor indefinido, no se debe gestionar dato
        } 
        
        if ($entity->getAggregations()->contains($key)) {
            $aggregation = $entity->getAggregations()->getValue($key); 
            
            if ($aggregation->isArray()) {
                return $this->createCollection($aggregation->getClass(), $value);
            } else {
                return $this->createEntity($aggregation->getClass(), $value);
            }
        }
        
        return $value; // Retornando el valor del atributo predeterminado
    }
    
    /**
     * 
     * @param string $classEntity
     * @param object $value
     * @return IEntity|null
     */
    protected function createEntity(string $classEntity, $value): ?IEntity {
        return $this->ofArray(new $classEntity(), $value); // Retornando entidad generada
    }
    
    /**
     * 
     * @param string $classEntity
     * @param object $collection
     * @return IEntityCollection 
     */
    protected function createCollection(string $classEntity, $collection): IEntityCollection {
        $array = $this->getEntityCollection(); // Colección 
        
        foreach ($collection as $value) {
            $array->attach($this->createEntity($classEntity, $value));
        } // Cargando entidades del listado
        
        return $array; // Retornando entidades generadas
    }
    
    /**
     * 
     * @return IEntityCollection|null
     */
    protected function getEntityCollection(): ?IEntityCollection {
        return new EntityCollection();
    }
}