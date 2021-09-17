<?php

namespace Xofttion\SOA;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Capsule\Manager;
use Xofttion\ORM\Contracts\IModel;
use Xofttion\ORM\Contracts\IRelationship;
use Xofttion\SOA\Contracts\IUnitOfStorage;
use Xofttion\SOA\Contracts\IStorage;
use Xofttion\SOA\Storage;

class UnitOfStorage implements IUnitOfStorage
{

    // Atributos de la clase UnitOfStorage

    /**
     *
     * @var Manager 
     */
    protected $connectionManager;

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

    public function __construct()
    {
        $this->time = time();
    }

    // Métodos sobrescritos de la interfaz UnitOfStorage

    public function setConnectionManager(?Manager $connectionManager): void
    {
        $this->connectionManager = $connectionManager;
    }

    public function setContext(?string $context): void
    {
        $this->context = $context;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function getNow(): int
    {
        return $this->time;
    }

    public function getStorage(string $classModel): ?IStorage
    {
        if (!$this->storeStorage->contains($classModel)) {
            $storgage = $this->getInstanceStorage($classModel);
            $storgage->setUnitOfStorage($this);
            $storgage->setContext($this->getContext());

            $this->storeStorage->attach($classModel, $storgage);
        }

        return $this->storeStorage->getValue($classModel);
    }

    public function attach(IModel $model): void
    {
        $this->storeModel->attach($model, new StatusModel(StatusModel::STATE_DIRTY, $model));
    }

    public function persist(IModel $model): void
    {
        $model->reaload($this->insert($model));

        $this->attach($model);
        $model->cleanRelationships();
    }

    public function persists(Collection $collection): void
    {
        foreach ($collection as $model) {
            $this->persist($model);
        }
    }

    public function safeguard(IModel $model): void
    {
        $model->push();
    }

    public function safeguards(Collection $collection): void
    {
        foreach ($collection as $model) {
            $this->safeguard($model);
        }
    }

    public function destroy(IModel $model): void
    {
        $this->storeModel->attach($model, new StatusModel(StatusModel::STATE_REMOVE));
    }

    public function destroys(Collection $collection): void
    {
        foreach ($collection as $model) {
            $this->safeguard($model);
        }
    }

    public function transaction(): void
    {
        $this->getConnection()->beginTransaction();
    }

    public function commit(): void
    {
        foreach ($this->storeModel as $model) {
            $modelStatus = $this->storeModel->getValue($model);

            switch ($modelStatus->getStatus()) {
                case (StatusModel::STATE_DIRTY): {
                    $this->update($model);
                    break;
                }

                case (StatusModel::STATE_NEW): {
                    break;
                }

                case (StatusModel::STATE_REMOVE): {
                    $this->delete($model);
                    break;
                }
            }
        }

        $this->storeModel->clear();
        $this->getConnection()->commit();
    }

    public function rollback(): void
    {
        $this->getConnection()->rollback();
    }

    // Métodos de la clase UnitOfWork

    /**
     * 
     * @param StoreModel $storeModel
     * @return void
     */
    public function setStoreModel(StoreModel $storeModel): void
    {
        $this->storeModel = $storeModel;
    }

    /**
     * 
     * @param StoreStorage $storeStorage
     * @return void
     */
    public function setStoreStorage(StoreStorage $storeStorage): void
    {
        $this->storeStorage = $storeStorage;
    }

    /**
     * 
     * @param string $classModel
     * @return IStorage
     */
    protected function getInstanceStorage(string $classModel): IStorage
    {
        return new Storage($classModel);
    }

    /**
     * 
     * @return ConnectionInterface
     */
    protected function getConnection(): ConnectionInterface
    {
        if (is_null($this->connection)) {
            $this->connection = $this->getConnectionInterface();
        }

        return $this->connection;
    }

    /**
     * 
     * @return ConnectionInterface
     */
    protected function getConnectionInterface(): ConnectionInterface
    {
        return $this->connectionManager->getConnection($this->getContext());
    }

    /**
     * 
     * @param IModel $model
     * @return array
     */
    protected function insert(IModel $model): array
    {
        $model->setContext($this->getContext());

        $relationships = $model->getRelationships();
        $reloads = [];

        if (!is_null($relationships)) {
            foreach ($relationships->getParents() as $key => $parent) {
                $this->attachReloads($reloads, $key, $this->insertParent($parent));
            }

            $model->save();

            foreach ($relationships->getChildrens() as $key => $children) {
                $this->attachReloads($reloads, $key, $this->insertChildren($children));
            }
        }

        return $reloads;
    }

    /**
     * 
     * @param IRelationship $parent
     * @return array
     */
    private function insertParent(IRelationship $parent): array
    {
        $modelParent = $parent->getValue();
        $reloadsParent = [];

        if (!$modelParent->exists) {
            $reloadsParent = $this->insert($modelParent);
        }

        $parent->getRelation()->associate($modelParent);

        return $reloadsParent;
    }

    /**
     * 
     * @param IRelationship $children
     * @return array
     */
    private function insertChildren(IRelationship $children): array
    {
        $children->getRelation()->saveMany($children->getValue());

        $reloadsChildren = [];

        if (is_array($children->getValue())) {
            foreach ($children->getValue() as $modelChildren) {
                $reloadsChildren = array_unique(array_merge($reloadsChildren, $this->insert($modelChildren)));
            }
        }
        else {
            $reloadsChildren = $this->insert($children->getValue());
        }

        return $reloadsChildren;
    }

    /**
     * 
     * @param array $reloads
     * @param string $key
     * @param array $values
     * @return void
     */
    private function attachReloads(array &$reloads, string $key, array $values): void
    {
        if (empty($values)) {
            array_push($reloads, $key);
        }
        else {
            $reloads[$key] = $values;
        }
    }

    /**
     * 
     * @param IModel $model
     * @return void
     */
    protected function update(IModel $model): void
    {
        $model->push();
    }

    /**
     * 
     * @param IModel $model
     * @return void
     */
    protected function delete(IModel $model): void
    {
        $this->getStorage(get_class($model))->delete($model);
    }
}
