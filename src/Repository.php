<?php

namespace T4webInfrastructure;

use ArrayObject;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Expression;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\Event;
use T4webDomainInterface\Infrastructure\CriteriaInterface;
use T4webDomainInterface\Infrastructure\RepositoryInterface;
use T4webDomainInterface\EntityInterface;
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
     * @var QueryBuilder
     */
    protected $queryBuilder;

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
     * @param string                $entityName
     * @param TableGateway          $tableGateway
     * @param Mapper                $mapper
     * @param QueryBuilder          $queryBuilder
     * @param EventManagerInterface $eventManager
     */
    public function __construct(
        $entityName,
        CriteriaFactory $criteriaFactory,
        TableGateway $tableGateway,
        Mapper $mapper,
        QueryBuilder $queryBuilder,
        EventManagerInterface $eventManager
    )
    {
        $this->entityName = $entityName;
        $this->criteriaFactory = $criteriaFactory;
        $this->tableGateway = $tableGateway;
        $this->mapper = $mapper;
        $this->queryBuilder = $queryBuilder;
        $this->identityMap = new ArrayObject();
        $this->identityMapOriginal = new ArrayObject();
        $this->eventManager = $eventManager;
    }

    /**
     * @param EntityInterface $entity
     * @return EntityInterface
     */
    public function add(EntityInterface $entity)
    {
        $id = $entity->getId();

        if ($this->identityMap->offsetExists((int)$id)) {
            if (!$this->isEntityChanged($entity)) {
                return;
            }

            $e = $this->getEvent();
            $originalEntity = $this->identityMapOriginal->offsetGet($entity->getId());
            $e->setOriginalEntity($originalEntity);
            $e->setChangedEntity($entity);

            $this->triggerPreChanges($e);

            $result = $this->tableGateway->update($this->mapper->toTableRow($entity), ['id' => $id]);

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

        return $this->tableGateway->delete(['id' => $id]);
    }

    /**
     * @param mixed $criteria
     * @return EntityInterface|null
     */
    public function find($criteria)
    {
        $select = $this->queryBuilder->getSelect($criteria);

        $select->limit(1)->offset(0);
        $result = $this->tableGateway->selectWith($select)->toArray();

        if (!isset($result[0])) {
            return;
        }

        $entity = $this->mapper->fromTableRow($result[0]);

        $this->toIdentityMap($entity);

        return $entity;
    }

    /**
     * @param mixed $criteria
     * @return EntityInterface[]
     */
    public function findMany($criteria)
    {
        $select = $this->queryBuilder->getSelect($criteria);

        $rows = $this->tableGateway->selectWith($select)->toArray();

        $entities = $this->mapper->fromTableRows($rows);

        foreach ($entities as $entity) {
            $this->toIdentityMap($entity);
        }

        return $entities;
    }

    /**
     * @param mixed $criteria
     * @return int
     */
    public function count($criteria)
    {
        $select = $this->queryBuilder->getSelect($criteria);
        $select->columns(["row_count" => new Expression("COUNT(*)")]);

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
        if (empty($filter)) {
            $criteria = new Criteria($this->entityName);
        } else {
            $criteria = $this->criteriaFactory->build($this->entityName, $filter);
        }

        return $criteria;
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
        if (!empty($createdEntity) && $createdEntity instanceof Collection) {
            $this->eventManager->addIdentifiers(get_class($createdEntity->getFirst()));
        } else {
            $this->eventManager->addIdentifiers(get_class($createdEntity));
        }

        $event = new Event(sprintf('entity:%s:created', get_class($createdEntity)), $this, ['entity' => $createdEntity]);
        $this->eventManager->trigger($event);

        if ($event->getParam('entity') && $event->getParam('entity') instanceof EntityInterface) {
            $createdEntity = $event->getParam('entity');
        }
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
        $this->eventManager->trigger($this->getEntityChangeEventName($changedEntity) . ':pre', $this, $e);
    }

    /**
     * @param EntityChangedEvent $e
     */
    protected function triggerAttributesChange(EntityChangedEvent $e)
    {
        $changedEntity = $e->getChangedEntity();

        $originalAttrs = $e->getOriginalEntity()->extract();
        $changedAttrs = $changedEntity->extract();

        foreach (array_keys(array_diff($originalAttrs, $changedAttrs)) as $attribute) {
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
