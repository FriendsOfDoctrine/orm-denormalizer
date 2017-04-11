<?php
namespace Argayash\DenormalizedOrm;

/**
 * Class DnTableGroupContainer
 * @package Argayash\DenormalizedOrm
 */
class DnTableGroupContainer implements \Iterator
{
    /** @var DnTableGroup[] */
    protected $dnTableGroups = [];
    /**
     * @var int
     */
    protected $position = 0;

    /**
     * @param DnTableGroup $dnTableGroup
     */
    public function add(DnTableGroup $dnTableGroup)
    {
        $this->dnTableGroups[] = $dnTableGroup;
    }

    /**
     * @param string $className
     *
     * @return DnTableGroup[]
     */
    public function getByContainClass(string $className)
    {
        return array_filter($this->dnTableGroups, function ($dnTableGroup) use ($className) {
            /** @var DnTableGroup $dnTableGroup */
            return $dnTableGroup->hasClass($className);
        });
    }

    public function current()
    {
        return $this->dnTableGroups[$this->position]??null;
    }

    public function next()
    {
        ++$this->position;
    }

    public function key()
    {
        return $this->position;
    }

    public function valid()
    {
        return isset($this->dnTableGroups[$this->position]);
    }

    public function rewind()
    {
        $this->position = 0;
    }
}