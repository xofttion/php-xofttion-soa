<?php

namespace Xofttion\SOA\Utils;

use Xofttion\SOA\Contracts\IEntity;
use Xofttion\SOA\Contracts\IEntityCollection;
use Xofttion\SOA\Contracts\IEntityMapper;
use Xofttion\SOA\EntityCollection;
use Xofttion\SOA\Utils\ReflectiveEntity;

class EntityMapper implements IEntityMapper
{

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

    private function __construct()
    {
        $this->entities = [];
    }

    // Métodos estáticos de la clase IEntityMapper

    /**
     * 
     * @return IEntityMapper
     */
    public static function getInstance(): IEntityMapper
    {
        if (is_null(self::$instance)) {
            self::$instance = new static ();
        }

        return self::$instance;
    }

    // Métodos sobrescritos de la interfaz IEntityMapper

    public function ofArray(IEntity $entity, ?array $data): ?IEntity
    {
        if (is_defined($data)) {
            $reflective = new ReflectiveEntity($entity);

            foreach ($data as $propertyName => $value) {
                $this->setValueEntity($reflective, $propertyName, $value);
            }

            array_push($this->entities, $entity);

            return $entity;
        }

        return null;
    }

    public function clean(): IEntityMapper
    {
        $this->entities = [];

        return $this;
    }

    public function getCollection(): array
    {
        return $this->entities;
    }

    // Métodos de la clase EntityMapper

    /**
     * 
     * @param ReflectiveEntity $reflective
     * @param string $propertyName
     * @param mixed $value
     * @return void
     */
    protected function setValueEntity(ReflectiveEntity $reflective, string $propertyName, $value): void
    {
        $reflective->setSetter($propertyName, $this->getValue($reflective->getEntity(), $propertyName, $value));
    }

    /**
     * 
     * @param IEntity $entity
     * @param string $propertyName
     * @param mixed $value
     * @return mixed
     */
    protected function getValue(IEntity $entity, string $propertyName, $value)
    {
        if (is_defined($value)) {
            if ($entity->getAggregations()->contains($propertyName)) {
                $aggregation = $entity->getAggregations()->getValue($propertyName);

                if ($aggregation->isArray()) {
                    return $this->createCollection($aggregation->getClass(), $value);
                }
                else {
                    return $this->createEntity($aggregation->getClass(), $value);
                }
            }

            return $value;
        }

        return null;
    }

    /**
     * 
     * @param string $classEntity
     * @param mixed $value
     * @return IEntity|null
     */
    protected function createEntity(string $classEntity, $value): ?IEntity
    {
        return $this->ofArray(new $classEntity(), $value); // Retornando entidad generada
    }

    /**
     * 
     * @param string $classEntity
     * @param mixed $collection
     * @return IEntityCollection 
     */
    protected function createCollection(string $classEntity, $collection): IEntityCollection
    {
        $collection = $this->getEntityCollection();

        foreach ($collection as $value) {
            $collection->attach($this->createEntity($classEntity, $value));
        }

        return $collection;
    }

    /**
     * 
     * @return IEntityCollection|null
     */
    protected function getEntityCollection(): ?IEntityCollection
    {
        return new EntityCollection();
    }
}
