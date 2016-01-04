<?php

namespace T4webDomainTest;

use Zend\Db\Adapter\Adapter;
use T4webInfrastructure\QueryBuilder;
use T4webInfrastructure\Criteria;
use T4webInfrastructure\Config;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;

class QueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    private $dbAdapter;

    /**
     * @var Config
     */
    private $config;

    public function setUp()
    {
        $this->dbAdapter = new Adapter([
            'driver'         => 'Pdo',
            'dsn'            => 'mysql:dbname=board;host=localhost',
            'driver_options' => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
            ),
            'username' => 'board',
            'password' => '111',
        ]);

        $this->config = new Config(
            [
                'User' => [
                    'table' => 'users',
                    'columnsAsAttributesMap' => [
                        'id' => 'id',
                        'project_id' => 'projectId',
                        'name' => 'name',
                        'assignee_id' => 'assigneeId',
                        'status' => 'status',
                        'type' => 'type',
                    ],
                    'relations' => [
                        'Photo' => ['photos.user_id', 'users.id'],
                        'Tag' => ['users_tags_link', 'user_id', 'tag_id'],
                    ],
                ],
                'Photo' => [
                    'table' => 'photos',
                    'columnsAsAttributesMap' => [
                        'status' => 'status'
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

    public function testPredicateGetSelect()
    {
        $qb = new QueryBuilder($this->config);

        $criteria = new Criteria('User');
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

        $select = $qb->getSelect($criteria);

        $sql = $select->getSqlString($this->dbAdapter->getPlatform());

        $this->assertInstanceOf('Zend\Db\Sql\Select', $select);
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

    public function testRelationGetSelect()
    {
        $qb = new QueryBuilder($this->config);

        $criteria = new Criteria('User');
        $criteria->greaterThan('id', 5);
        $criteria->limit(20);
        $criteria->order('id');
        $criteria->relation('Photo')
            ->in('status', [2,3]);

        $select = $qb->getSelect($criteria);

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

    public function testRelationManyToManyGetSelect()
    {
        $qb = new QueryBuilder($this->config);

        $criteria = new Criteria('User');
        $criteria->greaterThan('id', 5);
        $criteria->limit(20);
        $criteria->order('id');
        $criteria->relation('Tag')
            ->equalTo('type', 2);

        $select = $qb->getSelect($criteria);

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

    public function testOrGetSelect()
    {
        $qb = new QueryBuilder($this->config);

        $criteria = new Criteria('User');
        $criteria->greaterThan('id', 5);
        $criteria->orCriteria()
            ->lessThan('id', 50)
            ->notEqualTo('id', 40);
        $criteria->limit(20);
        $criteria->order('id');
        $criteria->relation('Photo')
            ->in('status', [2,3]);

        $select = $qb->getSelect($criteria);

        $sql = $select->getSqlString($this->dbAdapter->getPlatform());

        $this->assertInstanceOf('Zend\Db\Sql\Select', $select);
        $this->assertEquals(
            "SELECT `users`.* "
            . "FROM `users` "
            . "INNER JOIN `photos` ON `photos`.`user_id` = `users`.`id` "
            . "WHERE `users`.`id` > '5' "
            . "OR (`users`.`id` < '50' AND `users`.`id` != '40') "
            . "AND `photos`.`status` IN ('2', '3') "
            . "ORDER BY `users`.`id` ASC "
            . "LIMIT '20'",
            $sql
        );
    }

    public function testAndOrGetSelect()
    {

        $select = new Select();
        $select->from('users');

        $where = new Where();

            $where4 = new Where();
                $where2 = new Where();
                $where2->like('bax', 'bar');
                $where3 = new Where();
                $where3->like('foo', 'bar');
                $where5 = new Where();
                $where5->like('foo2', 'bar');
            $where4->orPredicate($where2);
            $where4->orPredicate($where3);
            $where4->orPredicate($where5);

        $where->equalTo('id', 5);
        $where->andPredicate($where4);

        $select->where($where);
        die(var_dump($select->getSqlString($this->dbAdapter->getPlatform())));

        $qb = new QueryBuilder($this->config);

        $criteria = new Criteria('User');
        $criteria->greaterThan('id', 5);
        $criteria->andCriteria()
            ->like('foo', 'bar')
            ->orCriteria()
                ->like('foo', 'bar');
        $criteria->limit(20);
        $criteria->order('id');

        $select = $qb->getSelect($criteria);

        $sql = $select->getSqlString($this->dbAdapter->getPlatform());

        $this->assertInstanceOf('Zend\Db\Sql\Select', $select);
        $this->assertEquals(
            "SELECT `users`.* FROM `users` WHERE `id` = '5' AND ((`bax` LIKE 'bar') OR (`foo` LIKE 'bar') OR (`foo2` LIKE 'bar'))",
            $sql
        );
    }
}
