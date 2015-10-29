<?php

namespace T4webInfrastructureTest;

use T4webInfrastructure\Criteria;

class CriteriaTest extends \PHPUnit_Framework_TestCase
{

    public function testConstruct()
    {
        $criteria = new Criteria('users');

        $this->assertAttributeEquals('users', 'entityName', $criteria);
        $this->assertEquals('users', $criteria->getEntityName());
    }
}
