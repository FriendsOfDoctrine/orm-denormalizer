<?php
/**
 *  This file is part of the FOD\OrmDenormalizer package -- Denormalizer ORM Doctrine library
 *
 *  (c) FriendsOfDoctrine <https://github.com/FriendsOfDoctrine/>.
 *
 *  For the full copyright and license inflormation, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace FOD\OrmDenormalizer;

/**
 * Class DnTableGroupContainer
 * @package FOD\OrmDenormalizer
 * @author Nikolay Mitrofanov <mitrofanovnk@gmail.com>
 */
class DnTableGroupContainer implements \Iterator
{
    /** @var DnTableGroup[] */
    protected $dnTableGroups;
    /**
     * @var int
     */
    protected $position = 0;

    /** @var  DnTableGroupContainer */
    protected static $instance;

    /**
     * DnTableGroupContainer constructor.
     */
    protected function __construct()
    {
        $this->dnTableGroups = [];
    }

    /**
     * @return DnTableGroupContainer
     */
    public static function getInstance()
    {
        return (self::$instance = null !== self::$instance ? self::$instance : new self());
    }

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
    public function getByContainClass($className)
    {
        return array_filter($this->dnTableGroups, function ($dnTableGroup) use ($className) {
            /** @var DnTableGroup $dnTableGroup */
            return $dnTableGroup->hasClass($className);
        });
    }

    /**
     * @param string $className
     *
     * @return DnTableGroup[]
     */
    public function getByLeadClass($className)
    {
        return array_filter($this->dnTableGroups, function ($dnTableGroup) use ($className) {
            /** @var DnTableGroup $dnTableGroup */
            return current(array_keys($dnTableGroup->getStructureSchema())) === $className;
        });
    }

    public function current()
    {
        return isset($this->dnTableGroups[$this->position]) ? $this->dnTableGroups[$this->position] : null;
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