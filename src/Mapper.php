<?php

namespace T4webInfrastructure;

use T4webDomainInterface\EntityInterface;

class Mapper
{
    /**
     * @var array
     */
    protected $columnsAsAttributesMap;

    /**
     * @param array $columnsAsAttributesMap
     */
    public function __construct(array $columnsAsAttributesMap)
    {
        $this->columnsAsAttributesMap = $columnsAsAttributesMap;
    }

    /**
     * @param EntityInterface $entity
     * @return array
     */
    public function toTableRow(EntityInterface $entity)
    {
        $objectState = $entity->extract(array_values($this->columnsAsAttributesMap));

        if (array_key_exists('id', $objectState)) {
            unset($objectState['id']);
        }

        return $this->getIntersectValuesAsKeys($this->columnsAsAttributesMap, $objectState);
    }

    /**
     * @param array $row
     * @return array
     */
    public function fromTableRow(array $row)
    {
        $attributesValues = $this->getIntersectValuesAsKeys(array_flip($this->columnsAsAttributesMap), $row);

        return $attributesValues;
    }

    /**
     * @param array $rows
     * @return array
     */
    public function fromTableRows(array $rows)
    {
        $attributesValues = [];
        foreach ($rows as $row) {
            $attributesValues[] = $this->fromTableRow($row);
        }

        return $attributesValues;
    }

    /**
     * @param array $array1
     * @param array $array2
     * @return array
     */
    private function getIntersectValuesAsKeys($array1, $array2)
    {
        $result = [];

        foreach ($array1 as $key => $value) {
            if (array_key_exists($value, $array2)) {
                $result[$key] = $array2[$value];
            }
        }

        return $result;
    }
}
