<?php
namespace Argayash\DenormalizedOrm;


class DnColumn
{
    /** @var  string */
    protected $name;
    /** @var  string */
    protected $type;
    /** @var array */
    protected $options = [];

    /**
     * @var string
     */
    protected $targetEntityClass;
    /**
     * @var string
     */
    protected $targetPropertyName;

    /**
     * DnColumn constructor.
     *
     * @param string $name
     * @param array $field
     * @param string $targetEntityClass
     * @param string $targetPropertyName
     */
    public function __construct(string $name, array $field, string $targetEntityClass, string $targetPropertyName)
    {
        $this->name = $name;

        $this->type = $field['type']??'string';
        $this->targetEntityClass = $targetEntityClass;
        $this->targetPropertyName = $targetPropertyName;

        $this->options = array_filter($field, function ($key) {
            return in_array($key, ['scale', 'length', 'unique', 'nullable', 'precision', 'id'], true);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return strtolower($this->name);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return string
     */
    public function getTargetEntityClass(): string
    {
        return $this->targetEntityClass;
    }

    /**
     * @return string
     */
    public function getTargetPropertyName(): string
    {
        return $this->targetPropertyName;
    }
}