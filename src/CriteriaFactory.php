<?php

namespace T4webInfrastructure;

use T4webDomainInterface\Infrastructure\CriteriaInterface;

class CriteriaFactory
{
    /**
     * @param array $filter
     * @return CriteriaInterface[]
     */
    public function build(array $filter = [])
    {
        return [new Criteria()];
    }

}