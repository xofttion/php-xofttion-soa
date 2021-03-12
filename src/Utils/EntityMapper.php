<?php

namespace Xofttion\SOA\Utils;

use Xofttion\SOA\Contracts\IEntity;
use Xofttion\SOA\Contracts\IEntityCollection;
use Xofttion\SOA\Contracts\IEntityMapper;
use Xofttion\SOA\EntityCollection;
use Xofttion\SOA\Utils\ReflectiveEntity;

class EntityMapper implements IEntityMapper {
    
    // Atributos de la clase EntityMapper
    
    /**
     *
     * @var IEntityMapper 
     */
    private static $instance = null;
    
    /**
     *
     * @var array 
     */
    private $entities;
    
    // Constructor de la clase IEntityMapper
    
    private function __construct() {
        $this->entities = [];
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
        if (is_defined($data)) { 
            $reflective = new ReflectiveEntity($entity); // Iniciando reflexión

            foreach ($data as $propertyName => $value) {
                $this->setValueEntity($reflective, $propertyName, $value);
            }

            array_push($this->entities, $entity); // Agregando

            return $entity; // Retornando entidad con sus atributos mapeados
        } 
        
        return null; // No se ha definido origen de datos
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
     * @param ReflectiveEntity $reflective
     * @param string $propertyName
     * @param object $value
     * @return void
     */
    protected function setValueEntity(ReflectiveEntity $reflective, string $propertyName, $value): void {
        $reflective->setSetter($propertyName, $this->getValue($reflective->getEntity(), $propertyName, $value));
    }

    /**
     * 
     * @param IEntity $entity
     * @param string $propertyName
     * @param object $value
     * @return object|null
     */
    protected function getValue(IEntity $entity, string $propertyName, $value) {
        if (is_defined($value)) { 
            if ($entity->getAggregations()->contains($propertyName)) {
                $aggregation = $entity->getAggregations()->getValue($propertyName);

                if ($aggregation->isArray()) {
                    return $this->createCollection($aggregation->getClass(), $value);
                } else {
                    return $this->createEntity($aggregation->getClass(), $value);
                }
            }

            return $value; // Retornando el valor del atributo predeterminado
        } 
        
        return null; // Valor indefinido, no se debe gestionar propiedade de entidad
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