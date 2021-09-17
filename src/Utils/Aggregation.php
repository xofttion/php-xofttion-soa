<?php

namespace Xofttion\SOA\Utils;

use Xofttion\SOA\Contracts\IAggregation;

class Aggregation implements IAggregation
{

    // Atributos de la clase Aggregation

    /**
     *
     * @var string 
     */
    private $class;

    /**
     *
     * @var bool 
     */
    private $array;

    /**
     *
     * @var bool 
     */
    private $cascade;

    /**
     *
     * @var bool 
     */
    private $refresh;

    /**
     *
     * @var bool 
     */
    private $belong;

    /**
     *
     * @var string 
     */
    private $column;

    // Constructor de la clase Aggregation

    /**
     * 
     * @param string $class
     * @param bool $array
     * @param bool $cascade
     * @param bool $refresh
     * @param bool $belong
     * @param string|null $column
     */
    public function __construct(
        string $class,
        bool $array = false,
        bool $cascade = false,
        bool $refresh = false,
        bool $belong = false,
        ?string $column = null
    ) {
        $this->class = $class;
        $this->array = $array;
        $this->cascade = $cascade;
        $this->refresh = $refresh;
        $this->belong = $belong;
        $this->column = $column;
    }

    // Métodos de la clase Aggregation

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function isArray(): ?bool
    {
        return $this->array;
    }

    public function isCascade(): ?bool
    {
        return $this->cascade;
    }

    public function isRefresh(): ?bool
    {
        return $this->refresh;
    }

    public function isBelong(): ?bool
    {
        return $this->belong;
    }

    public function getColumn(): ?string
    {
        return $this->column;
    }
}
