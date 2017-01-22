<?php

namespace T4webInfrastructure;

use T4webDomainInterface\Infrastructure\CriteriaInterface;

class InMemoryCriteria implements CriteriaInterface
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var array
     */
    protected $callbacks;

    /**
     * @param string $entityName
     */
    public function __construct($entityName)
    {
        $this->entityName = $entityName;
        $this->callbacks = [];
    }

    /**
     * @return mixed
     */
    public function getQuery()
    {
        $callbacks = $this->callbacks;
        return function(array $data) use ($callbacks) {
            foreach ($callbacks as $callback) {
                if (!$callback($data)) {
                    return false;
                }
            }

            return true;
        };
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
        // not implemented
        return $this;
    }

    /**
     * @param string $attribute
     * @param bool|int|float|string $value
     * @return $this
     */
    public function equalTo($attribute, $value)
    {
        $this->callbacks[] = function(array $data) use ($attribute, $value) {
            if (!array_key_exists($attribute, $data)) {
                return false;
            }

            if ($data[$attribute] == $value) {
                return true;
            }

            return false;
        };

        return $this;
    }

    /**
     * @param string $attribute
     * @param bool|int|float|string $value
     * @return $this
     */
    public function notEqualTo($attribute, $value)
    {
        $this->callbacks[] = function(array $data) use ($attribute, $value) {
            if (!array_key_exists($attribute, $data)) {
                return false;
            }

            if ($data[$attribute] != $value) {
                return true;
            }

            return false;
        };

        return $this;
    }

    /**
     * @param string $attribute
     * @param int|float $value
     * @return $this
     */
    public function lessThan($attribute, $value)
    {
        $this->callbacks[] = function(array $data) use ($attribute, $value) {
            if (!array_key_exists($attribute, $data)) {
                return false;
            }

            if ($data[$attribute] < $value) {
                return true;
            }

            return false;
        };

        return $this;
    }

    /**
     * @param string $attribute
     * @param int|float $value
     * @return $this
     */
    public function greaterThan($attribute, $value)
    {
        $this->callbacks[] = function(array $data) use ($attribute, $value) {
            if (!array_key_exists($attribute, $data)) {
                return false;
            }

            if ($data[$attribute] > $value) {
                return true;
            }

            return false;
        };

        return $this;
    }

    /**
     * @param string $attribute
     * @param int|float $value
     * @return $this
     */
    public function greaterThanOrEqualTo($attribute, $value)
    {
        $this->callbacks[] = function(array $data) use ($attribute, $value) {
            if (!array_key_exists($attribute, $data)) {
                return false;
            }

            if ($data[$attribute] >= $value) {
                return true;
            }

            return false;
        };

        return $this;
    }

    /**
     * @param string $attribute
     * @param int|float $value
     * @return $this
     */
    public function lessThanOrEqualTo($attribute, $value)
    {
        $this->callbacks[] = function(array $data) use ($attribute, $value) {
            if (!array_key_exists($attribute, $data)) {
                return false;
            }

            if ($data[$attribute] <= $value) {
                return true;
            }

            return false;
        };

        return $this;
    }

    /**
     * @param string $attribute
     * @param int|float $value
     * @return $this
     */
    public function like($attribute, $value)
    {
        $this->callbacks[] = function(array $data) use ($attribute, $value) {
            if (!array_key_exists($attribute, $data)) {
                return false;
            }

            if (strpos($data[$attribute], $value) !== false) {
                return true;
            }

            return false;
        };

        return $this;
    }

    /**
     * @param string $attribute
     * @return $this
     */
    public function isNull($attribute)
    {
        $this->callbacks[] = function(array $data) use ($attribute) {
            if (!array_key_exists($attribute, $data)) {
                return false;
            }

            if (is_null($data[$attribute])) {
                return true;
            }

            return false;
        };

        return $this;
    }

    /**
     * @param string $attribute
     * @return $this
     */
    public function isNotNull($attribute)
    {
        $this->callbacks[] = function(array $data) use ($attribute) {
            if (!array_key_exists($attribute, $data)) {
                return false;
            }

            if (!is_null($data[$attribute])) {
                return true;
            }

            return false;
        };

        return $this;
    }

    /**
     * @param string $attribute
     * @param array $values
     * @return $this
     */
    public function in($attribute, array $values)
    {
        $this->callbacks[] = function(array $data) use ($attribute, $values) {
            if (!array_key_exists($attribute, $data)) {
                return false;
            }

            if (in_array($data[$attribute], $values)) {
                return true;
            }

            return false;
        };

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
        $this->callbacks[] = function(array $data) use ($attribute, $minValue, $maxValue) {
            if (!array_key_exists($attribute, $data)) {
                return false;
            }
            
            if ($data[$attribute] >= $minValue && $data[$attribute] <= $maxValue) {
                return true;
            }

            return false;
        };

        return $this;
    }

    /**
     * @param string $attribute
     * @return $this
     */
    public function order($attribute)
    {
        // not implemented
        return $this;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function limit($limit)
    {
        // not implemented
        return $this;
    }

    /**
     * @param int $offset
     * @return $this
     */
    public function offset($offset)
    {
        // not implemented
        return $this;
    }

    /**
     * @param $attribute
     * @return string
     */
    public function getField($attribute)
    {
        return $attribute;
    }
}
