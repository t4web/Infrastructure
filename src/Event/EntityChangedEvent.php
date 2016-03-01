<?php

namespace T4webInfrastructure\Event;

use Zend\EventManager\Event;
use T4webDomainInterface\EntityInterface;

class EntityChangedEvent extends Event
{

    /**
     * @var EntityInterface
     */
    protected $changedEntity;

    /**
     * @var EntityInterface
     */
    protected $originalEntity;

    /**
     * @return EntityInterface
     */
    public function getChangedEntity()
    {
        return $this->changedEntity;
    }

    /**
     * @param EntityInterface $changedEntity
     */
    public function setChangedEntity(EntityInterface $changedEntity)
    {
        $this->changedEntity = $changedEntity;
    }

    /**
     * @return EntityInterface
     */
    public function getOriginalEntity()
    {
        return $this->originalEntity;
    }

    /**
     * @param EntityInterface $originalEntity
     */
    public function setOriginalEntity(EntityInterface $originalEntity)
    {
        $this->originalEntity = $originalEntity;
    }
}
