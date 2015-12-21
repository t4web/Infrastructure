<?php
namespace T4webInfrastructure;

class Config
{
    /**
     * @var array
     */
    protected $entityMap = [];

    public function __construct(array $entityMap)
    {
        $this->entityMap = $entityMap;
    }

    /**
     * @param string $entityName
     *
     * @return string
     */
    public function getTable($entityName)
    {
        if (!isset($this->entityMap[$entityName]['table'])) {
            throw new ConfigException(sprintf("entity_map not configured for %s", $entityName));
        }

        return $this->entityMap[$entityName]['table'];
    }

    /**
     * @param string $entityName
     * @param string $attribute
     * @return string
     */
    public function getFiled($entityName, $attribute)
    {
        if (!isset($this->entityMap[$entityName]['columnsAsAttributesMap'])) {
            throw new ConfigException(sprintf("entity_map[columnsAsAttributesMap] not configured for %s", $entityName));
        }

        $field = array_search($attribute, $this->entityMap[$entityName]['columnsAsAttributesMap']);

        if (!$field) {
            throw new ConfigException(sprintf("attributes %s not exists in entity_map[columnsAsAttributesMap] config", $attribute));
        }

        return $field;
    }

    /**
     * @param string $entityName
     * @param string $joinEntityName
     *
     * @return string
     */
    public function getRelationExpression($entityName, $joinEntityName)
    {
        if (!isset($this->entityMap[$entityName]['relations'][$joinEntityName])) {
            throw new ConfigException(
                sprintf(
                    "entity_map for %s not configured with relation %s",
                    $entityName,
                    $joinEntityName
                )
            );
        }

        if (!isset($this->entityMap[$entityName]['relations'][$joinEntityName][0])
            || !isset($this->entityMap[$entityName]['relations'][$joinEntityName][1])) {
            throw new ConfigException(
                sprintf(
                    "entity_map for %s with relation %s must be array [field, joined-filed]",
                    $entityName,
                    $joinEntityName
                )
            );
        }

        return $this->entityMap[$entityName]['relations'][$joinEntityName][0].' = '
            . $this->entityMap[$entityName]['relations'][$joinEntityName][1];
    }

    /**
     * @param string $entityName
     * @param string $joinEntityName
     * @return bool
     */
    public function isRelationManyToMany($entityName, $joinEntityName)
    {
        if (!isset($this->entityMap[$entityName]['relations'][$joinEntityName])) {
            throw new ConfigException(
                sprintf(
                    "entity_map for %s not configured with relation %s",
                    $entityName,
                    $joinEntityName
                )
            );
        }

        if (!isset($this->entityMap[$entityName]['relations'][$joinEntityName][0])
            || !isset($this->entityMap[$entityName]['relations'][$joinEntityName][1])
            || !isset($this->entityMap[$entityName]['relations'][$joinEntityName][2])) {
            return false;
        }

        return true;
    }

    /**
     * @param string $entityName
     * @param string $joinEntityName
     * @return array
     */
    public function getRelationManyToMany($entityName, $joinEntityName)
    {
        if (!$this->isRelationManyToMany($entityName, $joinEntityName)) {
            throw new ConfigException(
                sprintf(
                    "entity_map for %s with relation %s must be array [link-table, field, joined-filed]",
                    $entityName,
                    $joinEntityName
                )
            );
        }

        return [
            $this->entityMap[$entityName]['relations'][$joinEntityName][0],
            $this->entityMap[$entityName]['relations'][$joinEntityName][1],
            $this->entityMap[$entityName]['relations'][$joinEntityName][2],
        ];
    }

    /**
     * @param string $entityName
     *
     * @return array
     */
    public function getColumnsAsAttributesMap($entityName)
    {
        if (!isset($this->entityMap[$entityName]['columnsAsAttributesMap'])) {
            throw new ConfigException(
                sprintf("entity_map[columnsAsAttributesMap] not configured for %s", $entityName)
            );
        }

        if (!is_array($this->entityMap[$entityName]['columnsAsAttributesMap'])) {
            throw new ConfigException(
                sprintf("entity_map[columnsAsAttributesMap] for %s must be array", $entityName)
            );
        }

        return $this->entityMap[$entityName]['columnsAsAttributesMap'];
    }
}
