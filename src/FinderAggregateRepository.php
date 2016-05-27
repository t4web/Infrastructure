<?php

namespace T4webInfrastructure;

use ArrayObject;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use T4webDomainInterface\Infrastructure\RepositoryInterface;
use T4webDomainInterface\Infrastructure\CriteriaInterface;
use T4webDomainInterface\EntityInterface;
use T4webDomainInterface\EntityFactoryInterface;

class FinderAggregateRepository implements RepositoryInterface
{
    /**
     * @var TableGateway
     */
    private $tableGateway;

    /**
     * @var Mapper
     */
    private $mapper;

    /**
     * @var EntityFactoryInterface
     */
    private $entityFactory;

    /**
     * @var RepositoryInterface
     */
    private $entityRepository;

    /**
     * @var RepositoryInterface[]
     */
    private $relatedRepository;

    /**
     * @var array
     */
    private $relationsConfig;

    /**
     * @var ArrayObject[]
     */
    private $with;

    /**
     * FinderAggregateRepository constructor.
     * @param TableGateway $tableGateway
     * @param Mapper $mapper
     * @param EntityFactoryInterface $entityFactory
     * @param RepositoryInterface $entityRepository
     * @param RepositoryInterface[] $relatedRepository
     * @param array $relationsConfig
     */
    public function __construct(
        TableGateway $tableGateway,
        Mapper $mapper,
        EntityFactoryInterface $entityFactory,
        RepositoryInterface $entityRepository,
        array $relatedRepository,
        array $relationsConfig)
    {
        $this->tableGateway = $tableGateway;
        $this->mapper = $mapper;
        $this->entityFactory = $entityFactory;
        $this->entityRepository = $entityRepository;
        $this->relatedRepository = $relatedRepository;
        $this->relationsConfig = $relationsConfig;
    }

    /**
     * @param string $entityName
     * @return $this
     */
    public function with($entityName)
    {
        if (!isset($this->relatedRepository[$entityName])) {
            throw new \RuntimeException(get_class($this) . ": related $entityName repository not exists");
        }

        $this->with[$entityName] = [];

        return $this;
    }

    /**
     * @param mixed $criteria
     * @return EntityInterface|null
     */
    public function find($criteria)
    {
        if (empty($this->with)) {
            return $this->entityRepository->find($criteria);
        }

        /** @var Select $select */
        $select = $criteria->getQuery();

        $select->limit(1)->offset(0);
        $result = $this->tableGateway->selectWith($select)->toArray();

        if (!isset($result[0])) {
            return;
        }

        $row = $result[0];

        $relatedEntityIds = [];
        foreach ($this->with as $relatedEntityName => $cascadeWith) {
            $relatedField = $this->getRelatedField($relatedEntityName);

            if (!isset($row[$relatedField])) {
                throw new \RuntimeException(get_class($this) . ": relation field $relatedEntityName not fetched");
            }

            if (!isset($relatedEntityIds[$relatedEntityName])) {
                $relatedEntityIds[$relatedEntityName] = new ArrayObject();
            }

            if (!in_array($row[$relatedField], (array)$relatedEntityIds[$relatedEntityName])) {
                $relatedEntityIds[$relatedEntityName]->append($row[$relatedField]);
            }
        }

        $relatedEntities = [];
        foreach ($this->with as $relatedEntityName => $cascadeWith) {
            $criteria = $this->relatedRepository[$relatedEntityName]->createCriteria(['id.in' => (array)$relatedEntityIds[$relatedEntityName]]);
            $relatedEntities[$relatedEntityName] = $this->relatedRepository[$relatedEntityName]->findMany($criteria);
        }

        $relatedField = $this->getRelatedField($relatedEntityName);

        $entityArgs = [
            'data' => $this->mapper->fromTableRow($row)
        ];

        foreach ($this->relationsConfig as $entityName => $joinRule) {
            if (isset($relatedEntities[$entityName])) {
                if (isset($relatedEntities[$entityName][$row[$relatedField]])) {
                    $entityArgs['aggregateItems'][] = $relatedEntities[$entityName][$row[$relatedField]];
                } else {
                    $entityArgs['aggregateItems'][] = null;
                }
            } else {
                $entityArgs['aggregateItems'][] = null;
            }
        }

        $entity = $this->entityFactory->create($entityArgs);

        $this->with = null;

        return $entity;
    }

    /**
     * @param mixed $criteria
     * @return EntityInterface[]
     */
    public function findMany($criteria)
    {
        if (empty($this->with)) {
            return $this->entityRepository->findMany($criteria);
        }

        /** @var Select $select */
        $select = $criteria->getQuery();

        $rows = $this->tableGateway->selectWith($select)->toArray();

        foreach ($rows as $row) {
            foreach ($this->with as $relatedEntityName => $cascadeWith) {
                $relatedField = $this->getRelatedField($relatedEntityName);

                if (!isset($row[$relatedField])) {
                    throw new \RuntimeException(get_class($this) . ": relation field $relatedEntityName not fetched");
                }

                if (!isset($relatedEntityIds[$relatedEntityName])) {
                    $relatedEntityIds[$relatedEntityName] = new ArrayObject();
                }

                if (!in_array($row[$relatedField], (array)$relatedEntityIds[$relatedEntityName])) {
                    $relatedEntityIds[$relatedEntityName]->append($row[$relatedField]);
                }
            }
        }

        $relatedEntities = [];
        foreach ($this->with as $relatedEntityName => $cascadeWith) {
            $criteria = $this->relatedRepository[$relatedEntityName]->createCriteria(['id.in' => (array)$relatedEntityIds[$relatedEntityName]]);
            $relatedEntities[$relatedEntityName] = $this->relatedRepository[$relatedEntityName]->findMany($criteria);
        }

        $entitiesArgs = [];
        foreach ($rows as &$row) {
            $entityArgs = [
                'data' => $this->mapper->fromTableRow($row)
            ];

            foreach ($this->relationsConfig as $entityName => $joinRule) {
                $relatedField = $this->getRelatedField($entityName);
                
                if (isset($relatedEntities[$entityName])) {
                    if (isset($relatedEntities[$entityName][$row[$relatedField]])) {
                        $entityArgs['aggregateItems'][] = $relatedEntities[$entityName][$row[$relatedField]];
                    } else {
                        $entityArgs['aggregateItems'][] = null;
                    }
                } else {
                    $entityArgs['aggregateItems'][] = null;
                }
            }

            $entitiesArgs[] = $entityArgs;
        }

        $entities = $this->entityFactory->createCollection($entitiesArgs);

        $this->with = null;

        return $entities;
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

    private function getRelatedField($entityName)
    {
        if (!isset($this->relationsConfig[$entityName])) {
            throw new \RuntimeException(get_class($this) . ": relation $entityName not exists");
        }

        list($table, $field) = explode('.', $this->relationsConfig[$entityName][0]);

        return $field;
    }

    /**
     * @param mixed $criteria
     * @return int
     */
    public function count($criteria)
    {
        return $this->entityRepository->count($criteria);
    }

    /**
     * @param array $filter
     * @return CriteriaInterface
     */
    public function createCriteria(array $filter = [])
    {
        return $this->entityRepository->createCriteria($filter);
    }

    /**
     * @param EntityInterface $entity
     * @return EntityInterface|int|null
     */
    public function add(EntityInterface $entity)
    {
        throw new \RuntimeException(get_class($this) . ' cannot adding');
    }

    /**
     * @param EntityInterface $entity
     * @return void
     */
    public function remove(EntityInterface $entity)
    {
        throw new \RuntimeException(get_class($this) . ' cannot removing');
    }
}
