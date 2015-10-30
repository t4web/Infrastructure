<?php

namespace T4webInfrastructure;

use T4webDomainInterface\EntityInterface;
use T4webDomainInterface\EntityFactoryInterface;

class Mapper
{
    /**
     * @var array
     */
    protected $columnsAsAttributesMap;

    /**
     * @var EntityFactoryInterface
     */
    protected $entityFactory;

    /**
     * @param array $columnsAsAttributesMap
     * @param EntityFactoryInterface $factory
     */
    public function __construct(array $columnsAsAttributesMap, EntityFactoryInterface $factory)
    {
        $this->columnsAsAttributesMap = $columnsAsAttributesMap;
        $this->entityFactory = $factory;
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
     * @return EntityInterface
     */
    public function fromTableRow(array $row)
    {
        $attributesValues = $this->getIntersectValuesAsKeys(array_flip($this->columnsAsAttributesMap), $row);

        return $this->entityFactory->create($attributesValues);
    }

    /**
     * @param array $rows
     * @return EntityInterface[]
     */
    public function fromTableRows(array $rows)
    {
        $attributesValues = array();
        foreach ($rows as $row) {
            $attributesValues[] = $this->getIntersectValuesAsKeys(array_flip($this->columnsAsAttributesMap), $row);
        }

        return $this->entityFactory->createCollection($attributesValues);
    }

    /**
     * @param array $array1
     * @param array $array2
     * @return array
     */
    private function getIntersectValuesAsKeys($array1, $array2)
    {
        $result = array();

        foreach ($array1 as $key => $value) {
            if (array_key_exists($value, $array2)) {
                $result[$key] = $array2[$value];
            }
        }

        return $result;
    }
}
