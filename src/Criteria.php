<?php

namespace T4webInfrastructure;

use T4webDomainInterface\Infrastructure\CriteriaInterface;
use Zend\Db\Sql\Select;

class Criteria implements CriteriaInterface
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Select
     */
    protected $select;

    /**
     * @param string $entityName
     */
    public function __construct($entityName, Config $config, Select $select = null)
    {
        $this->entityName = $entityName;
        $this->config = $config;
        if ($select === null) {
            $this->select = new Select();
            $this->select->from($this->config->getTable($this->entityName));
        } else {
            $this->select = $select;
        }
    }

    /**
     * @return Select
     */
    public function getQuery()
    {
        return $this->select;
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @param string $entityName
     * @return CriteriaInterface
     */
    public function relation($entityName)
    {
        if ($this->config->isRelationManyToMany($this->entityName, $entityName)) {

            list($linkTable,
                $mainField,
                $joinedField) = $this->config->getRelationManyToMany($this->entityName, $entityName);

            $mainTable = $this->config->getTable($this->entityName);
            $joinedTable = $this->config->getTable($entityName);

            $this->select->join(
                $linkTable,
                "$linkTable.$mainField = $mainTable.id",
                []
            );

            $this->select->join(
                $joinedTable,
                "$linkTable.$joinedField = $joinedTable.id",
                []
            );

        } else {
            $table = $this->config->getTable($entityName);

            $this->select->join(
                $table,
                $this->config->getRelationExpression($this->entityName, $entityName),
                []
            );
        }

        $relationCriteria = new self($entityName, $this->config, $this->select);

        return $relationCriteria;
    }

    /**
     * @param string $attribute
     * @param bool|int|float|string $value
     * @return $this
     */
    public function equalTo($attribute, $value)
    {
        $this->select->where->equalTo($this->getField($attribute), $value);

        return $this;
    }

    /**
     * @param string $attribute
     * @param bool|int|float|string $value
     * @return $this
     */
    public function notEqualTo($attribute, $value)
    {
        $this->select->where->notEqualTo($this->getField($attribute), $value);

        return $this;
    }

    /**
     * @param string $attribute
     * @param int|float $value
     * @return $this
     */
    public function lessThan($attribute, $value)
    {
        $this->select->where->lessThan($this->getField($attribute), $value);

        return $this;
    }

    /**
     * @param string $attribute
     * @param int|float $value
     * @return $this
     */
    public function greaterThan($attribute, $value)
    {
        $this->select->where->greaterThan($this->getField($attribute), $value);

        return $this;
    }

    /**
     * @param string $attribute
     * @param int|float $value
     * @return $this
     */
    public function greaterThanOrEqualTo($attribute, $value)
    {
        $this->select->where->greaterThanOrEqualTo($this->getField($attribute), $value);

        return $this;
    }

    /**
     * @param string $attribute
     * @param int|float $value
     * @return $this
     */
    public function lessThanOrEqualTo($attribute, $value)
    {
        $this->select->where->lessThanOrEqualTo($this->getField($attribute), $value);

        return $this;
    }

    /**
     * @param string $attribute
     * @param int|float $value
     * @return $this
     */
    public function like($attribute, $value)
    {
        $this->select->where->like($this->getField($attribute), $value);

        return $this;
    }

    /**
     * @param string $attribute
     * @return $this
     */
    public function isNull($attribute)
    {
        $this->select->where->isNull($this->getField($attribute));

        return $this;
    }

    /**
     * @param string $attribute
     * @return $this
     */
    public function isNotNull($attribute)
    {
        $this->select->where->isNotNull($this->getField($attribute));

        return $this;
    }

    /**
     * @param string $attribute
     * @param array $values
     * @return $this
     */
    public function in($attribute, array $values)
    {
        $this->select->where->in($this->getField($attribute), $values);

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
        $this->select->where->between($this->getField($attribute), $minValue, $maxValue);

        return $this;
    }

    /**
     * @param string $attribute
     * @return $this
     */
    public function order($attribute)
    {
        $exploded = explode(' ', $attribute);
        if (count($exploded) == 2) {
            $order = $this->getField($exploded[0]) . ' ' . $exploded[1];
        } else {
            $order = $this->getField($attribute);
        }

        $this->select->order($order);

        return $this;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->select->limit($limit);

        return $this;
    }

    /**
     * @param int $offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->select->offset($offset);

        return $this;
    }

    /**
     * @param $attribute
     * @return string
     */
    public function getField($attribute)
    {
        $table = $this->config->getTable($this->entityName);
        $field = $this->config->getFiled($this->entityName, $attribute);

        return $table.".".$field;
    }
}
