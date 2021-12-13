<?php

namespace Xofttion\SOA\Utils;

use Closure;
use ReflectionClass;
use Xofttion\Kernel\Contracts\IDataStorage;
use Xofttion\Kernel\Structs\DataStorage;
use Xofttion\Kernel\Str;
use Xofttion\SOA\Contracts\IEntity;
use Xofttion\SOA\Contracts\IAggregationsStorage;

class AggregationsStorage implements IAggregationsStorage
{

    // Atributos de la clase AggregationsStorage

    /**
     *
     * @var AggregationsStorage 
     */
    private static $instance = null;

    // Constructor de la clase AggregationsStorage

    private function __construct()
    {
    }

    // Métodos estáticos de la clase AggregationsStorage

    /**
     * 
     * @return AggregationsStorage
     */
    public static function getInstance(): AggregationsStorage
    {
        if (is_null(self::$instance)) {
            self::$instance = new static ();
        }

        return self::$instance;
    }

    // Métodos sobrecritos de la interfaz IAggregationsStorage

    public function cascade(IEntity $entity): IDataStorage
    {
        return $this->toStorage($entity, function (IEntity $entity) {
            return $entity->getAggregations()->forCascade();
        });
    }

    public function refresh(IEntity $entity): IDataStorage
    {
        return $this->toStorage($entity, function (IEntity $entity) {
            return $entity->getAggregations()->forRefresh();
        });
    }

    public function belong(IEntity $entity): IDataStorage
    {
        return $this->toStorage($entity, function (IEntity $entity) {
            return $entity->getAggregations()->forBelong();
        });
    }

    // Métodos de la clase AggregationsStorage 

    /**
     * 
     * @param IEntity $entity
     * @param Closure $closure
     * @return array
     */
    private function toStorage(IEntity $entity, Closure $closure): IDataStorage
    {
        $reflection = new ReflectionClass($entity);
        $aggregations = new DataStorage();

        foreach ($closure($entity) as $key => $aggregation) {
            $value = $this->getValueKeyEntity($reflection, $key, $entity);

            if (is_defined($value)) {
                $aggregations->attach($aggregation, $value);
            }
        }

        return $aggregations;
    }

    /**
     * 
     * @param ReflectionClass $reflection
     * @param string $key
     * @param IEntity $entity
     * @return mixed
     */
    private function getValueKeyEntity(ReflectionClass $reflection, string $key, IEntity $entity)
    {
        if ($reflection->hasProperty($key)) {
            $accessor = $reflection->getProperty($key);

            if ($accessor->isPublic()) {
                return $accessor->getValue($entity);
            }
        }

        return $this->getValueMethodEntity($reflection, $key, $entity);
    }

    /**
     * 
     * @param ReflectionClass $reflection
     * @param string $key
     * @param IEntity $entity
     * @return mixed
     */
    private function getValueMethodEntity(ReflectionClass $reflection, string $key, IEntity $entity)
    {
        $methodGetter = Str::getCamelCase()->ofSnakeGetter($key);

        if ($reflection->hasMethod($methodGetter)) {
            $accessor = $reflection->getMethod($methodGetter);

            if ($accessor->isPublic()) {
                return $accessor->invoke($entity);
            }
        }

        return null;
    }
}
