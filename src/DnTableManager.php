<?php
namespace Argayash\DenormalizedOrm;

use Argayash\DenormalizedOrm\Mapping\DnClassMetadata;
use Argayash\DenormalizedOrm\Mapping\DnClassMetadataFactory;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Class DnTableManager
 * @package AppBundle\DenormalizedOrm
 */
class DnTableManager
{
    /** @var  EntityManager */
    protected $em;

    /** @var DnClassMetadataFactory */
    protected $classMetadataFactory;

    /** @var ClassMetadata[] */
    protected $classesMetadata = [];

    /** @var DnClassMetadata[] */
    protected $dnClassesMetadata = [];

    /**
     * @var DnTableGroup[]
     */
    protected $dnTableGroups = [];

    /**
     * DnTableManager constructor.
     *
     * @param EntityManager $entityManager
     * @param DnClassMetadataFactory $classMetadataFactory
     */
    public function __construct(EntityManager $entityManager, DnClassMetadataFactory $classMetadataFactory)
    {
        $this->em = $entityManager;
        $this->classMetadataFactory = $classMetadataFactory;

        $this->loadDnTables()->loadGroups();
    }

    /**
     * @return DnTableGroup[]
     */
    public function getDnTableGroups(): array
    {
        return $this->dnTableGroups;
    }

    /**
     * @return $this
     */
    protected function loadDnTables()
    {
        /** @var ClassMetadata $classMetadata */
        foreach ($this->em->getMetadataFactory()->getAllMetadata() as $classMetadata) {
            if ($dnClassMetadata = $this->classMetadataFactory->loadMetadata($classMetadata->name)) {
                $this->classesMetadata[$classMetadata->name] = $classMetadata;
                $this->dnClassesMetadata[$classMetadata->name] = $dnClassMetadata;
            }
        }

        return $this;
    }

    /**
     * @param string $firstClass
     * @param array $classRelation
     * @param array $classesRelation
     *
     * @return array
     */
    protected function getEntityGroupSchema(string $firstClass, array $classRelation, array $classesRelation)
    {
        $relation = [];

        foreach ($classRelation as $field => $relationClass) {
            if ($firstClass !== $relationClass) {
                $relation[$firstClass][$field] = $relationClass;
                if (isset($classesRelation[$relationClass])) {
                    $relation += $this->getEntityGroupSchema($relationClass, $classesRelation[$relationClass], $classesRelation);
                }
            }
        }

        return $relation;
    }

    /**
     * @return $this
     */
    protected function loadGroups()
    {
        $group = [];
        $dependsEntities = [];
        foreach ($this->classesMetadata as $classMetadata) {
            foreach ($classMetadata->getAssociationMappings() as $association) {
                if ($this->classesMetadata[$association['targetEntity']]??null) {
                    if (!empty($association['joinColumns'])) {
                        /** Many-One */
                        $group[$classMetadata->name][$association['fieldName']] = $association['targetEntity'];
                        $dependsEntities[] = $association['targetEntity'];
                    }
                }
            }
        }

        foreach (array_filter($group, function ($key) use ($dependsEntities) {
            return !in_array($key, $dependsEntities, true);
        }, ARRAY_FILTER_USE_KEY) as $firstEntityName => $mappingEntities) {
            if (isset($group[$firstEntityName])) {
                $dnTableGroup = new DnTableGroup($this->getEntityGroupSchema($firstEntityName, $group[$firstEntityName], $group), $this->classesMetadata, $this->dnClassesMetadata);
                $this->dnTableGroups[] = $dnTableGroup;
            }
        }

        return $this;
    }

    /**
     * @param DnTableGroup $dnTableGroup
     * @param Connection|null $connection
     *
     * @return string[]
     */
    public function getMigrationSQL(DnTableGroup $dnTableGroup, Connection $connection = null): array
    {
        if (null === $connection) {
            $connection = $this->em->getConnection();
        }
        $fromSchema = $connection->getSchemaManager()->createSchema();

        $toSchema = clone $fromSchema;
        $newTable = $toSchema->createTable($dnTableGroup->getTableName());

        /** @var DnColumn $column */
        foreach ($dnTableGroup->getColumns() as $column) {
            $newTable->addColumn($column->getName(), $column->getType(), $column->getOptions());
        }

        if ($dnTableGroup->getIndexes()) {
            $newTable->setPrimaryKey($dnTableGroup->getIndexes());
        }

        return $fromSchema->getMigrateToSql($toSchema, $connection->getDatabasePlatform());
    }

    /**
     * @param DnTableGroup $dnTableGroup
     * @param Connection|null $connection
     */
    public function createTable(DnTableGroup $dnTableGroup, Connection $connection = null)
    {
        if (null === $connection) {
            $connection = $this->em->getConnection();
        }
        foreach ($this->getMigrationSQL($dnTableGroup, $connection) as $sql) {
            $connection->exec($sql);
        }
    }
}