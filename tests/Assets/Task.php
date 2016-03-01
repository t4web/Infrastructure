<?php

namespace T4webInfrastructureTest\Assets;

use T4webDomainInterface\EntityInterface;

class Task implements EntityInterface
{
    protected $id;
    protected $name;
    protected $assignee;

    public function __construct(array $data = [])
    {
        $this->populate($data);
    }

    public function getId()
    {
        return $this->id;
    }

    public function extract(array $properties = [])
    {
        $state = get_object_vars($this);

        if (empty($properties)) {
            return $state;
        }

        $rawArray = array_fill_keys($properties, null);

        return array_intersect_key($state, $rawArray);
    }

    public function populate(array $array = [])
    {
        $state = get_object_vars($this);

        $stateIntersect = array_intersect_key($array, $state);

        foreach ($stateIntersect as $key => $value) {
            $this->$key = $value;
        }

        return $this;
    }
}
