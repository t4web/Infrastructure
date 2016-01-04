<?php

namespace T4webInfrastructure;

use T4webDomainInterface\Infrastructure\CriteriaInterface;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Predicate\Predicate;

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
     * @var CriteriaInterface[]
     */
    protected $andCriteria = [];

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
     * @var Config
     */
    protected $config;
    protected $sqlSelect;
    protected $where;

    /**
     * @param string $entityName
     */
    public function __construct($entityName, Config $config, Select $select = null)
    {
        $this->entityName = $entityName;
        $this->config = $config;
        if ($select === null) {
            $this->sqlSelect = new Select();
            $this->sqlSelect->from($this->config->getTable($this->entityName));
        } else {
            $this->sqlSelect = $select;
        }
        $this->where = new Where();
    }

    /**
     * @return Select
     */
    public function getSelect()
    {
        //$this->sqlSelect->where->equalTo();
        return $this->sqlSelect;
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
        //if ($this->config->isRelationManyToMany($this->entityName, $entityName)) {
        //$this->buildManyToMany($select, $criteria->getEntityName(), $relation);
        //} else {
        $table = $this->config->getTable($entityName);


        $this->sqlSelect->join(
            $table,
            $this->config->getRelationExpression($this->entityName, $entityName),
            []
        );
        //}

        $relationCriteria = new self($entityName, $this->config, $this->sqlSelect);

        return $relationCriteria;
    }

    /**
     * @param string $attribute
     * @param bool|int|float|string $value
     * @return $this
     */
    public function equalTo($attribute, $value)
    {
        $where = new Where();
        //$this->where->equalTo($this->getField($attribute), $value);

        //var_dump($attribute, $this->getField($attribute), $value);

        $this->sqlSelect->where->equalTo($this->getField($attribute), $value);

        return $this;
    }

    /**
     * @param string $attribute
     * @param bool|int|float|string $value
     * @return $this
     */
    public function notEqualTo($attribute, $value)
    {
        $where = new Where();
        $where->notEqualTo($this->getField($attribute), $value);

        $this->sqlSelect->where($where);

        return $this;
    }

    /**
     * @param string $attribute
     * @param int|float $value
     * @return $this
     */
    public function lessThan($attribute, $value)
    {
        $where = new Where();
        $where->lessThan($this->getField($attribute), $value);

        $this->sqlSelect->where($where);

        return $this;
    }

    /**
     * @param string $attribute
     * @param int|float $value
     * @return $this
     */
    public function greaterThan($attribute, $value)
    {
        $where = new Where();
        $where->greaterThan($this->getField($attribute), $value);

        //$this->sqlSelect->where($where);
        $this->sqlSelect->where->greaterThan($this->getField($attribute), $value);

        return $this;
    }

    /**
     * @param string $attribute
     * @param int|float $value
     * @return $this
     */
    public function greaterThanOrEqualTo($attribute, $value)
    {
        $where = new Where();
        $where->greaterThanOrEqualTo($this->getField($attribute), $value);

        $this->sqlSelect->where($where);

        return $this;
    }

    /**
     * @param string $attribute
     * @param int|float $value
     * @return $this
     */
    public function lessThanOrEqualTo($attribute, $value)
    {
        $where = new Where();
        $where->lessThanOrEqualTo($this->getField($attribute), $value);

        $this->sqlSelect->where($where);

        return $this;
    }

    /**
     * @param string $attribute
     * @param int|float $value
     * @return $this
     */
    public function like($attribute, $value)
    {
        $where = new Where();
        $where->like($this->getField($attribute), $value);

        $this->sqlSelect->where($where);

        return $this;
    }

    /**
     * @param string $attribute
     * @return $this
     */
    public function isNull($attribute)
    {
        $where = new Where();
        $where->isNull($this->getField($attribute));

        $this->sqlSelect->where($where);

        return $this;
    }

    /**
     * @param string $attribute
     * @return $this
     */
    public function isNotNull($attribute)
    {
        $where = new Where();
        $where->isNotNull($this->getField($attribute));

        //$this->sqlSelect->where($where);
        $this->sqlSelect->where->isNotNull($this->getField($attribute));

        return $this;
    }

    /**
     * @param string $attribute
     * @param array $values
     * @return $this
     */
    public function in($attribute, array $values)
    {
        $where = new Where();
        $where->in($this->getField($attribute), $values);

        $this->sqlSelect->where($where);

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
        $where = new Where();
        $where->between($this->getField($attribute), $minValue, $maxValue);

        $this->sqlSelect->where($where);

        return $this;
    }

    /**
     * @param string $attribute
     * @return $this
     */
    public function order($attribute)
    {
        $this->sqlSelect->order($attribute);

        return $this;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->sqlSelect->limit($limit);

        return $this;
    }

    /**
     * @param int $offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->sqlSelect->offset($offset);

        return $this;
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
     * @param string $entityName
     * @return CriteriaInterface
     */
    public function andCriteria($entityName = null)
    {
        if (is_null($entityName)) {
            $entityName = $this->entityName;
        }

        $andCriteria = new Criteria($entityName);
        $this->andCriteria[] = $andCriteria;
        return $andCriteria;
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


    private function getField($attribute)
    {
        $table = $this->config->getTable($this->entityName);
        $field = $this->config->getFiled($this->entityName, $attribute);

        return $table.".".$field;
    }
}


