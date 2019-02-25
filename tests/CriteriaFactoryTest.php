<?php

namespace T4webInfrastructureTest;

use T4webInfrastructure\CriteriaFactory;
use T4webInfrastructure\InMemoryCriteria;
use T4webInfrastructure\Config;

class CriteriaFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CriteriaFactory
     */
    private $criteriaFactory;

    public function setUp()
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
                    'criteriaMap' => [
                        'date_more' => 'dateCreate.greaterThan',
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

        $this->criteriaFactory = new CriteriaFactory($config);
    }

    public function testBuild()
    {
        $criteria = $this->criteriaFactory->build(
            'Task',
            [
                'T4webInfrastructureTest\Assets\Active' => true,
                'date_more' => '2015-10-30',
                'id.isNotNull' => true,
                'dateCreate.between' => ['2015-10-30', '2015-10-31'],

                'relations' => [
                    'User' => [
                        'status.in' => [2, 3, 4],
                        'name.like' => 'gor'
                    ]
                ],
                'limit' => 5
            ]
        );

        $this->assertInstanceOf('T4webDomainInterface\Infrastructure\CriteriaInterface', $criteria);
        $this->assertEquals('Task', $criteria->getEntityName());

        /** @var \Zend\Db\Sql\Select $select */
        $select = $criteria->getQuery();

        $dbAdapter = new \Zend\Db\Adapter\Adapter([
            'driver'         => 'Pdo',
            'dsn'            => 'mysql:dbname=board;host=localhost',
            'driver_options' => [
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
            ],
            'username' => 'board',
            'password' => '111',
        ]);

        $this->assertEquals(
            "SELECT `tasks`.*"
            . " FROM `tasks`"
            . " INNER JOIN `users` ON `tasks`.`user_id` = `users`.`id`"
            . " WHERE `tasks`.`status` = '2'"
            . " AND `tasks`.`date_create` > '2015-10-30'"
            . " AND `tasks`.`id` IS NOT NULL"
            . " AND `tasks`.`date_create` BETWEEN '2015-10-30' AND '2015-10-31'"
            . " AND `users`.`status` IN ('2', '3', '4')"
            . " AND `users`.`name` LIKE 'gor'"
            . " LIMIT '5'",
            $select->getSqlString($dbAdapter->getPlatform())
        );
    }

    public function testBuildWithNotCallableCustomCriteria()
    {
        $this->setExpectedException(\RuntimeException::class);

        $criteria = $this->criteriaFactory->build(
            'Task',
            [
                'T4webInfrastructureTest\Assets\ActiveNotCallable' => true,
                'date_more' => '2015-10-30',

                'relations' => [
                    'User' => [
                        'status.in' => [2, 3, 4],
                        'name.like' => 'gor'
                    ]
                ],
                'limit' => 5
            ]
        );
    }

    public function testBuildInMemoryCriteria()
    {
        $criteria = $this->criteriaFactory->buildInMemory(
            'Task',
            [
                'date_more' => '2015-10-30',
                'id_isNotNull' => true,
                'dateCreate_between' => ['2015-10-30', '2015-10-31'],
            ]
        );

        $this->assertInstanceOf(InMemoryCriteria::class, $criteria);
        $this->assertEquals('Task', $criteria->getEntityName());
    }
}
