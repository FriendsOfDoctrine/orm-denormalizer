<?php
namespace Argayash\DenormalizedOrm;


use Argayash\DenormalizedOrm\Mapping\Annotation\DnTable;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Class DnTableGroup
 * @package AppBundle\DenormalizedOrm
 */
class DnTableGroup
{
    /**
     * @var array
     */
    protected $structureSchema = [];

    /** @var ClassMetadata[] */
    protected $classMetadata = [];

    /**
     * @var array
     */
    protected $columns = [];

    /**
     * @var array
     */
    protected $indexes = [];

    protected $isSetIndex = false;

    /**
     * DnTableGroup constructor.
     *
     * @param array $structureSchema
     * @param array $classMetadata
     */
    public function __construct(array $structureSchema, array $classMetadata)
    {
        $this->classMetadata = $classMetadata;
        $this->structureSchema = $structureSchema;
    }

    /**
     * @param string $parentClassName
     * @param string $fieldName
     * @param string $childrenClassName
     *
     * @return $this
     */
    public function add(string $parentClassName, string $fieldName, string $childrenClassName)
    {
        $this->structureSchema[$parentClassName][$fieldName] = $childrenClassName;

        return $this;
    }

    /**
     * @return array
     */
    public function getStructureSchema(): array
    {
        return $this->structureSchema;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        $tableName = [];

        foreach ($this->structureSchema as $entityClass => $entityJoins) {
            if ($classMetadata = $this->getClassMetadataByName($entityClass)) {
                $tableName[] = $classMetadata->table['name'];

                foreach ($entityJoins as $joinKey => $entityJoin) {
                    if ($classMetadata = $this->getClassMetadataByName($entityJoin)) {
                        $tableName[] = $joinKey . DnTable::DENORMALIZE_FIELD_DELIMITER . $classMetadata->table['name'];
                    }
                }
            }
        }

        return strtolower(implode(DnTable::DENORMALIZE_TABLE_DELIMITER, $tableName));
    }

    /**
     * @return DnColumn[]
     */
    public function getColumns()
    {
        foreach ($this->structureSchema as $entityClass => $entityJoins) {
            if ($classMetadata = $this->getClassMetadataByName($entityClass)) {
                $columnPrefix = $classMetadata->getReflectionClass()->getShortName();
                $this->getColumnsOfClassMetadata($columnPrefix, $classMetadata);
                foreach ($entityJoins as $joinKey => $entityJoin) {
                    if ($classMetadata = $this->getClassMetadataByName($entityJoin)) {
                        $this->getColumnsOfClassMetadata($columnPrefix . DnTable::DENORMALIZE_FIELD_DELIMITER . $joinKey, $classMetadata);
                    }
                }
            }
        }

        return $this->columns;
    }

    /**
     * @return array
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    /**
     * @param string $columnPrefix
     * @param ClassMetadata $classMetadata
     *
     * @return $this
     */
    protected function getColumnsOfClassMetadata(string $columnPrefix, ClassMetadata $classMetadata)
    {

        foreach ($classMetadata->fieldMappings as $fieldName => $field) {
            $dnColumn = new DnColumn($columnPrefix . DnTable::DENORMALIZE_FIELD_DELIMITER . $fieldName, $field, $classMetadata->name, $fieldName);
            if (!$this->isSetIndex && isset($field['id']) && $field['id']) {
                $this->indexes[] = $dnColumn->getName();
            }
            $this->columns[] = $dnColumn;
        }

        $this->isSetIndex = true;

        return $this;
    }

    /**
     * @param string $className
     *
     * @return ClassMetadata|null
     */
    protected function getClassMetadataByName(string $className)
    {
        return $this->classMetadata[$className]??null;
    }
}