<?php

namespace Xofttion\SOA;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Capsule\Manager as DB;

use Xofttion\ORM\Contracts\IModel;

use Xofttion\SOA\Contracts\IUnitOfStorage;
use Xofttion\SOA\Contracts\IStorage;
use Xofttion\SOA\Storage;

class UnitOfStorage implements IUnitOfStorage {
    
    // Atributos de la clase UnitOfStorage
    
    /**
     *
     * @var ConnectionInterface 
     */
    private $connection;

    /**
     *
     * @var string
     */
    private $context;
    
    /**
     *
     * @var int 
     */
    private $time;
    
    /**
     *
     * @var StoreModel 
     */
    protected $storeModel;
    
    /**
     *
     * @var StoreStorage
     */
    protected $storeStorage;
    
    // Constructor de la clase UnitOfStorage
    
    public function __construct() {
        $this->time = time();
    }

    // Métodos sobrescritos de la interfaz UnitOfStorage

    public function setContext(?string $context): void {
        $this->context = $context;
    }

    public function getContext(): ?string {
        return $this->context;
    }

    public function getNow(): int {
        return $this->time;
    }

    public function getStorage(string $classModel): ?IStorage {
        if (!$this->storeStorage->contains($classModel)) {
            $storgage = $this->getInstanceStorage($classModel);
            $storgage->setUnitOfStorage($this);
            $storgage->setContext($this->getContext());

            $this->storeStorage->attach($classModel, $storgage);
        } // No existe estancia del almacén, definiendo almacén
        
        return $this->storeStorage->getValue($classModel); // Almacén
    }
    
    public function attach(IModel $model): void {
        $this->storeModel->attach($model, new StatusModel(StatusModel::STATE_DIRTY, $model));
    }

    public function persist(IModel $model): void {
        $model->setContext($this->getContext());   // Asignando contexto
        
        $aggregations = $model->getAggregations(); // Agregaciones
                
        foreach ($aggregations->getParents()->values() as $parent) {
            $modelParent = $parent->getValue(); // Modelo de la relación
            
            if (!$modelParent->exists) {
                $this->persist($modelParent); // Se debe persistir modelo
            }
            
            $parent->getRelation()->associate($modelParent); // Asociando
        }
        
        $model->save(); // Guardando los datos del modelo
                
        foreach ($aggregations->getParents() as $key => $parent) {
            $model[$key] = $parent->getValue(); // Asignando valor padre
        }
        
        foreach ($aggregations->getChildrens() as $key => $children) {
            $children->getRelation()->saveMany($children->getValue());
            
            $this->persist($children->getValue()); // Registrando
            
            $model[$key] = $children->getValue(); // Asignando valor hijo
        }
        
        $this->attach($model); $model->cleanAggregations(); // Limpiando agregaciones
    }

    public function persists(Collection $collection): void {
        foreach ($collection as $model) {
            $this->persist($model); // Registrando modelo de colección
        }
    }

    public function safeguard(IModel $model): void {
        $model->push();
    }

    public function safeguards(Collection $collection): void {
        foreach ($collection as $model) {
            $this->safeguard($model); // Actualizando modelo de colección
        }
    }

    public function destroy(IModel $model): void {
        $this->storeModel->attach($model, new StatusModel(StatusModel::STATE_REMOVE));
    }

    public function destroys(Collection $collection): void {
        foreach ($collection as $model) {
            $this->safeguard($model); // Eliminando modelo de colección
        }
    }

    public function transaction(): void {
        $this->getConnection()->beginTransaction(); // Iniciando transacción
    }

    public function commit(): void {
        foreach ($this->storeModel as $model) {
            $modelStatus = $this->storeModel->getValue($model); // Datos
            
            switch ($modelStatus->getStatus()) {
                case (StatusModel::STATE_DIRTY) :
                    $this->update($model); // Actualizando
                break;
            
                case (StatusModel::STATE_NEW) :
                    // Se debería registrar la entidad
                break;
            
                case (StatusModel::STATE_REMOVE) :
                    $this->delete($model); // Eliminando
                break;
            }
        }
        
        $this->storeModel->clear(); $this->getConnection()->commit(); // Confirmando comandos
    }

    public function rollback(): void {
        $this->getConnection()->rollback(); // Revertiendo todos los comandos
    }
    
    // Métodos de la clase UnitOfWork

    /**
     * 
     * @param StoreModel $storeModel
     * @return void
     */
    public function setStoreModel(StoreModel $storeModel): void {
        $this->storeModel = $storeModel;
    }
    
    /**
     * 
     * @param StoreStorage $storeStorage
     * @return void
     */
    public function setStoreStorage(StoreStorage $storeStorage): void {
        $this->storeStorage = $storeStorage;
    }
    
    /**
     * 
     * @param string $classModel
     * @return IStorage
     */
    protected function getInstanceStorage(string $classModel): IStorage {
        return new Storage($classModel);
    }
    
    /**
     * 
     * @return ConnectionInterface
     */
    protected function getConnection(): ConnectionInterface {
        if (is_null($this->connection)) {
            $this->connection = DB::connection($this->getContext());
        } // Definiendo conexión de la transacción 
        
        return $this->connection; // Conexión con base de datos
    }
    
    /**
     * 
     * @param IModel $model
     * @return void
     */
    protected function update(IModel $model): void {
        $model->push();
    }

    /**
     * 
     * @param IModel $model
     * @return void
     */
    protected function delete(IModel $model): void {
        $this->getStorage(get_class($model))->delete($model);
    }
}