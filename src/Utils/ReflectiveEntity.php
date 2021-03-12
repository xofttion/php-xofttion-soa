<?php

namespace Xofttion\SOA\Utils;

use ReflectionClass;

use Xofttion\Kernel\Str;
use Xofttion\Kernel\Utils\ReflectiveClass;
use Xofttion\SOA\Contracts\IEntity;

class ReflectiveEntity {
    
    // Parámetros de la clase ReflectiveEntity
    
    /**
     * 
     * @var ReflectiveClass
     */
    private static $reflective;

    /**
     * 
     * @var IEntity
     */
    private $entity;

    /**
     * 
     * @var ReflectionClass
     */
    private $reflection;
    
    // Constructor de la clase ReflectiveEntity
    
    /**
     * 
     * @param IEntity $entity
     */
    public function __construct(IEntity $entity) {
        $this->entity = $entity; 
    }
    
    // Métodos de la clase ReflectiveEntity
    
    /**
     * 
     * @return IEntity
     */
    public function getEntity(): IEntity {
        return $this->entity;
    }
    
    /**
     * 
     * @return ReflectionClass
     */
    private function getReflection(): ReflectionClass {
        if (is_null($this->reflection)) {
            $this->reflection = new ReflectionClass($this->getEntity());
        }
        
        return $this->reflection; // Instancia de reflexión
    }
    
    /**
     * 
     * @param string $propertyName
     * @param object $value
     * @return bool
     */
    public function setProperty(string $propertyName, $value): bool {
        return $this->getReflectiveClass()->setProperty($this->getEntity(), $propertyName, $value, $this->getReflection());
    }
    
    /**
     * 
     * @param string $methodName
     * @param object $value
     * @return bool
     */
    public function setMethod(string $methodName, $value): bool {
        return $this->getReflectiveClass()->setMethod($this->getEntity(), $methodName, $value, $this->getReflection());
    }
    
    /**
     * 
     * @param string $propertyName
     * @param object $value
     * @return bool
     */
    public function setSetter(string $propertyName, $value): bool {
        return $this->setMethod($this->getNameSetter($propertyName), $value) ? true : $this->setProperty($propertyName, $value);
    }
    
    /**
     * 
     * @param string $propertyName
     * @return object|null
     */
    public function getProperty(string $propertyName) {
        return $this->getReflectiveClass()->getProperty($this->getEntity(), $propertyName, $this->getReflection());
    }
    
    /**
     * 
     * @param string $methodName
     * @return object|null
     */
    public function getMethod(string $methodName) {
        return $this->getReflectiveClass()->getMethod($this->getEntity(), $methodName, $this->getReflection());
    }
    
    /**
     * 
     * @param string $propertyName
     * @return object|null
     */
    public function getGetter(string $propertyName) {
        if ($this->getReflection()->hasProperty($propertyName)) {
            $propertyAccessor = $this->getReflection()->getProperty($propertyName); // Propiedad

            if ($propertyAccessor->isPublic()) {
                return $propertyAccessor->getValue($this->getEntity()); // Retorno valor exitoso
            }
            
            return $this->getMethod($this->getNameGetter($propertyName)); // Retorno por método
        }
        
        return null; // La entidad no contiene ninguna propiedad con el nombre establecido
    }


    /**
     * 
     * @param string $propertyName
     * @return string
     */
    private function getNameSetter(string $propertyName): string {
        return Str::getCamelCase()->ofSnakeSetter($propertyName);
    }
    
    /**
     * 
     * @param string $propertyName
     * @return string
     */
    private function getNameGetter(string $propertyName): string {
        return Str::getCamelCase()->ofSnakeGetter($propertyName);
    }

    /**
     * 
     * @return ReflectiveClass
     */
    protected function getReflectiveClass(): ReflectiveClass {
        if (is_null(self::$reflective)) {
            self::$reflective = new ReflectiveClass(); // Instanciando
        }
        
        return self::$reflective; // Retornando instancia única
    }
}