<?php

namespace T4webInfrastructureTest;

use T4webInfrastructure\CriteriaFactory;
use T4webInfrastructure\Config;
use Zend\Stdlib\ArrayObject;

class CriteriaFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CriteriaFactory
     */
    private $criteriaFactory;

    public function setUp()
    {
        $this->criteriaFactory = new CriteriaFactory();
    }

    public function testBuild()
    {
        $config = new Config(
            [
                'Task' => [
                    'table' => 'tasks',
                    'columnsAsAttributesMap' => [
                        'id' => 'id',
                        'user_id' => 'userId',
                        'status' => 'status',
                        'date_create' => 'dateCreate',
                    ],
                    'relations' => [
                        'User' => ['tasks.user_id', 'users.id'],
                    ],
                ],
                'User' => [
                    'table' => 'users',
                    'columnsAsAttributesMap' => [
                        'id' => 'id',
                        'name' => 'name',
                        'status' => 'status',
                        'date_create' => 'dateCreate',
                    ],
                ],
            ]
        );

        $criteria = $this->criteriaFactory->build(
            'Task',
            $config,
            [
                'status.equalTo' => 2,
                'dateCreate.greaterThan' => '2015-10-30',

                'relations' => [
                    'User' => [
                        'status.in' => [2, 3, 4],
                        'name.like' => 'gor'
                    ]
                ]
            ]
        );

        $this->assertInstanceOf('T4webDomainInterface\Infrastructure\CriteriaInterface', $criteria);
        $this->assertEquals('Task', $criteria->getEntityName());
        $this->assertEquals(
            [
                [
                    'name' => 'equalTo',
                    'attribute' => 'status',
                    'value' => 2,
                ],
                [
                    'name' => 'greaterThan',
                    'attribute' => 'dateCreate',
                    'value' => '2015-10-30',
                ],
            ],
            $criteria->getPredicate()
        );

        $relations = $criteria->getRelations();

        $this->assertEquals('User', $relations[0]->getEntityName());
        $this->assertEquals(
            [
                [
                    'name' => 'in',
                    'attribute' => 'status',
                    'values' => [2, 3, 4],
                ],
                [
                    'name' => 'like',
                    'attribute' => 'name',
                    'value' => 'gor',
                ],
            ],
            $relations[0]->getPredicate()
        );
    }
}
