<?php

namespace T4webInfrastructureTest;

use T4webInfrastructure\CriteriaFactory;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\EventManager\EventManager;
use T4webInfrastructure\Repository;
use T4webInfrastructure\Mapper;
use T4webInfrastructure\Config;

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
            'driver_options' => [
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
            ],
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
            ]
        );

        $entityFactory = new Assets\EntityFactory('T4webInfrastructureTest\Assets\Task', 'ArrayObject');

        $config = new Config(
            [
                'Task' => [
                    'table' => 'tasks',
                    'columnsAsAttributesMap' => [
                        'id' => 'id',
                        'projectId' => 'project_id',
                        'name' => 'name',
                        'assigneeId' => 'assignee_id',
                        'status' => 'status',
                        'date_create' => 'dateCreate',
                        'type' => 'type',
                    ],
                ]
            ]
        );
        $criteriaFactory = new CriteriaFactory($config);
        $em = new EventManager();

        $this->repository = new Repository(
            'Task',
            $criteriaFactory,
            $tableGateway,
            $mapper,
            $entityFactory,
            $em
        );
    }

    public function testCreateCriteria()
    {
        $criteria = $this->repository->createCriteria();

        $this->assertInstanceOf('T4webDomainInterface\Infrastructure\CriteriaInterface', $criteria);
    }

    public function testCreateCriteriaWithFilter()
    {
        $criteria = $this->repository->createCriteria(['status.equalTo' => 2]);

        $this->assertInstanceOf('T4webDomainInterface\Infrastructure\CriteriaInterface', $criteria);
    }

    public function testFindRowExists()
    {
        $id = 2;
        $criteria = $this->repository->createCriteria();
        $criteria->equalTo('id', $id);

        $entity = $this->repository->find($criteria);

        $this->assertInstanceOf('T4webInfrastructureTest\Assets\Task', $entity);
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

    public function testFindByIdRowExists()
    {
        $id = 2;

        $entity = $this->repository->findById($id);

        $this->assertInstanceOf('T4webInfrastructureTest\Assets\Task', $entity);
        $this->assertEquals($id, $entity->getId());
    }

    public function testFindByIdRowNotExists()
    {
        $id = 1;
        $entity = $this->repository->findById($id);

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
        $newEntity = $this->repository->add(new Assets\Task(['name' => 'Some name', 'assignee' => 'AA']));

        $this->assertInstanceOf('T4webInfrastructureTest\Assets\Task', $newEntity);

        $criteria = $this->repository->createCriteria();
        $criteria->equalTo('id', $newEntity->getId());
        $entity = $this->repository->find($criteria);

        $this->assertInstanceOf('T4webInfrastructureTest\Assets\Task', $entity);
        $this->assertEquals($newEntity->getId(), $entity->getId());
    }

    public function testAddUpdate()
    {
        $id = 3;
        $criteria = $this->repository->createCriteria();
        $criteria->equalTo('id', $id);

        $entity = $this->repository->find($criteria);

        $this->assertInstanceOf('T4webInfrastructureTest\Assets\Task', $entity);

        $entity->populate(['name' => date('His'), 'assignee' => 'MA']);

        $rowsAffected = $this->repository->add($entity);

        $this->assertEquals(1, $rowsAffected);
    }
}
