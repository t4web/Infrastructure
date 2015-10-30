<?php

namespace T4webDomainTest;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\EventManager\EventManager;
use T4webDomainInterface\EntityInterface;
use T4webDomainInterface\EntityFactoryInterface;
use T4webInfrastructure\Repository;
use T4webInfrastructure\Mapper;
use T4webInfrastructure\QueryBuilder;
use T4webInfrastructure\Criteria;

class Task implements EntityInterface
{
    protected $id;
    protected $name;
    protected $assignee;

    public function __construct(array $data = [])
    {
        $this->populate($data);
    }

    public function getId()
    {
        return $this->id;
    }

    public function extract(array $properties = [])
    {
        $state = get_object_vars($this);

        if (empty($properties)) {
            return $state;
        }

        $rawArray = array_fill_keys($properties, null);

        return array_intersect_key($state, $rawArray);
    }

    public function populate(array $array = [])
    {
        $state = get_object_vars($this);

        $stateIntersect = array_intersect_key($array, $state);

        foreach ($stateIntersect as $key => $value) {
            $this->$key = $value;
        }

        return $this;
    }
}

class EntityFactory implements EntityFactoryInterface {

    protected $entityClass;
    protected $collectionClass;

    public function __construct($entityClass, $collectionClass = 'T4webBase\Domain\Collection') {
        $this->entityClass = $entityClass;
        $this->collectionClass = $collectionClass;
    }

    public function create(array $data) {
        return new $this->entityClass($data);
    }

    public function createCollection(array $data) {
        $collection = new $this->collectionClass();

        foreach ($data as $value) {
            $entity = $this->create($value);
            $collection->offsetSet($entity->getId(), $entity);
        }

        return $collection;
    }

}

class RepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Repository
     */
    private $repository;

    public function setUp()
    {
        $dbAdapter = new Adapter([
            'driver'         => 'Pdo',
            'dsn'            => 'mysql:dbname=board;host=localhost',
            'driver_options' => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
            ),
            'username' => 'board',
            'password' => '111',
        ]);

        $tableGateway = new TableGateway('tasks', $dbAdapter);
        $mapper = new Mapper(
            [
                'id' => 'id',
                'name' => 'name',
                'assignee' => 'assignee',
                'status' => 'status',
                'type' => 'type',
            ],
            new EntityFactory('T4webDomainTest\Task', 'ArrayObject'));
        $queryBuilder = new QueryBuilder('tasks');

        $em = new EventManager();

        $this->repository = new Repository(
            $tableGateway,
            $mapper,
            $queryBuilder,
            $em
        );
    }

    public function testFindRowExists()
    {
        $id = 2;
        $criteria = $this->repository->createCriteria();
        $criteria->equalTo('id', $id);

        $entity = $this->repository->find($criteria);

        $this->assertInstanceOf('T4webDomainTest\Task', $entity);
        $this->assertEquals($id, $entity->getId());
    }

    public function testFindRowNotExists()
    {
        $id = 1;
        $criteria = $this->repository->createCriteria();
        $criteria->equalTo('id', $id);

        $entity = $this->repository->find($criteria);

        $this->assertNull($entity);
    }

    public function testCount()
    {
        $id = 2;
        $criteria = $this->repository->createCriteria();
        $criteria->equalTo('id', $id);

        $count = $this->repository->count($criteria);

        $this->assertEquals(1, $count);
    }

    public function testFindManyRowExists()
    {
        $id = 2;
        $criteria = $this->repository->createCriteria();
        $criteria->equalTo('id', $id);

        $entities = $this->repository->findMany($criteria);

        $this->assertInstanceOf('ArrayObject', $entities);
        $this->assertEquals($id, $entities[$id]->getId());
    }

    public function testAddInsert()
    {
        $newEntity = $this->repository->add(new Task(['name' => 'Some name', 'assignee' => 'AA']));

        $this->assertInstanceOf('T4webDomainTest\Task', $newEntity);

        $criteria = $this->repository->createCriteria();
        $criteria->equalTo('id', $newEntity->getId());
        $entity = $this->repository->find($criteria);

        $this->assertInstanceOf('T4webDomainTest\Task', $entity);
        $this->assertEquals($newEntity->getId(), $entity->getId());
    }

    public function testAddUpdate()
    {
        $id = 3;
        $criteria = $this->repository->createCriteria();
        $criteria->equalTo('id', $id);

        $entity = $this->repository->find($criteria);

        $this->assertInstanceOf('T4webDomainTest\Task', $entity);

        $entity->populate(['name' => date('His'), 'assignee' => date('is')]);

        $rowsAffected = $this->repository->add($entity);

        $this->assertEquals(1, $rowsAffected);
    }
/*
    public function testRemove()
    {
        $id = 4;

        $entity = $this->repository->find(new Criteria('id', 'equalTo', $id));

        $rowsAffected = $this->repository->remove($entity);

        $this->assertEquals(1, $rowsAffected);
    }
*/
}
