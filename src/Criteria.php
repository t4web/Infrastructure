<?php

namespace T4webInfrastructure;

use T4webDomainInterface\Infrastructure\CriteriaInterface;


class Criteria implements CriteriaInterface
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var CriteriaInterface[]
     */
    protected $relations = [];

    /**
     * @var CriteriaInterface[]
     */
    protected $orCriteria = [];

    /**
     * @var array
     */
    protected $predicate = [];

    /**
     * @var int
     */
    protected $limit;

    /**
     * @var int
     */
    protected $offset;

    /**
     * @var array
     */
    protected $order = [];

    /**
     * @param string $entityName
     */
    public function __construct($entityName)
    {
        $this->entityName = $entityName;
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @param string $attribute
     * @param bool|int|float|string $value
     * @return $this
     */
    public function equalTo($attribute, $value)
    {
        $this->predicate[] = [
            'name' => 'equalTo',
            'attribute' => $attribute,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * @param string $attribute
     * @param bool|int|float|string $value
     * @return $this
     */
    public function notEqualTo($attribute, $value)
    {
        $this->predicate[] = [
            'name' => 'notEqualTo',
            'attribute' => $attribute,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * @param string $attribute
     * @param int|float $value
     * @return $this
     */
    public function lessThan($attribute, $value)
    {
        $this->predicate[] = [
            'name' => 'lessThan',
            'attribute' => $attribute,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * @param string $attribute
     * @param int|float $value
     * @return $this
     */
    public function greaterThan($attribute, $value)
    {
        $this->predicate[] = [
            'name' => 'greaterThan',
            'attribute' => $attribute,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * @param string $attribute
     * @param int|float $value
     * @return $this
     */
    public function greaterThanOrEqualTo($attribute, $value)
    {
        $this->predicate[] = [
            'name' => 'greaterThanOrEqualTo',
            'attribute' => $attribute,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * @param string $attribute
     * @param int|float $value
     * @return $this
     */
    public function lessThanOrEqualTo($attribute, $value)
    {
        $this->predicate[] = [
            'name' => 'lessThanOrEqualTo',
            'attribute' => $attribute,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * @param string $attribute
     * @param int|float $value
     * @return $this
     */
    public function like($attribute, $value)
    {
        $this->predicate[] = [
            'name' => 'like',
            'attribute' => $attribute,
            'value' => $value,
        ];

        return $this;
    }

    /**
     * @param string $attribute
     * @return $this
     */
    public function isNull($attribute)
    {
        $this->predicate[] = [
            'name' => 'isNull',
            'attribute' => $attribute,
        ];

        return $this;
    }

    /**
     * @param string $attribute
     * @return $this
     */
    public function isNotNull($attribute)
    {
        $this->predicate[] = [
            'name' => 'isNotNull',
            'attribute' => $attribute,
        ];

        return $this;
    }

    /**
     * @param string $attribute
     * @param array $values
     * @return $this
     */
    public function in($attribute, array $values)
    {
        $this->predicate[] = [
            'name' => 'in',
            'attribute' => $attribute,
            'values' => $values,
        ];

        return $this;
    }

    /**
     * @param string $attribute
     * @param int|float|string $minValue
     * @param int|float|string $maxValue
     * @return $this
     */
    public function between($attribute, $minValue, $maxValue)
    {
        $this->predicate[] = [
            'name' => 'between',
            'attribute' => $attribute,
            'minValue' => $minValue,
            'maxValue' => $maxValue,
        ];

        return $this;
    }

    /**
     * @param string $attribute
     * @return $this
     */
    public function order($attribute)
    {
        $this->order[] = $attribute;

        return $this;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param int $offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @param string $entityName
     * @return CriteriaInterface
     */
    public function relation($entityName)
    {
        $relation = new Criteria($entityName);
        $this->relations[] = $relation;
        return $relation;
    }

    /**
     * @param string $entityName
     * @return CriteriaInterface
     */
    public function orCriteria($entityName = null)
    {
        if (is_null($entityName)) {
            $entityName = $this->entityName;
        }

        $orCriteria = new Criteria($entityName);
        $this->orCriteria[] = $orCriteria;
        return $orCriteria;
    }

    /**
     * @return CriteriaInterface[]
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * @return array
     */
    public function getPredicate()
    {
        return $this->predicate;
    }

    /**
     * @return CriteriaInterface[]
     */
    public function getOr()
    {
        return $this->orCriteria;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @return array
     */
    public function getOrder()
    {
        return $this->order;
    }

}