<?php

namespace T4webInfrastructureTest\Assets;

use T4webDomainInterface\Infrastructure\CriteriaInterface;

class Active
{
    public function __invoke(CriteriaInterface $criteria, $value)
    {
        $criteria->equalTo('status', 2);
    }
}
