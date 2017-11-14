<?php

namespace T4webInfrastructure;

use ArrayObject;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\Event;
use T4webDomainInterface\Infrastructure\CriteriaInterface;
use T4webDomainInterface\Infrastructure\RepositoryInterface;
use T4webDomainInterface\EntityInterface;
use T4webDomainInterface\EntityFactoryInterface;
use T4webInfrastructure\Event\EntityChangedEvent;

class Repository implements RepositoryInterface
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var CriteriaFactory
     */
    protected $criteriaFactory;

    /**
     * @var TableGateway
     */
    protected $tableGateway;

    /**
     * @var Mapper
     */
    protected $mapper;

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

    /**
     * @var string
     */
    protected $tablePrimaryKey;

    /**
     * @param string                $entityName
     * @param CriteriaFactory       $criteriaFactory
     * @param TableGateway          $tableGateway
     * @param Mapper                $mapper
     * @param EntityFactoryInterface $entityFactory
     * @param EventManagerInterface $eventManager
     * @param string $tablePrimaryKey
     */
    public function __construct(
        $entityName,
        CriteriaFactory $criteriaFactory,
        TableGateway $tableGateway,
        Mapper $mapper,
        EntityFactoryInterface $entityFactory,
        EventManagerInterface $eventManager,
        $tablePrimaryKey = 'id'
    ) {

        $this->entityName = $entityName;
        $this->criteriaFactory = $criteriaFactory;
        $this->tableGateway = $tableGateway;
        $this->mapper = $mapper;
        $this->entityFactory = $entityFactory;
        $this->identityMap = new ArrayObject();
        $this->identityMapOriginal = new ArrayObject();
        $this->eventManager = $eventManager;
        $this->tablePrimaryKey = $tablePrimaryKey;
    }

    /**
     * @param EntityInterface $entity
     * @return EntityInterface|int|null
     */
    public function add(EntityInterface $entity)
    {
        $id = $entity->getId();

        if (!is_null($id) && $this->identityMap->offsetExists((int)$id)) {
            if (!$this->isEntityChanged($entity)) {
                return;
            }

            $e = $this->getEvent();
            $originalEntity = $this->identityMapOriginal->offsetGet($entity->getId());
            $e->setOriginalEntity($originalEntity);
            $e->setChangedEntity($entity);

            $this->triggerPreChanges($e);

            $result = $this->tableGateway->update($this->mapper->toTableRow($entity), [$this->tablePrimaryKey => $id]);

            $this->triggerChanges($e);
            $this->triggerAttributesChange($e);

            return $result;
        } else {
            $this->tableGateway->insert($this->mapper->toTableRow($entity));

            if (empty($id)) {
                $id = $this->tableGateway->getLastInsertValue();
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

        if (empty($id)) {
            return;
        }

        $result = $this->tableGateway->delete([$this->tablePrimaryKey => $id]);

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

        /** @var Select $select */
        $select = $criteria->getQuery();

        $select->limit(1)->offset(0);
        $result = $this->tableGateway->selectWith($select)->toArray();

        if (!isset($result[0])) {
            return;
        }

        $attributesValues = $this->mapper->fromTableRow($result[0]);

        $entity = $this->entityFactory->create($attributesValues);

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

        /** @var Select $select */
        $select = $criteria->getQuery();

        $rows = $this->tableGateway->selectWith($select)->toArray();

        $attributesValues = $this->mapper->fromTableRows($rows);

        $entities = $this->entityFactory->createCollection($attributesValues);

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

        /** @var Select $select */
        $select = $criteria->getQuery();
        $select->columns(["row_count" => new Expression("COUNT(*)")]);

        $select->reset('limit');
        $select->reset('offset');
        $select->reset('order');
        $select->reset('group');

        $result = $this->tableGateway->selectWith($select)->toArray();

        if (!isset($result[0])) {
            return 0;
        }

        return $result[0]['row_count'];
    }

    /**
     * @param array $filter
     * @return CriteriaInterface
     */
    public function createCriteria(array $filter = [])
    {
        return $this->criteriaFactory->build($this->entityName, $filter);
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

        $this->eventManager->addIdentifiers(get_class($changedEntity));

        $this->eventManager->trigger($this->getEntityChangeEventName($changedEntity), $this, $e);
    }

    /**
     * @param EntityChangedEvent $e
     */
    protected function triggerPreChanges(EntityChangedEvent $e)
    {
        $changedEntity = $e->getChangedEntity();

        $this->eventManager->addIdentifiers(get_class($changedEntity));

        $this->eventManager->trigger($this->getEntityChangeEventName($changedEntity).':pre', $this, $e);
    }

    /**
     * @param EntityChangedEvent $e
     */
    protected function triggerAttributesChange(EntityChangedEvent $e)
    {
        $changedEntity = $e->getChangedEntity();

        $this->eventManager->addIdentifiers(get_class($changedEntity));

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
