<?php

namespace T4webInfrastructureTest;

use T4webInfrastructure\CriteriaFactory;
use T4webInfrastructure\InMemoryRepository;
use T4webInfrastructure\Event\EntityChangedEvent;
use Zend\EventManager\EventManager;
use Zend\EventManager\StaticEventManager;
use Zend\EventManager\Event;
use T4webInfrastructure\Config;

class InMemoryRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InMemoryRepository
     */
    private $repository;

    /**
     * @var EventManager
     */
    private $em;

    public function setUp()
    {
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
        StaticEventManager::getInstance();
        $this->em = new EventManager();
        $this->em->addIdentifiers('Task\Infrastructure\InMemoryRepository');

        $this->repository = new InMemoryRepository(
            'Task',
            'ArrayObject',
            $criteriaFactory,
            $entityFactory,
            $this->em
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
        $this->repository->add(
            new Assets\Task(['id' => $id, 'name' => 'Some name', 'assignee' => 'AA'])
        );

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
        $this->repository->add(
            new Assets\Task(['name' => 'Some name', 'assignee' => 'AA'])
        );
        $this->repository->add(
            new Assets\Task(['name' => 'Some other name', 'assignee' => 'AA'])
        );
        $this->repository->add(
            new Assets\Task(['name' => 'Some other name', 'assignee' => 'MG'])
        );

        $count = $this->repository->count(['assignee_equalTo' => 'AA']);

        $this->assertEquals(2, $count);
    }

    public function testFindManyRowExists()
    {
        $this->repository->add(
            new Assets\Task(['id' => 1, 'name' => 'Some name', 'assignee' => 'AA'])
        );
        $this->repository->add(
            new Assets\Task(['id' => 2, 'name' => 'Some other name', 'assignee' => 'AA'])
        );
        $this->repository->add(
            new Assets\Task(['id' => 3, 'name' => 'Some xx name', 'assignee' => 'MG'])
        );

        $entities = $this->repository->findMany(['assignee_equalTo' => 'AA']);

        $this->assertInstanceOf('ArrayObject', $entities);
        $this->assertEquals(2, $entities->count());
        $this->assertEquals(1, $entities[1]->getId());
        $this->assertEquals(2, $entities[2]->getId());
    }

    public function testAddInsert()
    {
        $newEntity = $this->repository->add(
            new Assets\Task(['name' => 'Some name', 'assignee' => 'AA'])
        );

        $this->assertInstanceOf('T4webInfrastructureTest\Assets\Task', $newEntity);

        $entity = $this->repository->find(['id_equalTo' => $newEntity->getId()]);

        $this->assertInstanceOf('T4webInfrastructureTest\Assets\Task', $entity);
        $this->assertEquals($newEntity, $entity);
    }

    public function testAddUpdate()
    {
        $this->repository->add(
            new Assets\Task(['name' => 'Some name', 'assignee' => 'AA'])
        );

        $entity = $this->repository->find(['id_equalTo' => 1]);

        $this->assertInstanceOf('T4webInfrastructureTest\Assets\Task', $entity);

        $entity->populate(['name' => date('His'), 'assignee' => 'MA']);

        $rowsAffected = $this->repository->add($entity);

        $this->assertEquals(1, $rowsAffected);
    }

    public function testTriggerCreate()
    {
        $listener = new EventListener();

        $this->em->getSharedManager()->attach(
            'Task\Infrastructure\InMemoryRepository',
            'entity:T4webInfrastructureTest\Assets\Task:created',
            $listener
        );

        $this->repository->add(
            new Assets\Task(['name' => 'Some name', 'assignee' => 'AA'])
        );

        $this->assertEquals(1, $listener->called);
        $this->assertInstanceOf(Event::class, $listener->event);
        $this->assertInstanceOf(Assets\Task::class, $listener->event->getParam('entity'));
        $this->assertEquals('Some name', $listener->event->getParam('entity')->getName());
    }

    public function testTriggerDelete()
    {
        $listener = new EventListener();

        $this->em->getSharedManager()->attach(
            'Task\Infrastructure\InMemoryRepository',
            'entity:T4webInfrastructureTest\Assets\Task:deleted',
            $listener
        );

        $this->repository->remove(
            new Assets\Task(['name' => 'Some name', 'assignee' => 'AA'])
        );

        $this->assertEquals(1, $listener->called);
        $this->assertInstanceOf(Event::class, $listener->event);
        $this->assertInstanceOf(Assets\Task::class, $listener->event->getParam('entity'));
        $this->assertEquals('Some name', $listener->event->getParam('entity')->getName());
    }

    public function testTriggerChange()
    {
        $listener = new EventListener();
        $listenerAttribute = new EventListener();

        $this->em->getSharedManager()->attach(
            'Task\Infrastructure\InMemoryRepository',
            'entity:T4webInfrastructureTest\Assets\Task:changed',
            $listener
        );

        $this->em->getSharedManager()->attach(
            'Task\Infrastructure\InMemoryRepository',
            'attribute:T4webInfrastructureTest\Assets\Task:name:changed',
            $listenerAttribute
        );

        $this->repository->add(
            new Assets\Task(['id' => 1, 'name' => 'Some name', 'assignee' => 'AA'])
        );

        $this->repository->add(
            new Assets\Task(['id' => 1, 'name' => 'Updated name', 'assignee' => 'AA'])
        );

        $this->assertEquals(1, $listener->called);
        $this->assertInstanceOf(EntityChangedEvent::class, $listener->event);
        $this->assertInstanceOf(Assets\Task::class, $listener->event->getChangedEntity());
        $this->assertInstanceOf(Assets\Task::class, $listener->event->getOriginalEntity());
        $this->assertEquals('Some name', $listener->event->getOriginalEntity()->getName());
        $this->assertEquals('Updated name', $listener->event->getChangedEntity()->getName());

        $this->assertEquals(1, $listenerAttribute->called);
        $this->assertInstanceOf(EntityChangedEvent::class, $listener->event);
        $this->assertInstanceOf(Assets\Task::class, $listener->event->getChangedEntity());
        $this->assertInstanceOf(Assets\Task::class, $listener->event->getOriginalEntity());
        $this->assertEquals('Some name', $listener->event->getOriginalEntity()->getName());
        $this->assertEquals('Updated name', $listener->event->getChangedEntity()->getName());
    }
}

class EventListener
{
    public $called = 0;
    public $event;

    public function __invoke($event)
    {
        $this->called++;
        $this->event = $event;
    }
}
