<?php

namespace T4webInfrastructureTest;

use T4webInfrastructure\Criteria;
use Zend\Db\Adapter\Adapter;
use T4webInfrastructure\Config;

class CriteriaTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Criteria
     */
    private $criteria;

    private $dbAdapter;

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
                        'name' => 'name',
                        'status' => 'status',
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

    public function testRelation()
    {
        $criteria = new Criteria('User', $this->config);
        $criteria->equalTo('status', 1);
        $criteria->greaterThan('dtCreate', '2015');
        $criteria->relation('Photo')
            ->equalTo('status', 2)
            ->isNotNull('contest');

        $select = $criteria->getSelect();

        $sql = $select->getSqlString($this->dbAdapter->getPlatform());

        $this->assertEquals(
            "SELECT `users`.* "
            . "FROM `users` "
            . "INNER JOIN `photos` ON `photos`.`user_id` = `users`.`id` "
            . "WHERE `users`.`status` = '1' "
            . "AND `users`.`dt_create` > '2015' "
            . "AND `photos`.`status` = '2' "
            . "AND `photos`.`contest` IS NOT NULL",
            $sql
        );

        ///$this->assertAttributeEquals('users', 'entityName', $this->criteria);
        //$this->assertEquals('users', $this->criteria->getEntityName());
    }
}
