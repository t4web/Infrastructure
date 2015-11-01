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

        return $this->entityMap[$entityName]['relations'][$joinEntityName];
    }
}