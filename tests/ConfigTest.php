<?php

namespace T4webDomainTest;

use T4webInfrastructure\Config;

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

    public function testGetEntityClass()
    {
        $entityClass = $this->config->getEntityClass('Task');

        $this->assertEquals($this->entityMap['Task']['entityClass'], $entityClass);
    }

    public function testGetRelationExpression()
    {
        $joinOn = $this->config->getRelationExpression('Task', 'User');

        $this->assertEquals('tasks.assignee_id = user.id', $joinOn);
    }

    public function testIsRelationManyToMany()
    {
        $this->assertFalse($this->config->isRelationManyToMany('Task', 'User'));
        $this->assertTrue($this->config->isRelationManyToMany('Task', 'Tag'));
    }

    public function testGetRelationManyToMany()
    {
        list($linkTable, $joinOn1, $joinOn2) = $this->config->getRelationManyToMany('Task', 'Tag');

        $this->assertEquals('tasks_tags_link', $linkTable);
        $this->assertEquals('task_id', $joinOn1);
        $this->assertEquals('tag_id', $joinOn2);
    }

    public function testGetColumnsAsAttributesMap()
    {
        $columnsMap = $this->config->getColumnsAsAttributesMap('Task');

        $this->assertEquals($this->entityMap['Task']['columnsAsAttributesMap'], $columnsMap);
    }

    public function testGetCriteriaMap()
    {
        $criteriaMap = $this->config->getCriteriaMap('Task');

        $this->assertEquals($this->entityMap['Task']['criteriaMap'], $criteriaMap);
    }
}
