<?php

namespace T4webInfrastructureTest\Assets;

use T4webDomainInterface\EntityFactoryInterface;

class EntityFactory implements EntityFactoryInterface
{

    protected $entityClass;
    protected $collectionClass;

    public function __construct($entityClass, $collectionClass = 'T4webBase\Domain\Collection')
    {
        $this->entityClass = $entityClass;
        $this->collectionClass = $collectionClass;
    }

    public function create(array $data)
    {
        if (!isset($data['data']) && !isset($data['aggregateItems'])) {
            return new $this->entityClass($data);
        }

        $reflector = new \ReflectionClass($this->entityClass);

        $istanceArgs = [$data['data']];

        foreach ($data['aggregateItems'] as $aggregateItem) {
            $istanceArgs[] = $aggregateItem;
        }

        return $reflector->newInstanceArgs($istanceArgs);
    }

    public function createCollection(array $data)
    {
        $collection = new $this->collectionClass();

        foreach ($data as $value) {
            $entity = $this->create($value);
            $collection->offsetSet($entity->getId(), $entity);
        }

        return $collection;
    }
}
