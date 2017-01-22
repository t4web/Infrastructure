<?php

namespace T4webInfrastructureTest;

use T4webInfrastructure\InMemoryCriteria;

class InMemoryCriteriaTest extends \PHPUnit_Framework_TestCase
{
    private $entities;

    public function SetUp()
    {
        $this->entities = [
            [ 'id' => 2, 'name' => 'Max', 'lastName' => null ],
            [ 'id' => 3, 'name' => 'Fedot', 'lastName' => 'Demidoff' ],
            [ 'id' => 4, 'name' => 'Ivan', 'lastName' => 'Kravcov' ],
        ];
    }

    public function testPredicate_equalTo()
    {
        $criteria = new InMemoryCriteria('User');
        $criteria->equalTo('id', 2);

        $callback = $criteria->getQuery();

        $result = $this->applyCriteria($callback);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey(2, $result);
    }

    public function testPredicate_notEqualTo()
    {
        $criteria = new InMemoryCriteria('User');
        $criteria->notEqualTo('id', 2);

        $callback = $criteria->getQuery();

        $result = $this->applyCriteria($callback);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey(3, $result);
        $this->assertArrayHasKey(4, $result);
    }

    public function testPredicate_lessThan()
    {
        $criteria = new InMemoryCriteria('User');
        $criteria->lessThan('id', 3);

        $callback = $criteria->getQuery();

        $result = $this->applyCriteria($callback);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey(2, $result);
    }

    public function testPredicate_greaterThan()
    {
        $criteria = new InMemoryCriteria('User');
        $criteria->greaterThan('id', 3);

        $callback = $criteria->getQuery();

        $result = $this->applyCriteria($callback);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey(4, $result);
    }

    public function testPredicate_greaterThanOrEqualTo()
    {
        $criteria = new InMemoryCriteria('User');
        $criteria->greaterThanOrEqualTo('id', 3);

        $callback = $criteria->getQuery();

        $result = $this->applyCriteria($callback);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey(3, $result);
        $this->assertArrayHasKey(4, $result);
    }

    public function testPredicate_lessThanOrEqualTo()
    {
        $criteria = new InMemoryCriteria('User');
        $criteria->lessThanOrEqualTo('id', 3);

        $callback = $criteria->getQuery();

        $result = $this->applyCriteria($callback);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey(2, $result);
        $this->assertArrayHasKey(3, $result);
    }

    public function testPredicate_like()
    {
        $criteria = new InMemoryCriteria('User');
        $criteria->like('name', 'a');

        $callback = $criteria->getQuery();

        $result = $this->applyCriteria($callback);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey(2, $result);
        $this->assertArrayHasKey(4, $result);
        $this->assertEquals('Max', $result[2]['name']);
        $this->assertEquals('Ivan', $result[4]['name']);
    }

    public function testPredicate_isNull()
    {
        $criteria = new InMemoryCriteria('User');
        $criteria->isNull('lastName');

        $callback = $criteria->getQuery();

        $result = $this->applyCriteria($callback);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey(2, $result);
    }

    public function testPredicate_isNotNull()
    {
        $criteria = new InMemoryCriteria('User');
        $criteria->isNotNull('lastName');

        $callback = $criteria->getQuery();

        $result = $this->applyCriteria($callback);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey(3, $result);
        $this->assertArrayHasKey(4, $result);
    }

    public function testPredicate_in()
    {
        $criteria = new InMemoryCriteria('User');
        $criteria->in('id', [3, 4]);

        $callback = $criteria->getQuery();

        $result = $this->applyCriteria($callback);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey(3, $result);
        $this->assertArrayHasKey(4, $result);
    }

    public function testPredicate_between()
    {
        $criteria = new InMemoryCriteria('User');
        $criteria->between('id', 3, 4);

        $callback = $criteria->getQuery();

        $result = $this->applyCriteria($callback);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey(3, $result);
        $this->assertArrayHasKey(4, $result);
    }

    public function testSeveralPredicates()
    {
        $criteria = new InMemoryCriteria('User');
        $criteria->notEqualTo('id', 2);
        $criteria->lessThan('id', 5);
        $criteria->greaterThan('id', 2);
        $criteria->greaterThanOrEqualTo('id', 3);
        $criteria->lessThanOrEqualTo('id', 4);
        $criteria->like('name', 'Ivan');
        $criteria->isNotNull('lastName');
        $criteria->in('id', [2, 3, 4]);
        $criteria->between('id', 1, 22);

        $callback = $criteria->getQuery();

        $result = $this->applyCriteria($callback);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey(4, $result);
    }

    private function applyCriteria($callback)
    {
        $result = [];
        foreach ($this->entities as $entity) {
            if ($callback($entity)) {
                $result[$entity['id']] = $entity;
            }
        }

        return $result;
    }
}
