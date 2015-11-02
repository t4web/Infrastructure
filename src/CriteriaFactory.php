<?php

namespace T4webInfrastructure;

use T4webDomainInterface\Infrastructure\CriteriaInterface;
use RuntimeException;

class CriteriaFactory
{
    /**
     * @param string $entityName
     * @param array $filter
     * @return CriteriaInterface
     */
    public function build($entityName, array $filter = [])
    {
        if (!is_string($entityName)) {
            throw new RuntimeException(sprintf('Entity mame must be string, %s given', gettype($entityName)));
        }

        $criteria = new Criteria($entityName);

        if (empty($filter)) {
            return $criteria;
        }

        if (isset($filter['relations'])) {
            $relations = $filter['relations'];
            unset($filter['relations']);
        }

        $this->applyFilter($criteria, $filter);

        foreach ($relations as $relationEntity => $relationFilter) {
            $relation = $criteria->relation($relationEntity);
            $this->applyFilter($relation, $relationFilter);
        }


        return $criteria;
    }

    /**
     * @param CriteriaInterface $criteria
     * @param array $filter
     * @return CriteriaInterface
     */
    private function applyFilter(CriteriaInterface $criteria, array $filter)
    {
        foreach($filter as $expressionString => $value) {
            $expressionArray = explode('.', $expressionString);

            if (count($expressionArray) > 2) {
                continue;
            }

            list($attribute, $method) = $expressionArray;

            if (!method_exists($criteria, $method)) {
                throw new RuntimeException(sprintf('Predicate %s does not exists', $method));
            }

            call_user_func([$criteria, $method], $attribute, $value);
        }

        return $criteria;
    }

}