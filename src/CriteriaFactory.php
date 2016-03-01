<?php

namespace T4webInfrastructure;

use T4webDomainInterface\Infrastructure\CriteriaInterface;
use RuntimeException;

class CriteriaFactory
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * CriteriaFactory constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

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

        $criteria = new Criteria($entityName, $this->config);

        if (empty($filter)) {
            return $criteria;
        }

        $relations = [];
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
        foreach ($filter as $expressionString => $value) {

            $criteriaMap = $this->config->getCriteriaMap($criteria->getEntityName());
            if (isset($criteriaMap[$expressionString])) {
                $expressionString = $criteriaMap[$expressionString];
            }

            if (in_array($expressionString, ['limit', 'offset'])) {
                $value = (int)$value;
                if ($value < 0) {
                    throw new RuntimeException(
                        sprintf('Predicate %s must unsigned int, %s given', $expressionString, $value)
                    );
                }

                $criteria->{$expressionString}($value);
                continue;
            }

            if ($expressionString == 'page') {
                if (!isset($filter['limit'])) {
                    throw new RuntimeException(sprintf('Predicate %s require limit', $expressionString));
                }
                $criteria->offset($filter['limit'] * ($value - 1));
                continue;
            }

            if ($expressionString == 'order') {
                $criteria->order($value);
                continue;
            }

            if (strpos($expressionString, '.') !== false) {
                $expressionArray = explode('.', $expressionString);
            } else {
                $expressionArray = explode('_', $expressionString);
            }

            if (count($expressionArray) > 2) {
                continue;
            }

            if (count($expressionArray) == 2) {
                list($attribute, $method) = $expressionArray;

                if (in_array($method, ['isNull', 'isNotNull']) && $value) {
                    $criteria->{$method}($attribute);
                    continue;
                }

                if ($method == 'between') {
                    if (!is_array($value) || !isset($value[0]) || !isset($value[1])) {
                        throw new RuntimeException(
                            sprintf('Predicate %s must contain array [MIN_VALUE, MAX_VALUE], ', $method)
                        );
                    }

                    $criteria->between($attribute, $value[0], $value[1]);
                    continue;
                }

                if (!method_exists($criteria, $method)) {
                    throw new RuntimeException(sprintf('Predicate %s does not exists', $method));
                }

                call_user_func([$criteria, $method], $attribute, $value);
            }

            if (count($expressionArray) == 1) {
                $customCriteria = ucfirst($expressionArray[0]);
                $entityNamespace = $this->config->getNamespace($criteria->getEntityName());
                $customCriteriaClass = "$entityNamespace\\Infrastructure\\Criteria\\$customCriteria";

                if (!class_exists($customCriteriaClass)) {
                    throw new RuntimeException(
                        sprintf('Wrong criteria %s. Class %s does not exists.', $customCriteria, $customCriteriaClass)
                    );
                }

                $customCriteriaInstance = new $customCriteriaClass();

                if (!is_callable($customCriteriaInstance)) {
                    throw new RuntimeException(
                        sprintf(
                            'Wrong criteria %s. Object of type %s is not callable.',
                            $customCriteria,
                            $customCriteriaClass
                        )
                    );
                }

                $customCriteriaInstance($criteria, $value);
            }
        }

        return $criteria;
    }
}
