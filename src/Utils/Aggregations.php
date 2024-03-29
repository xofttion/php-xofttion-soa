<?php

namespace Xofttion\SOA\Utils;

use Closure;
use Xofttion\Kernel\Structs\Json;
use Xofttion\SOA\Contracts\IAggregationsKeys;
use Xofttion\SOA\Contracts\IAggregation;
use Xofttion\SOA\Contracts\IAggregations;

class Aggregations implements IAggregations
{

    // Atributos de la clase Aggregations

    /**
     *
     * @var Json 
     */
    private $aggregations;

    // Constructor de la clase Aggregations

    /**
     * 
     */
    public function __construct()
    {
        $this->aggregations = new Json();
    }

    // Métodos de la clase Aggregations

    /**
     * 
     * @param Closure $closure
     * @return array
     */
    protected function forProcess(Closure $closure): array
    {
        $aggregations = [];

        foreach ($this->aggregations->values() as $key => $value) {
            if ($closure($value)) {
                $aggregations[$key] = $value;
            }
        }

        return $aggregations;
    }

    // Métodos sobrescritos de la clase IAggregations

    public function attach(string $key, IAggregation $aggregation): IAggregations
    {
        $this->aggregations->attach($key, $aggregation);

        return $this;
    }

    public function contains(string $key): bool
    {
        return $this->aggregations->contains($key);
    }

    public function getValue(string $key): ?IAggregation
    {
        return $this->aggregations->getValue($key);
    }

    public function hasOne(string $key, string $class): IAggregations
    {
        return $this->attach($key, new HasOne($class));
    }

    public function hasMany(string $key, string $class): IAggregations
    {
        return $this->attach($key, new HasMany($class));
    }

    public function refreshTo(string $key, string $class): IAggregations
    {
        return $this->attach($key, new RefreshTo($class));
    }

    public function belongTo(string $key, string $class, ?string $column = null): IAggregations
    {
        if (is_null($column)) {
            $column = "{$key}_id"; // Clave de la columna 
        }

        return $this->attach($key, new BelongTo($class, $column));
    }

    public function requiredTo(string $key, string $class, ?string $column = null): IAggregations
    {
        if (is_null($column)) {
            $column = "{$key}_id"; // Clave de la columna 
        }

        return $this->attach($key, new RequiredTo($class, $column));
    }

    public function containTo(string $key, string $class): IAggregations
    {
        return $this->attach($key, new ContainTo($class));
    }

    public function containsTo(string $key, string $class): IAggregations
    {
        return $this->attach($key, new ContainsTo($class));
    }

    public function keys(): IAggregationsKeys
    {
        return AggregationsKeys::getInstance()->setAggregations($this->aggregations->values());
    }

    public function forCascade(): array
    {
        return $this->forProcess(function (IAggregation $aggregation) {
            return $aggregation->isCascade();
        });
    }

    public function forRefresh(): array
    {
        return $this->forProcess(function (IAggregation $aggregation) {
            return $aggregation->isRefresh();
        });
    }

    public function forBelong(): array
    {
        return $this->forProcess(function (IAggregation $aggregation) {
            return $aggregation->isBelong();
        });
    }
}
