<?php

namespace Xofttion\SOA\Contracts;

use JsonSerializable;

interface IEntity extends JsonSerializable
{

    // Métodos de la interfaz IEntity

    /**
     * 
     * @param int $primaryKey
     * @return void
     */
    public function setPrimaryKey(int $primaryKey): void;

    /**
     * 
     * @return int|null
     */
    public function getPrimaryKey(): ?int;

    /**
     * 
     * @param int $parentKey
     * @return void
     */
    public function setParentKey(int $parentKey): void;

    /**
     * 
     * @return int|null
     */
    public function getParentKey(): ?int;

    /**
     * 
     * @return string
     */
    public function getTable(): string;

    /**
     * 
     * @return IAggregations
     */
    public function getAggregations(): IAggregations;

    /**
     * 
     * @return array
     */
    public function getProtectedsKeys(): array;

    /**
     * 
     * @return array
     */
    public function getInoperativesKeys(): array;

    /**
     * 
     * @return array
     */
    public function getNulleables(): array;

    /**
     * 
     * @return array
     */
    public function toArray(): array;
}
