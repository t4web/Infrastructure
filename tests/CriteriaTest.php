<?php

namespace T4webInfrastructureTest;

use T4webInfrastructure\Criteria;
use Zend\Db\Adapter\Adapter;
use T4webInfrastructure\Config;

class CriteriaTest extends \PHPUnit_Framework_TestCase
{
    private $dbAdapter;

    private $config;

    public function setUp()
    {
        $this->dbAdapter = new Adapter([
            'driver'         => 'Pdo',
            'dsn'            => 'mysql:dbname=board;host=localhost',
            'driver_options' => [
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
            ],
            'username' => 'board',
            'password' => '111',
        ]);

        $this->config = new Config(
            [
                'User' => [
                    'table' => 'users',
                    'columnsAsAttributesMap' => [
                        'id' => 'id',
                        'name' => 'name',
                        'status' => 'status',
                        'type' => 'type',
                        'project_id' => 'projectId',
                        'dt_create' => 'dtCreate',
                    ],
                    'relations' => [
                        'Photo' => ['photos.user_id', 'users.id'],
                        'Tag' => ['users_tags_link', 'user_id', 'tag_id'],
                    ],
                ],
                'Photo' => [
                    'table' => 'photos',
                    'columnsAsAttributesMap' => [
                        'status' => 'status',
                        'contest' => 'contest',
                    ]
                ],
                'Tag' => [
                    'table' => 'tags',
                    'columnsAsAttributesMap' => [
                        'type' => 'type'
                    ]
                ]
            ]
        );
    }

    public function testPredicates()
    {
        $criteria = new Criteria('User', $this->config);
        $criteria->equalTo('id', 2);
        $criteria->notEqualTo('id', 3);
        $criteria->lessThan('projectId', 4);
        $criteria->greaterThan('id', 5);
        $criteria->greaterThanOrEqualTo('id', 6);
        $criteria->lessThanOrEqualTo('id', 7);
        $criteria->like('name', 'php');
        $criteria->isNull('id');
        $criteria->isNotNull('id');
        $criteria->in('type', [1,2,3]);
        $criteria->between('id', 1, 22);
        $criteria->limit(4);
        $criteria->offset(1);
        $criteria->order('id');

        $select = $criteria->getQuery();

        $sql = $select->getSqlString($this->dbAdapter->getPlatform());

        $this->assertEquals(
            "SELECT `users`.* "
            . "FROM `users` "
            . "WHERE `users`.`id` = '2' "
            . "AND `users`.`id` != '3' "
            . "AND `users`.`project_id` < '4' "
            . "AND `users`.`id` > '5' "
            . "AND `users`.`id` >= '6' "
            . "AND `users`.`id` <= '7' "
            . "AND `users`.`name` LIKE 'php' "
            . "AND `users`.`id` IS NULL "
            . "AND `users`.`id` IS NOT NULL "
            . "AND `users`.`type` IN ('1', '2', '3') "
            . "AND `users`.`id` BETWEEN '1' AND '22' "
            . "ORDER BY `users`.`id` ASC "
            . "LIMIT '4' "
            . "OFFSET '1'",
            $sql
        );
    }

    public function testRelation()
    {
        $criteria = new Criteria('User', $this->config);
        $criteria->greaterThan('id', 5);
        $criteria->limit(20);
        $criteria->order('id');
        $criteria->relation('Photo')
            ->in('status', [2,3]);

        $select = $criteria->getQuery($criteria);

        $sql = $select->getSqlString($this->dbAdapter->getPlatform());

        $this->assertInstanceOf('Zend\Db\Sql\Select', $select);
        $this->assertEquals(
            "SELECT `users`.* "
            . "FROM `users` "
            . "INNER JOIN `photos` ON `photos`.`user_id` = `users`.`id` "
            . "WHERE `users`.`id` > '5' "
            . "AND `photos`.`status` IN ('2', '3') "
            . "ORDER BY `users`.`id` ASC "
            . "LIMIT '20'",
            $sql
        );
    }

    public function testRelationManyToMany()
    {
        $criteria = new Criteria('User', $this->config);
        $criteria->greaterThan('id', 5);
        $criteria->limit(20);
        $criteria->order('id');
        $criteria->relation('Tag')
            ->equalTo('type', 2);

        $select = $criteria->getQuery($criteria);

        $sql = $select->getSqlString($this->dbAdapter->getPlatform());

        $this->assertInstanceOf('Zend\Db\Sql\Select', $select);
        $this->assertEquals(
            "SELECT `users`.* "
            . "FROM `users` "
            . "INNER JOIN `users_tags_link` ON `users_tags_link`.`user_id` = `users`.`id` "
            . "INNER JOIN `tags` ON `users_tags_link`.`tag_id` = `tags`.`id` "
            . "WHERE `users`.`id` > '5' "
            . "AND `tags`.`type` = '2' "
            . "ORDER BY `users`.`id` ASC "
            . "LIMIT '20'",
            $sql
        );
    }
}
