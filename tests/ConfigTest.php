<?php

namespace T4webDomainTest;

use T4webInfrastructure\Config;
use T4webInfrastructure\ConfigException;

class ConfigTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Config
     */
    private $config;

    /**
     * @var array
     */
    private $entityMap;

    public function setUp()
    {
        $this->entityMap = [
            'Task' => [
                'table' => 'tasks',
                'entityClass' => 'Tasks\Task\Task',
                'primaryKey' => 'id',
                'sequence' => 'id_sequence',
                'columnsAsAttributesMap' => [
                    'id' => 'id',
                    'projectId' => 'project_id',
                    'name' => 'name',
                    'assigneeId' => 'assignee_id',
                    'status' => 'status',
                    'type' => 'type',
                ],
                'relations' => [
                    'User' => ['tasks.assignee_id', 'user.id'],
                    'Tag' => ['tasks_tags_link', 'task_id', 'tag_id'],
                    'Bad' => [],
                ],
                'criteriaMap' => [
                    'id' => 'id_equalTo',
                ],
            ],
        ];

        $this->config = new Config($this->entityMap);
    }

    public function testConstructor()
    {
        $this->assertAttributeEquals($this->entityMap, 'entityMap', $this->config);
    }

    public function testGetTable()
    {
        $table = $this->config->getTable('Task');

        $this->assertEquals($this->entityMap['Task']['table'], $table);
    }

    public function testGetTableException()
    {
        $this->setExpectedException(ConfigException::class);
        $table = $this->config->getTable('User');
    }

    public function testGetEntityClass()
    {
        $entityClass = $this->config->getEntityClass('Task');

        $this->assertEquals($this->entityMap['Task']['entityClass'], $entityClass);
    }

    public function testGetEntityClassException()
    {
        $this->setExpectedException(ConfigException::class);
        $entityClass = $this->config->getEntityClass('User');
    }

    public function testGetFieldException()
    {
        $this->setExpectedException(ConfigException::class);
        $this->config->getFiled('User', 'name');
    }

    public function testGetFieldNotExistsException()
    {
        $this->setExpectedException(ConfigException::class);
        $this->config->getFiled('Task', 'amount');
    }

    public function testGetRelationExpression()
    {
        $joinOn = $this->config->getRelationExpression('Task', 'User');

        $this->assertEquals('tasks.assignee_id = user.id', $joinOn);
    }

    public function testGetRelationExpressionException()
    {
        $this->setExpectedException(ConfigException::class);
        $joinOn = $this->config->getRelationExpression('Task', 'Statistic');
    }

    public function testGetRelationExpressionBadException()
    {
        $this->setExpectedException(ConfigException::class);
        $joinOn = $this->config->getRelationExpression('Task', 'Bad');
    }

    public function testIsRelationManyToMany()
    {
        $this->assertFalse($this->config->isRelationManyToMany('Task', 'User'));
        $this->assertTrue($this->config->isRelationManyToMany('Task', 'Tag'));
    }

    public function testIsRelationManyToManyException()
    {
        $this->setExpectedException(ConfigException::class);
        $this->config->isRelationManyToMany('Task', 'Statistic');
    }

    public function testGetRelationManyToMany()
    {
        list($linkTable, $joinOn1, $joinOn2) = $this->config->getRelationManyToMany('Task', 'Tag');

        $this->assertEquals('tasks_tags_link', $linkTable);
        $this->assertEquals('task_id', $joinOn1);
        $this->assertEquals('tag_id', $joinOn2);
    }

    public function testGetRelationManyToManyException()
    {
        $this->setExpectedException(ConfigException::class);
        $this->config->getRelationManyToMany('Task', 'Statistic');
    }

    public function testGetColumnsAsAttributesMap()
    {
        $columnsMap = $this->config->getColumnsAsAttributesMap('Task');

        $this->assertEquals($this->entityMap['Task']['columnsAsAttributesMap'], $columnsMap);
    }

    public function testGetColumnsAsAttributesMapException()
    {
        $this->setExpectedException(ConfigException::class);
        $this->config->getColumnsAsAttributesMap('Bad');
    }

    public function testGetCriteriaMap()
    {
        $criteriaMap = $this->config->getCriteriaMap('Task');

        $this->assertEquals($this->entityMap['Task']['criteriaMap'], $criteriaMap);
    }

    public function testGetPrimaryKey()
    {
        $this->assertEquals($this->entityMap['Task']['primaryKey'], $this->config->getPrimaryKey('Task'));
    }

    public function testGetSequence()
    {
        $this->assertEquals($this->entityMap['Task']['sequence'], $this->config->getSequence('Task'));
    }

    public function testGetNamespace()
    {
        $this->assertEquals('Tasks\Task', $this->config->getNamespace('Task'));
    }
}
