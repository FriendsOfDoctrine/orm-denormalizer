<?php
namespace FOD\DoctrineOrmDenormalized;


use FOD\DoctrineOrmDenormalized\Mapping\Annotation\DnTable;
use FOD\DoctrineOrmDenormalized\Mapping\DnClassMetadata;
use Doctrine\DBAL\Connection;
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

    /**
     * @var DnColumn[]
     */
    protected $columns = [];

    /**
     * @var array
     */
    protected $indexes = [];

    /**
     * @var DnClassMetadata[]
     */
    protected $dnClassMetadata = [];
    /**
     * @var bool
     */
    protected $isSetIndex = false;

    /** @var DnTableValue[][] */
    protected $dnTableValues = [];

    /**
     * @var array
     */
    protected $columnValuesSetNumbers = [];

    /**
     * DnTableGroup constructor.
     *
     * @param array $structureSchema
     * @param DnClassMetadata[] $dnClassMetadata
     */
    public function __construct(array $structureSchema, array $dnClassMetadata)
    {
        $this->structureSchema = $structureSchema;
        $this->dnClassMetadata = $dnClassMetadata;
    }

    /**
     * @param DnTableValue $value
     *
     * @return $this
     */
    public function addColumnValue(DnTableValue $value)
    {
        $this->columnValuesSetNumbers[$value->getName()] = !isset($this->columnValuesSetNumbers[$value->getName()]) ? 0 : ($this->columnValuesSetNumbers[$value->getName()] + 1);
        $this->dnTableValues[$this->columnValuesSetNumbers[$value->getName()]][$value->getName()] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getValuesArray()
    {
        $values = [];
        foreach ($this->dnTableValues as $setIndex => $setValues) {
            foreach ($setValues as $dnTableValue) {
                $values[$setIndex][$dnTableValue->getName()] = $dnTableValue->getValue();
            }
        }

        return $values;
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    public function hasClass($className)
    {
        return count(array_filter($this->structureSchema, function ($value, $key) use ($className) {
                return $key === $className || in_array($className, $value, true);
            }, ARRAY_FILTER_USE_BOTH)) > 0;
    }

    /**
     * @return array
     */
    public function getStructureSchema()
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
            if (($classMetadata = $this->getClassMetadataByName($entityClass)) && ($dnClassMetadata = $this->getDnClassMetadataByName($entityClass))) {
                $tableName[] = $dnClassMetadata->getDnTable()->name ?: $classMetadata->reflClass->getShortName();

                foreach ($entityJoins as $joinKey => $entityJoin) {
                    if (($classMetadata = $this->getClassMetadataByName($entityJoin)) && ($dnClassMetadata = $this->getDnClassMetadataByName($entityJoin))) {
                        $tableName[] = $joinKey . DnTable::DENORMALIZE_FIELD_DELIMITER . ($dnClassMetadata->getDnTable()->name ?: $classMetadata->reflClass->getShortName());
                    }
                }
            }
        }

        return strtolower(implode(DnTable::DENORMALIZE_TABLE_DELIMITER, $tableName));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getTableName();
    }

    /**
     * @return DnColumn[]
     */
    public function getColumns()
    {
        if (!$this->columns) {
            foreach ($this->structureSchema as $entityClass => $entityJoins) {
                if ($classMetadata = $this->getClassMetadataByName($entityClass)) {
                    $columnPrefix = $classMetadata->getReflectionClass()->getShortName();
                    $this->getColumnsOfClassMetadata($columnPrefix, $this->getDnClassMetadataByName($entityClass));
                    foreach ($entityJoins as $joinKey => $entityJoin) {
                        if ($classMetadata = $this->getClassMetadataByName($entityJoin)) {
                            $this->getColumnsOfClassMetadata($columnPrefix . DnTable::DENORMALIZE_FIELD_DELIMITER . $joinKey, $this->getDnClassMetadataByName($entityJoin));
                        }
                    }
                }
            }
        }

        return $this->columns;
    }

    /**
     * @return array
     */
    public function getIndexes()
    {
        return $this->indexes;
    }

    /**
     * @param Connection $connection
     *
     * @return string[]
     */
    public function getMigrationSQL(Connection $connection)
    {
        $fromSchema = $connection->getSchemaManager()->createSchema();

        $toSchema = clone $fromSchema;
        $newTable = $toSchema->createTable($this->getTableName());

        /** @var DnColumn $column */
        foreach ($this->getColumns() as $column) {
            $newTable->addColumn($column->getName(), $column->getType(), $column->getOptions());
        }

        if ($this->getIndexes()) {
            $newTable->setPrimaryKey($this->getIndexes());
        }

        return $fromSchema->getMigrateToSql($toSchema, $connection->getDatabasePlatform());
    }

    /**
     * @param string $columnPrefix
     * @param DnClassMetadata $dnClassMetadata
     *
     * @return $this
     */
    protected function getColumnsOfClassMetadata($columnPrefix, DnClassMetadata $dnClassMetadata)
    {
        foreach ($dnClassMetadata->getClassMetadata()->fieldMappings as $fieldName => $field) {
            if (!in_array($fieldName, (array)$dnClassMetadata->getDnTable()->excludeFields, true)) {
                $dnColumn = new DnColumn($columnPrefix . DnTable::DENORMALIZE_FIELD_DELIMITER . $fieldName, $field, $dnClassMetadata->getClassMetadata()->name, $fieldName);
                if (!$this->isSetIndex && isset($field['id']) && $field['id']) {
                    $this->indexes[] = $dnColumn->getName();
                }
                $this->columns[$dnColumn->getName()] = $dnColumn;
            }
        }

        $this->isSetIndex = true;

        return $this;
    }

    /**
     * @param string $className
     *
     * @return ClassMetadata|null
     */
    protected function getClassMetadataByName($className)
    {
        return $this->getDnClassMetadataByName($className) ? $this->getDnClassMetadataByName($className)->getClassMetadata() : null;
    }

    /**
     * @param string $className
     *
     * @return DnClassMetadata|null
     */
    protected function getDnClassMetadataByName($className)
    {
        return isset($this->dnClassMetadata[$className]) ? $this->dnClassMetadata[$className] : null;
    }
}