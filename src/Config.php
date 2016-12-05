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
     * @return string
     */
    public function getEntityClass($entityName)
    {
        if (!isset($this->entityMap[$entityName]['entityClass'])) {
            throw new ConfigException(sprintf("entity_map[entityClass] not configured for %s", $entityName));
        }

        return $this->entityMap[$entityName]['entityClass'];
    }
    
    /**
     * @param string $entityName
     * @return string
     */
    public function getCollectionClass($entityName)
    {
        if (!isset($this->entityMap[$entityName]['collectionClass'])) {
            return 'ArrayObject';
        }

        return $this->entityMap[$entityName]['collectionClass'];
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
            throw new ConfigException(
                sprintf("attributes %s not exists in entity_map[columnsAsAttributesMap] config", $attribute)
            );
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

    /**
     * @param string $entityName
     * @return string|null
     */
    public function getPrimaryKey($entityName)
    {
        if (isset($this->entityMap[$entityName]['primaryKey'])) {
            return $this->entityMap[$entityName]['primaryKey'];
        }
    }

    /**
     * @param string $entityName
     * @return string|null
     */
    public function getSequence($entityName)
    {
        if (isset($this->entityMap[$entityName]['sequence'])) {
            return $this->entityMap[$entityName]['sequence'];
        }
    }

    /**
     * @param string $entityName
     * @return string|null
     */
    public function getCriteriaMap($entityName)
    {
        if (isset($this->entityMap[$entityName]['criteriaMap'])) {
            return $this->entityMap[$entityName]['criteriaMap'];
        }
    }

    /**
     * @param string $entityName
     * @return string
     */
    public function getNamespace($entityName)
    {
        if (isset($this->entityMap[$entityName]['namespace'])) {
            return $this->entityMap[$entityName]['namespace'];
        }

        return "{$entityName}s\\$entityName";
    }

    public function getCustomCriteriaClass($entityName, $criteriaName)
    {
        if (class_exists($criteriaName)) {
            return $criteriaName;
        }

        $entityNamespace = $this->getNamespace($entityName);
        $className = "$entityNamespace\\Infrastructure\\Criteria\\$criteriaName";
        
        return $className;
    }
}
