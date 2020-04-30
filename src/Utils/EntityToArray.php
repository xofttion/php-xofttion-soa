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
     * @return array
     */
    public function forTransaction(IEntity $entity): array {
        return $this->execute($entity, function (IEntity $entityClosure) {
            return $entityClosure->getInoperativesKeys();
        });
    }
    
    /**
     * 
     * @param IEntity $entity
     * @return array
     */
    public function forRequest(IEntity $entity): array {
        return $this->execute($entity, function (IEntity $entityClosure) {
            return $entityClosure->getProtectedsKeys();
        });
    }

    /**
     * 
     * @param IEntity $entity
     * @param Closure|null $closure
     * @return array
     */
    public function execute(IEntity $entity, ?Closure $closure): array {
        $reflection = new ReflectionClass($entity);
        $result     = []; // Array de entidad
        $discards   = is_null($closure) ? [] : $closure($entity);
        
        foreach ($reflection->getProperties() as $property) {
            if (!in_array($property->getName(), $discards)) { 
                $value = $this->getValueKeyEntity($reflection, $property, $entity, $closure);

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
     * @param Closure|null $closure
     * @return object
     */
    private function getValueKeyEntity(ReflectionClass $reflection, ReflectionProperty $property, IEntity $entity, ?Closure $closure) {
        $value = ($property->isPublic()) ? $property->getValue($entity) :
            $this->getValueMethodEntity($reflection, $property, $entity);

        if ($value instanceof IEntity) {
            return $this->execute($value, $closure); // Procesando entidad
        } else if ($value instanceof IEntityCollection) {
            $collection = []; // Colección de datos
            
            foreach ($value as $itemEntity) {
                array_push($collection, $this->execute($itemEntity, $closure));
            }
            
            return $collection; // Retornando colección
        }
        
        return $value; // Retornando valor de la property
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