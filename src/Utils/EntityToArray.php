<?php

namespace Xofttion\SOA\Utils;

use Closure;
use ReflectionClass;
use ReflectionProperty;

use Xofttion\Kernel\Str;

use Xofttion\SOA\Contracts\IEntity;
use Xofttion\SOA\Contracts\IEntityCollection;

class EntityToArray {
    
    // Atributos de la clase EntityToArray
    
    /**
     *
     * @var ModelMapper 
     */
    private static $instance = null;
    
    // Constructor de la clase EntityToArray
    
    private function __construct() {
        
    }
    
    // Métodos estáticos de la clase EntityToArray

    /**
     * 
     * @return EntityToArray
     */
    public static function getInstance(): EntityToArray {
        if (is_null(self::$instance)) {
            self::$instance = new static(); // Instanciando EntityToArray
        } 
        
        return self::$instance; // Retornando EntityToArray
    }
    
    // Métodos de la clase EntityToArray
    
    /**
     * 
     * @param IEntity $entity
     * @param array $superParents
     * @return array
     */
    public function forTransaction(IEntity $entity, array $superParents = []): array {
        return $this->execute($entity, $superParents, function (IEntity $entityClosure) { return $entityClosure->getInoperativesKeys(); });
    }
    
    /**
     * 
     * @param IEntity $entity
     * @param array $superParents
     * @return array
     */
    public function forRequest(IEntity $entity, array $superParents = null): array {
        return $this->execute($entity, $superParents, function (IEntity $entityClosure) { return $entityClosure->getProtectedsKeys(); });
    }

    /**
     * 
     * @param IEntity $entity
     * @param array $superParents
     * @param Closure|null $closure
     * @return array
     */
    public function execute(IEntity $entity, array $superParents = [], ?Closure $closure = null): array {
        $reflection = new ReflectionClass($entity);
        $result     = []; // Array de entidad
        $discards   = is_null($closure) ? [] : $closure($entity);
        
        foreach ($reflection->getProperties() as $property) {
            if (!in_array($property->getName(), $discards)) { 
                $value = $this->getValueKeyEntity($reflection, $property, $entity, $superParents, $closure);

                if (!is_null($value)) {
                    $result[$property->getName()] = $value; // Estableciendo
                }
            } 
        }
        
        return $result; // Retorna array generado de la entidad
    }

    /**
     * 
     * @param ReflectionClass $reflection
     * @param ReflectionProperty $property
     * @param IEntity $entity
     * @param array $superParents
     * @param Closure|null $closure
     * @return object
     */
    private function getValueKeyEntity(ReflectionClass $reflection, ReflectionProperty $property, IEntity $entity, array $superParents = [], ?Closure $closure = null) {
        if ($property->isPublic()) {
            $value = $property->getValue($entity); // Accediendo directamente a la propiedad
        } else {
            $value = $this->getValueMethodEntity($reflection, $property, $entity);
        }

        if ($value instanceof IEntity) {
            if ((in_array($value, $superParents)) || ($value === $entity)) {
                return null; // Se retorna null para controlar recuisividad infinita
            } else {
                array_push($superParents, $value); return $this->execute($value, $superParents, $closure);
            }
        } else if ($value instanceof IEntityCollection) {
            $collection = []; // Colección de datos
            
            foreach ($value as $entityCollection) {
                array_push($collection, $this->execute($entityCollection, $superParents, $closure));
            }
            
            return $collection; // Retornando la colección generada de entidades
        }
        
        return $value; // Retornando valor generado de la propiedad de la entidad
    }

    /**
     * 
     * @param ReflectionClass $reflection
     * @param ReflectionProperty $property
     * @param IEntity $entity
     * @return object
     */
    private function getValueMethodEntity(ReflectionClass $reflection, ReflectionProperty $property, IEntity $entity) {        
        $getter = Str::getCamelCase()->ofSnakeGetter($property->getName());
            
        if ($reflection->hasMethod($getter)) {
            return $this->getValueMethod($reflection, $getter, $entity); // Método getter
        }
        
        $ister = Str::getCamelCase()->ofSnakeIster($property->getName());
            
        if ($reflection->hasMethod($ister)) {
            return $this->getValueMethod($reflection, $ister, $entity);  // Método ister
        }
    }
    
    /**
     * 
     * @param ReflectionClass $reflection
     * @param string $method
     * @param IEntity $entity
     * @return object
     */
    private function getValueMethod(ReflectionClass $reflection, string $method, IEntity $entity) {
        $accessor = $reflection->getMethod($method); // Método de la entidad
            
        return (!$accessor->isPublic()) ? null : $accessor->invoke($entity);
    }
}