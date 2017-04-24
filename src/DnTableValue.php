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

use Doctrine\Common\Util\Inflector;

/**
 * Class DnTableValue
 * @package FOD\OrmDenormalizer
 * @author Nikolay Mitrofanov <mitrofanovnk@gmail.com>
 */
class DnTableValue
{
    /** @var  \stdClass */
    protected $entity;

    /** @var  DnColumn */
    protected $column;

    /**
     * @var mixed
     */
    protected $value;

    public function __construct(DnColumn $column, $entity)
    {
        $this->entity = $entity;
        $this->column = $column;

        $this->value = $this->getPropertyByName($column->getTargetPropertyName());
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->column->getName();
    }

    /**
     * @param string $propertyName
     *
     * @return mixed
     */
    protected function getPropertyByName($propertyName)
    {
        if (null === $this->entity) {
            return null;
        }
        foreach (['get', 'is'] as $methodPrefix) {
            $propertyGetMethod = $methodPrefix . Inflector::ucwords($propertyName);
            if (method_exists($this->entity, $propertyGetMethod)) {
                return $this->entity->{$propertyGetMethod}();
            }
            if (property_exists($this->entity, $propertyName)) {
                return $this->entity->{$propertyName};
            }
        }

        return null;
    }
}