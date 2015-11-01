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
                'columnsAsAttributesMap' => [
                    'id' => 'id',
                    'projectId' => 'project_id',
                    'name' => 'name',
                    'assigneeId' => 'assignee_id',
                    'status' => 'status',
                    'type' => 'type',
                ],
                'relations' => [
                    'User' => 'tasks.assignee_id = user.id',
                    'Project' => 'tasks.project_id = projects.id',
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

    public function testGetRelationExpression()
    {
        $joinOn = $this->config->getRelationExpression('Task', 'User');

        $this->assertEquals($this->entityMap['Task']['relations']['User'], $joinOn);
    }

}
