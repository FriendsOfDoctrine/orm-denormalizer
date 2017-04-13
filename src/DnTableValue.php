<?php
namespace FOD\OrmDenormalized;


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
        $propertyGetMethod = 'get' . ucfirst($propertyName);
        if (method_exists($this->entity, $propertyGetMethod)) {
            return $this->entity->{$propertyGetMethod}();
        }
        if (property_exists($this->entity, $propertyName)) {
            return $this->entity->{$propertyName};
        }

        return null;
    }
}