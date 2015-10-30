<?php

namespace T4webInfrastructureTest;

use T4webInfrastructure\Criteria;

class CriteriaTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Criteria
     */
    private $criteria;

    public function setUp()
    {
        $this->criteria = new Criteria('users');
    }

    public function testConstruct()
    {
        $this->assertAttributeEquals('users', 'entityName', $this->criteria);
        $this->assertEquals('users', $this->criteria->getEntityName());
    }

    public function testPredicate()
    {
        $this->criteria->equalTo('id', 2);
        $this->criteria->notEqualTo('id', 3);
        $this->criteria->lessThan('id', 4);
        $this->criteria->greaterThan('id', 5);
        $this->criteria->greaterThanOrEqualTo('id', 6);
        $this->criteria->lessThanOrEqualTo('id', 7);
        $this->criteria->like('name', 'php');
        $this->criteria->isNull('id');
        $this->criteria->isNotNull('id');
        $this->criteria->in('type', [1,2,3]);
        $this->criteria->between('id', 1, 22);

        $this->assertEquals(
            [
                [
                    'name' => 'equalTo',
                    'attribute' => 'id',
                    'value' => 2,
                ],
                [
                    'name' => 'notEqualTo',
                    'attribute' => 'id',
                    'value' => 3,
                ],
                [
                    'name' => 'lessThan',
                    'attribute' => 'id',
                    'value' => 4,
                ],
                [
                    'name' => 'greaterThan',
                    'attribute' => 'id',
                    'value' => 5,
                ],
                [
                    'name' => 'greaterThanOrEqualTo',
                    'attribute' => 'id',
                    'value' => 6,
                ],
                [
                    'name' => 'lessThanOrEqualTo',
                    'attribute' => 'id',
                    'value' => 7,
                ],
                [
                    'name' => 'like',
                    'attribute' => 'name',
                    'value' => 'php',
                ],
                [
                    'name' => 'isNull',
                    'attribute' => 'id',
                ],
                [
                    'name' => 'isNotNull',
                    'attribute' => 'id',
                ],
                [
                    'name' => 'in',
                    'attribute' => 'type',
                    'values' => [1, 2, 3,],
                ],
                [
                    'name' => 'between',
                    'attribute' => 'id',
                    'minValue' => 1,
                    'maxValue' => 22,
                ],
            ],
            $this->criteria->getPredicate()
        );
    }

    public function testOrder()
    {
        $this->criteria->order('create_dt DESC');
        $this->criteria->order('vip_status');

        $this->assertEquals(
            ['create_dt DESC', 'vip_status'],
            $this->criteria->getOrder()
        );
    }

    public function testLimitOffset()
    {
        $this->criteria->limit(20);
        $this->criteria->offset(10);

        $this->criteria->limit(22);
        $this->criteria->offset(11);

        $this->assertEquals(22, $this->criteria->getLimit());
        $this->assertEquals(11, $this->criteria->getOffset());
    }

    public function testRelations()
    {
        $this->criteria->relation('photos')
            ->equalTo('status', 3)
            ->greaterThan('created_dt', '2015-10-30')
            ->limit(22)
            ->offset(11);

        $this->criteria->relation('locations')
            ->equalTo('countryId', 153);

        $relations = $this->criteria->getRelations();

        $this->assertCount(2, $relations);
        $this->assertInstanceOf('T4webInfrastructure\Criteria', $relations[0]);
        $this->assertInstanceOf('T4webInfrastructure\Criteria', $relations[1]);
        $this->assertEquals('photos', $relations[0]->getEntityName());
        $this->assertEquals('locations', $relations[1]->getEntityName());
    }

    public function testOrCriteria()
    {
        $this->criteria->in('status', [1, 2, 3])
            ->orCriteria()
            ->equalTo('type', 2);

        $orCriteria = $this->criteria->getOr();

        $this->assertCount(1, $orCriteria);
        $this->assertInstanceOf('T4webInfrastructure\Criteria', $orCriteria[0]);
        $this->assertEquals('users', $orCriteria[0]->getEntityName());
    }
}
