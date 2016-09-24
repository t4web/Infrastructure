<?php

namespace T4webInfrastructureTest;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\EventManager\EventManager;
use T4webDomainInterface\Infrastructure\RepositoryInterface;
use T4webDomainInterface\EntityInterface;
use T4webDomainInterface\Infrastructure\CriteriaInterface;
use T4webInfrastructure\FinderAggregateRepository;
use T4webInfrastructure\Repository;
use T4webInfrastructure\CriteriaFactory;
use T4webInfrastructure\Mapper;
use T4webInfrastructure\Config;

class FinderAggregateRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FinderAggregateRepository
     */
    private $finderAggregateRepository;

    private $taskRepository;
    private $userRepository;

    public function setUpFake()
    {
        $tableGateway = $this->prophesize(TableGateway::class);
        $mapper = $this->prophesize(Mapper::class);

        $entityFactory = new Assets\EntityFactory('T4webInfrastructureTest\Assets\Task', 'ArrayObject');

        $this->taskRepository = $this->prophesize(RepositoryInterface::class);
        $this->userRepository = $this->prophesize(RepositoryInterface::class);

        $this->finderAggregateRepository = new FinderAggregateRepository(
            $tableGateway->reveal(),
            $mapper->reveal(),
            $entityFactory,
            $this->taskRepository->reveal(),
            ['User' => $this->userRepository->reveal()],
            []
        );
    }

    public function setUpReal()
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

        $this->taskRepository = new Repository(
            'Task',
            $criteriaFactory,
            $tableGateway,
            $mapper,
            $entityFactory,
            $em
        );

        $userConfig = new Config(
            [
                'User' => [
                    'table' => 'users',
                    'columnsAsAttributesMap' => [
                        'id' => 'id',
                        'name' => 'name',
                    ],
                ]
            ]
        );

        $userCriteriaFactory = new CriteriaFactory($userConfig);
        $tableGateway = new TableGateway('users', $dbAdapter);
        $mapper = new Mapper(
            [
                'id' => 'id',
                'name' => 'name',
            ]
        );
        $userEntityFactory = new Assets\EntityFactory('T4webInfrastructureTest\Assets\User', 'ArrayObject');

        $this->userRepository = new Repository(
            'User',
            $userCriteriaFactory,
            $tableGateway,
            $mapper,
            $userEntityFactory,
            $em
        );;

        $this->finderAggregateRepository = new FinderAggregateRepository(
            $tableGateway,
            $mapper,
            $entityFactory,
            $this->taskRepository,
            ['User' => $this->userRepository],
            ['User' => ['tasks.assignee', 'user.id']]
        );
    }

    public function testRemove()
    {
        $this->setUpFake();

        $this->setExpectedException(\RuntimeException::class);
        $entity = $this->prophesize(EntityInterface::class);
        $this->finderAggregateRepository->remove($entity->reveal());
    }

    public function testAdd()
    {
        $this->setUpFake();

        $this->setExpectedException(\RuntimeException::class);
        $entity = $this->prophesize(EntityInterface::class);
        $this->finderAggregateRepository->add($entity->reveal());
    }

    public function testCreateCriteria()
    {
        $this->setUpFake();

        $filter = ['id' => 333];

        $this->taskRepository->createCriteria($filter)->shouldBeCalled();

        $this->finderAggregateRepository->createCriteria($filter);
    }

    public function testCount()
    {
        $this->setUpFake();

        $criteria = ['id' => 333];

        $this->taskRepository->count($criteria)->shouldBeCalled();

        $this->finderAggregateRepository->count($criteria);
    }

    public function testWith()
    {
        $this->setUpFake();

        $this->finderAggregateRepository->with('User');
    }

    public function testWithBadRelatedEntity()
    {
        $this->setUpFake();

        $this->setExpectedException(\RuntimeException::class);
        $this->finderAggregateRepository->with('Payment');
    }

    public function testFindWithEmptyRelations()
    {
        $this->setUpFake();

        $criteria = $this->prophesize(CriteriaInterface::class);

        $this->taskRepository->find($criteria)->shouldBeCalled();
        
        $this->finderAggregateRepository->find($criteria);
    }

    public function testFindWithRelations()
    {
        $this->setUpReal();

        $result = $this->finderAggregateRepository->with('User')->find(['id_equalTo' => 2]);

        $this->assertInstanceOf('T4webInfrastructureTest\Assets\Task', $result);
        $this->assertInstanceOf('T4webInfrastructureTest\Assets\User', $result->getAssigneeUser());
        $this->assertEquals('John Doe', $result->getAssigneeUser()->getName());
    }

    public function testFindManyWithRelations()
    {
        $this->setUpReal();

        $result = $this->finderAggregateRepository->with('User')->findMany(['status_equalTo' => 0]);

        foreach ($result as $task) {
            if ($task->getId() == 2) {
                $this->assertInstanceOf('T4webInfrastructureTest\Assets\User', $task->getAssigneeUser());
                $this->assertEquals('John Doe', $task->getAssigneeUser()->getName());
            } else {
                $this->assertInstanceOf('T4webInfrastructureTest\Assets\Task', $task);
                $this->assertNull($task->getAssigneeUser());
            }
        }
    }
}
