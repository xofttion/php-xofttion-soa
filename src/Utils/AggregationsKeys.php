<?php

namespace Xofttion\SOA\Utils;

use Closure;

use Xofttion\SOA\Contracts\IAggregation;
use Xofttion\SOA\Contracts\IAggregationsKeys;

class AggregationsKeys implements IAggregationsKeys {
    
    // Atributos de la clase AggregationsKeys
    
    /**
     *
     * @var AggregationsKeys 
     */
    private static $instance = null;
    
    /**
     *
     * @var array 
     */
    private $aggregations;
    
    // Constructor de la clase AggregationsKeys
    
    private function __construct() {
        
    }
    
    // Métodos de la clase AggregationsKeys

    /**
     * 
     * @return AggregationsKeys
     */
    public static function getInstance(): AggregationsKeys {
        if (is_null(self::$instance)) {
            self::$instance = new static(); // Instanciando AggregationsKeys
        } 
        
        return self::$instance; // Retornando AggregationsKeys
    }
    
    /**
     * 
     * @param array $aggregations
     * @return AggregationsKeys
     */
    public function setAggregations(array $aggregations): AggregationsKeys {
        $this->aggregations = $aggregations; return $this;
    }

    /**
     * 
     * @param Closure $closure
     * @return array
     */
    protected function get(Closure $closure): array {
        $keys = []; // Listado de claves para gestion de datos
        
        foreach ($this->aggregations as $key => $value) {
            if ($closure($value)) {
                array_push($keys, $key);
            }
        } // Listado de claves para gestion de datos
        
        return $keys; // Retornando claves para gestion de datos
    }

    public function all(): array {
        return $this->get(function () { return true; });
    }

    public function cascade(): array {
        return $this->get(function (IAggregation $aggregation) { return $aggregation->isCascade(); });
    }

    public function composed(): array {
        return $this->get(function (IAggregation $aggregation) { return $aggregation->isComposed(); });
    }

    public function belong(): array {
        return $this->get(function (IAggregation $aggregation) { return $aggregation->isBelong(); });
    }
}