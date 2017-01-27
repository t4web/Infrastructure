<?php

namespace T4webInfrastructure;

use ArrayObject;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\Event;
use T4webDomainInterface\Infrastructure\CriteriaInterface;
use T4webDomainInterface\Infrastructure\RepositoryInterface;
use T4webDomainInterface\EntityInterface;
use T4webDomainInterface\EntityFactoryInterface;
use T4webInfrastructure\Event\EntityChangedEvent;

class InMemoryRepository implements RepositoryInterface
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var string
     */
    protected $collectionClass;

    /**
     * @var CriteriaFactory
     */
    protected $criteriaFactory;

    /**
     * @var EntityFactoryInterface
     */
    protected $entityFactory;

    /**
     * @var ArrayObject
     */
    protected $identityMap;

    /**
     * @var ArrayObject
     */
    protected $identityMapOriginal;

    /**
     * @var EventManagerInterface
     */
    protected $eventManager;

    /**
     * @var EntityChangedEvent
     */
    protected $event;

    protected $primaryKey = 1;

    /**
     * @param string $entityName
     * @param string $collectionClass
     * @param CriteriaFactory $criteriaFactory
     * @param EntityFactoryInterface $entityFactory
     * @param EventManagerInterface $eventManager
     */
    public function __construct(
        $entityName,
        $collectionClass,
        CriteriaFactory $criteriaFactory,
        EntityFactoryInterface $entityFactory,
        EventManagerInterface $eventManager
    ) {
        $this->entityName = $entityName;
        $this->collectionClass = $collectionClass;
        $this->criteriaFactory = $criteriaFactory;
        $this->entityFactory = $entityFactory;
        $this->identityMap = new ArrayObject();
        $this->identityMapOriginal = new ArrayObject();
        $this->eventManager = $eventManager;
    }

    /**
     * @param EntityInterface $entity
     * @return EntityInterface|int|null
     */
    public function add(EntityInterface $entity)
    {
        $id = $entity->getId();

        if ($this->identityMap->offsetExists((int)$id)) {
            $e = $this->getEvent();
            $originalEntity = $this->identityMapOriginal->offsetGet($entity->getId());
            $e->setOriginalEntity($originalEntity);
            $e->setChangedEntity($entity);

            $this->triggerPreChanges($e);

            $this->toIdentityMap($entity);

            $this->triggerChanges($e);
            $this->triggerAttributesChange($e);

            return 1;
        } else {
            if (empty($id)) {
                $id = $this->primaryKey++;
                $entity->populate(compact('id'));
            }

            $this->toIdentityMap($entity);

            $this->triggerCreate($entity);
        }

        return $entity;
    }

    /**
     * @param EntityInterface $entity
     * @return int|null
     */
    public function remove(EntityInterface $entity)
    {
        $id = $entity->getId();

        if ($this->identityMap->offsetExists((int)$id)) {
            $this->identityMap->offsetUnset($id);
            $result = 1;
        } else {
            $result = 0;
        }

        $this->triggerDelete($entity);

        return $result;
    }

    /**
     * @param CriteriaInterface|array $criteria
     * @return EntityInterface|null
     */
    public function find($criteria)
    {
        if (is_array($criteria)) {
            $criteria = $this->createCriteria($criteria);
        }

        $callbacks = $criteria->getQuery();

        $result = null;
        /** @var EntityInterface $entity */
        foreach ($this->identityMap as $entity) {

            $data = $entity->extract();
            $isSatisfied = true;

            foreach ($callbacks as $callback) {
                if (!$callback($data)) {
                    $isSatisfied = false;
                    break 1;
                }
            }

            if ($isSatisfied) {
                $result = $entity;
                break;
            }
        }

        if (is_null($result)) {
            return;
        }

        $entity = $result;

        $this->toIdentityMap($entity);

        return $entity;
    }

    /**
     * @param mixed $id
     * @return EntityInterface|null
     */
    public function findById($id)
    {
        $criteria = $this->createCriteria();
        $criteria->equalTo('id', $id);

        return $this->find($criteria);
    }

    /**
     * @param CriteriaInterface|array $criteria
     * @return EntityInterface[]
     */
    public function findMany($criteria)
    {
        if (is_array($criteria)) {
            $criteria = $this->createCriteria($criteria);
        }

        $callbacks = $criteria->getQuery();

        $entities = new $this->collectionClass;

        /** @var EntityInterface $entity */
        foreach ($this->identityMap as $entity) {

            $data = $entity->extract();
            $isSatisfied = true;

            foreach ($callbacks as $callback) {
                if (!$callback($data)) {
                    $isSatisfied = false;
                    break 1;
                }
            }

            if ($isSatisfied) {
                $entities->offsetSet($entity->getId(), $entity);
            }
        }

        foreach ($entities as $entity) {
            $this->toIdentityMap($entity);
        }

        return $entities;
    }

    /**
     * @param CriteriaInterface|array $criteria
     * @return int
     */
    public function count($criteria)
    {
        if (is_array($criteria)) {
            $criteria = $this->createCriteria($criteria);
        }

        /** @var ArrayObject $entities */
        $entities = $this->findMany($criteria);

        return $entities->count();
    }

    /**
     * @param array $filter
     * @return CriteriaInterface
     */
    public function createCriteria(array $filter = [])
    {
        return $this->criteriaFactory->buildInMemory($this->entityName, $filter);
    }

    /**
     * @param EntityInterface $entity
     */
    protected function toIdentityMap(EntityInterface $entity)
    {
        $this->identityMap->offsetSet($entity->getId(), $entity);
        $this->identityMapOriginal->offsetSet($entity->getId(), clone $entity);
    }

    /**
     * @param EntityInterface $changedEntity
     * @return bool
     */
    protected function isEntityChanged(EntityInterface $changedEntity)
    {
        $originalEntity = $this->identityMapOriginal->offsetGet($changedEntity->getId());
        return $changedEntity != $originalEntity;
    }

    /**
     * @return EntityChangedEvent
     */
    protected function getEvent()
    {
        if (null === $this->event) {
            $this->event = new EntityChangedEvent();
            $this->event->setTarget($this);
        }

        return $this->event;
    }

    /**
     * @param EntityInterface $createdEntity
     */
    protected function triggerCreate(EntityInterface &$createdEntity)
    {
        $this->eventManager->addIdentifiers(get_class($createdEntity));

        $event = new Event(
            sprintf('entity:%s:created', get_class($createdEntity)),
            $this,
            ['entity' => $createdEntity]
        );
        $this->eventManager->trigger($event);

        if ($event->getParam('entity') && $event->getParam('entity') instanceof EntityInterface) {
            $createdEntity = $event->getParam('entity');
        }
    }

    /**
     * @param EntityInterface $deletedEntity
     */
    protected function triggerDelete(EntityInterface $deletedEntity)
    {
        $this->eventManager->addIdentifiers(get_class($deletedEntity));

        $event = new Event(
            sprintf('entity:%s:deleted', get_class($deletedEntity)),
            $this,
            ['entity' => $deletedEntity]
        );
        $this->eventManager->trigger($event);
    }

    /**
     * @param EntityChangedEvent $e
     */
    protected function triggerChanges(EntityChangedEvent $e)
    {
        $changedEntity = $e->getChangedEntity();
        $this->eventManager->trigger($this->getEntityChangeEventName($changedEntity), $this, $e);
    }

    /**
     * @param EntityChangedEvent $e
     */
    protected function triggerPreChanges(EntityChangedEvent $e)
    {
        $changedEntity = $e->getChangedEntity();
        $this->eventManager->trigger($this->getEntityChangeEventName($changedEntity).':pre', $this, $e);
    }

    /**
     * @param EntityChangedEvent $e
     */
    protected function triggerAttributesChange(EntityChangedEvent $e)
    {
        $changedEntity = $e->getChangedEntity();

        $originalAttrs = $e->getOriginalEntity()->extract();
        $changedAttrs = $changedEntity->extract();

        foreach (array_keys(array_diff_assoc($originalAttrs, $changedAttrs)) as $attribute) {
            $this->eventManager->trigger($this->getAttributeChangeEventName($changedEntity, $attribute), $this, $e);
        }
    }

    /**
     * @param EntityInterface $changedEntity
     * @return string
     */
    protected function getEntityChangeEventName(EntityInterface $changedEntity)
    {
        return sprintf('entity:%s:changed', get_class($changedEntity));
    }

    /**
     * @param EntityInterface $changedEntity
     * @param $attributeName
     * @return string
     */
    protected function getAttributeChangeEventName(EntityInterface $changedEntity, $attributeName)
    {
        return sprintf('attribute:%s:%s:changed', get_class($changedEntity), $attributeName);
    }
}
